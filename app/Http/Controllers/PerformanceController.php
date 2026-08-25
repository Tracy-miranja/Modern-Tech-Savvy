<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceKeyResult;
use App\Models\PerformanceObjective;
use App\Models\PerformanceReview;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;

class PerformanceController extends Controller
{
    use HandleTransactions;

    public function cyclesIndex(Business $business)
    {
        return view('performance.cycles', ['business' => $business]);
    }

    public function setupIndex(Business $business)
    {
        return view('performance.setup', ['business' => $business]);
    }

    public function objectivesOverview(Business $business)
    {
        return view('performance.objectives', [
            'business' => $business,
            'departments' => \App\Models\Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']),
            'jobCategories' => \App\Models\JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function fetchObjectivesOverview(Request $request, Business $business)
    {
        $activeRole = strtolower((string) session('active_role'));
        if (!in_array($activeRole, ['business-hr', 'business-admin'], true)) {
            return RequestResponse::forbidden('You are not authorized to view this.');
        }

        $query = Employee::where('business_id', $business->id)->with(['user', 'department']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('job_category_id')) {

            $jobCategoryId = $request->integer('job_category_id');
            $query->whereHas('employmentDetails', fn ($q) => $q->where('job_category_id', $jobCategoryId));
        }
        if ($request->filled('employee_ids')) {
            $query->whereIn('id', (array) $request->input('employee_ids'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $employees = $query->orderBy('id')->paginate(min((int) $request->input('per_page', 20), 100) ?: 20);

        $cycleId = $request->integer('performance_cycle_id');
        $objectivesByEmployee = PerformanceObjective::where('business_id', $business->id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId))
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->getCollection()->map(function (Employee $employee) use ($objectivesByEmployee) {
            $objectives = $objectivesByEmployee->get($employee->id, collect());
            $count = $objectives->count();

            return [
                'employee_id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'department' => $employee->department->name ?? '—',
                'objectives_count' => $count,
                'avg_progress' => $count ? (int) round($objectives->avg(fn ($o) => $o->progress)) : null,
                'critical_count' => $objectives->where('confidence', 'critical')->count(),
            ];
        })->values();

        return RequestResponse::ok('Fetched.', [
            'rows' => $rows,
            'current_page' => $employees->currentPage(),
            'last_page' => $employees->lastPage(),
            'total' => $employees->total(),
        ]);
    }

    public function fetchActiveCycles(Business $business)
    {
        $cycles = PerformanceCycle::where('business_id', $business->id)
            ->where('status', 'active')
            ->latest()
            ->get();

        return RequestResponse::ok('Active cycles fetched successfully.', $cycles);
    }

    public function fetchCycles(Business $business)
    {
        $cycles = PerformanceCycle::where('business_id', $business->id)->latest()->get();
        return RequestResponse::ok('Cycles fetched successfully.', $cycles);
    }

    public function storeCycle(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kpi_weight' => 'required|numeric|min:0|max:100',
            'okr_weight' => 'required|numeric|min:0|max:100',
            'competency_weight' => 'required|numeric|min:0|max:100',
            'self_review_due_date' => 'nullable|date',
            'manager_review_due_date' => 'nullable|date',
            'lock_goals_on_start' => 'nullable|boolean',
        ]);

        $totalWeight = $validated['kpi_weight'] + $validated['okr_weight'] + $validated['competency_weight'];
        if (abs($totalWeight - 100) > 0.01) {
            return RequestResponse::badRequest('KPI, OKR, and competency weights must add up to 100%.', 422);
        }

        return $this->handleTransaction(function () use ($validated, $business, $request) {
            $cycle = PerformanceCycle::create(array_merge($validated, [
                'business_id' => $business->id,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]));

            return RequestResponse::created('Performance cycle created successfully.', $cycle);
        });
    }

    public function updateCycleStatus(Request $request, Business $business, PerformanceCycle $cycle)
    {
        if ((int) $cycle->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Cycle not found for this business.', 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,active,closed',
        ]);

        $wasClosed = $cycle->status === 'closed';
        $cycle->update(['status' => $validated['status']]);

        if ($validated['status'] === 'closed' && !$wasClosed) {
            $objectives = PerformanceObjective::where('performance_cycle_id', $cycle->id)
                ->with('keyResults')
                ->get();

            foreach ($objectives as $objective) {
                $objective->update(['final_score' => $objective->computeFinalScore()]);
            }
        }

        return RequestResponse::ok('Cycle status updated successfully.');
    }

    public function myPerformance(Business $business)
    {
        $employee = auth()->user()->activeEmployee();

        if (!$employee || (int) $employee->business_id !== (int) $business->id) {
            return view('performance.no-employee');
        }

        return view('performance.employee', [
            'business' => $business,
            'employee' => $employee,
            'isOwnProfile' => true,
            'routePrefix' => 'myaccount',
            'departments' => \App\Models\Department::where('business_id', $business->id)->get(['id', 'name']),
            'isHrOrAdmin' => in_array(strtolower((string) session('active_role')), ['business-hr', 'business-admin'], true),
        ]);
    }

    public function employeePerformance(Request $request, Business $business, Employee $employee)
    {
        if ((int) $employee->business_id !== (int) $business->id) {
            abort(404);
        }

        $routePrefix = str_starts_with((string) $request->route()->getName(), 'myaccount.') ? 'myaccount' : 'business';
        $viewer = auth()->user()->activeEmployee();

        return view('performance.employee', [
            'business' => $business,
            'employee' => $employee,
            'isOwnProfile' => $viewer && (int) $viewer->id === (int) $employee->id,
            'routePrefix' => $routePrefix,
            'departments' => \App\Models\Department::where('business_id', $business->id)->get(['id', 'name']),
            'isHrOrAdmin' => in_array(strtolower((string) session('active_role')), ['business-hr', 'business-admin'], true),
        ]);
    }

    private function canManagePerformanceFor(Employee $target): bool
    {
        $actingEmployee = auth()->user()->activeEmployee();
        if ($actingEmployee && (int) $actingEmployee->id === (int) $target->id) {
            return true;
        }
        if ($actingEmployee && (int) $target->manager_id === (int) $actingEmployee->id) {
            return true;
        }

        $activeRole = strtolower((string) session('active_role'));
        return in_array($activeRole, ['business-hr', 'business-admin'], true);
    }

    public function fetchObjectives(Request $request, Business $business, Employee $employee)
    {
        if (!$this->canManagePerformanceFor($employee)) {
            return RequestResponse::badRequest('You are not authorized to view this employee\'s performance.', 403);
        }

        $cycleId = $request->integer('performance_cycle_id');

        $objectives = PerformanceObjective::where('business_id', $business->id)
            ->where('employee_id', $employee->id)
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId))
            ->with(['keyResults', 'parentObjective:id,title,scope'])
            ->latest()
            ->get();

        return RequestResponse::ok('Objectives fetched successfully.', $objectives);
    }

    public function fetchKpisForEmployee(Business $business, Employee $employee)
    {
        if (!$this->canManagePerformanceFor($employee)) {
            return RequestResponse::badRequest('You are not authorized to view this employee\'s performance.', 403);
        }

        $kpis = \App\Models\Kpi::where('business_id', $business->id)
            ->where('employee_id', $employee->id)
            ->with('results')
            ->get()
            ->map(fn ($kpi) => [
                'id' => $kpi->id,
                'name' => $kpi->name,
                'description' => $kpi->description,
                'target_value' => $kpi->target_value,
                'comparison_operator' => $kpi->comparison_operator,
                'latest_result' => optional($kpi->results->last())->result_value,
                'progress_percentage' => round($kpi->getProgressPercentage(), 1),
            ]);

        return RequestResponse::ok('KPIs fetched successfully.', $kpis);
    }

    public function fetchCascadeObjectives(Request $request, Business $business)
    {
        $validated = $request->validate([
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'scope' => 'nullable|in:company,department',
        ]);

        $objectives = PerformanceObjective::where('business_id', $business->id)
            ->where('performance_cycle_id', $validated['performance_cycle_id'])
            ->where('alignment_status', 'approved')
            ->whereIn('scope', ($validated['scope'] ?? null) ? [$validated['scope']] : ['company', 'department'])
            ->with(['employee.user:id,name', 'department:id,name', 'keyResults'])
            ->orderBy('scope')
            ->get();

        return RequestResponse::ok('Cascade objectives fetched successfully.', $objectives);
    }

    public function storeObjective(Request $request, Business $business, Employee $employee)
    {
        if (!$this->canManagePerformanceFor($employee)) {
            return RequestResponse::badRequest('You are not authorized to set objectives for this employee.', 403);
        }

        $validated = $request->validate([
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'scope' => 'nullable|in:company,department,individual',
            'department_id' => 'nullable|exists:departments,id',
            'parent_objective_id' => 'nullable|exists:performance_objectives,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'weight' => 'required|numeric|min:0.01|max:100',
        ]);

        $cycle = PerformanceCycle::where('business_id', $business->id)->find($validated['performance_cycle_id']);
        if (!$cycle) {
            return RequestResponse::badRequest('Cycle not found for this business.', 404);
        }
        if ($cycle->goalsAreLocked()) {
            return RequestResponse::badRequest('Goals are locked for this cycle - it is closed or its self-review window has opened. Progress can still be updated, but new objectives cannot be added.', 422);
        }

        $scope = $validated['scope'] ?? 'individual';
        $activeRole = strtolower((string) session('active_role'));
        $isHrOrAdmin = in_array($activeRole, ['business-hr', 'business-admin'], true);
        $actingEmployee = auth()->user()->activeEmployee();

        if ($scope !== 'individual' && !$isHrOrAdmin) {
            return RequestResponse::badRequest('Only HR/admin can set company or departmental objectives.', 403);
        }

        if ($scope === 'department' && empty($validated['department_id'])) {
            return RequestResponse::badRequest('A departmental objective must specify department_id.', 422);
        }

        $parentObjective = null;
        if (!empty($validated['parent_objective_id'])) {
            $parentObjective = PerformanceObjective::where('business_id', $business->id)
                ->where('performance_cycle_id', $validated['performance_cycle_id'])
                ->find($validated['parent_objective_id']);

            if (!$parentObjective) {
                return RequestResponse::badRequest('Parent objective not found in this cycle.', 422);
            }

            $parentRank = array_search($parentObjective->scope, PerformanceObjective::SCOPES, true);
            $childRank = array_search($scope, PerformanceObjective::SCOPES, true);
            if ($parentRank === false || $childRank === false || $parentRank >= $childRank) {
                return RequestResponse::badRequest('An objective can only align to a more senior scope (individual -> department/company, department -> company).', 422);
            }
        }

        $isSelfServiceAlignment = $scope === 'individual'
            && $parentObjective
            && $actingEmployee && (int) $actingEmployee->id === (int) $employee->id
            && !$isHrOrAdmin;

        return $this->handleTransaction(function () use ($validated, $business, $employee, $request, $scope, $parentObjective, $isSelfServiceAlignment) {
            $objective = PerformanceObjective::create([
                'business_id' => $business->id,
                'performance_cycle_id' => $validated['performance_cycle_id'],
                'employee_id' => $employee->id,
                'scope' => $scope,
                'department_id' => $validated['department_id'] ?? null,
                'parent_objective_id' => $parentObjective?->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'weight' => $validated['weight'],
                'status' => 'on_track',
                'alignment_status' => $isSelfServiceAlignment ? 'proposed' : 'approved',
                'created_by' => $request->user()->id,
            ]);

            return RequestResponse::created('Objective created successfully.', $objective);
        });
    }

    public function approveAlignment(Request $request, Business $business, PerformanceObjective $objective)
    {
        if ((int) $objective->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Objective not found for this business.', 404);
        }
        if ($objective->alignment_status !== 'proposed') {
            return RequestResponse::badRequest('This objective is not awaiting alignment approval.', 422);
        }

        $parent = $objective->parentObjective;
        if (!$parent) {
            return RequestResponse::badRequest('This objective has no parent objective to align to.', 422);
        }

        $actingEmployee = auth()->user()->activeEmployee();
        $activeRole = strtolower((string) session('active_role'));
        if (!$actingEmployee || !$parent->canApproveAlignment($actingEmployee, $activeRole)) {
            return RequestResponse::badRequest('Only the parent objective\'s owner or HR can approve this alignment.', 403);
        }

        $objective->update(['alignment_status' => 'approved']);

        return RequestResponse::ok('Alignment approved.', $objective->fresh());
    }

    public function declineAlignment(Request $request, Business $business, PerformanceObjective $objective)
    {
        if ((int) $objective->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Objective not found for this business.', 404);
        }
        if ($objective->alignment_status !== 'proposed') {
            return RequestResponse::badRequest('This objective is not awaiting alignment approval.', 422);
        }

        $parent = $objective->parentObjective;
        $actingEmployee = auth()->user()->activeEmployee();
        $activeRole = strtolower((string) session('active_role'));
        if (!$parent || !$actingEmployee || !$parent->canApproveAlignment($actingEmployee, $activeRole)) {
            return RequestResponse::badRequest('Only the parent objective\'s owner or HR can decline this alignment.', 403);
        }

        $objective->update(['alignment_status' => 'draft', 'parent_objective_id' => null]);

        return RequestResponse::ok('Alignment declined.', $objective->fresh());
    }

    public function storeKeyResult(Request $request, Business $business, PerformanceObjective $objective)
    {
        if ((int) $objective->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Objective not found for this business.', 404);
        }
        if (!$this->canManagePerformanceFor($objective->employee)) {
            return RequestResponse::badRequest('You are not authorized to manage this objective.', 403);
        }
        if ($objective->cycle && $objective->cycle->goalsAreLocked()) {
            return RequestResponse::badRequest('Goals are locked for this cycle - it is closed or its self-review window has opened. Progress can still be updated, but new key results cannot be added.', 422);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'target_value' => 'required|numeric|min:0.01',
            'current_value' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'weight' => 'nullable|numeric|min:0|max:100',
        ]);

        $keyResult = PerformanceKeyResult::create([
            'performance_objective_id' => $objective->id,
            'description' => $validated['description'],
            'target_value' => $validated['target_value'],
            'current_value' => $validated['current_value'] ?? 0,
            'unit' => $validated['unit'] ?? null,
            'weight' => $validated['weight'] ?? 100,
        ]);

        return RequestResponse::created('Key result added successfully.', $keyResult);
    }

    public function updateKeyResultProgress(Request $request, Business $business, PerformanceKeyResult $keyResult)
    {
        if ((int) $keyResult->objective->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Key result not found for this business.', 404);
        }
        if (!$this->canManagePerformanceFor($keyResult->objective->employee)) {
            return RequestResponse::badRequest('You are not authorized to update this key result.', 403);
        }

        $validated = $request->validate([
            'current_value' => 'required|numeric|min:0',
        ]);

        $keyResult->update(['current_value' => $validated['current_value']]);
        $keyResult->objective->refreshConfidence();

        return RequestResponse::ok('Progress updated successfully.', $keyResult->fresh());
    }

    public function fetchCriticalObjectives(Request $request, Business $business)
    {
        $actingEmployee = auth()->user()->activeEmployee();
        $activeRole = strtolower((string) session('active_role'));
        $isHrOrAdmin = in_array($activeRole, ['business-hr', 'business-admin'], true);

        $query = PerformanceObjective::where('business_id', $business->id)
            ->where('confidence', 'critical')
            ->with(['employee.user:id,name', 'keyResults']);

        if (!$isHrOrAdmin) {
            if (!$actingEmployee) {
                return RequestResponse::badRequest('No employee record for this business.', 403);
            }
            $teamIds = $actingEmployee->allReports()->pluck('id')->push($actingEmployee->id);
            $query->whereIn('employee_id', $teamIds);
        }

        $cycleId = $request->integer('performance_cycle_id');
        if ($cycleId) {
            $query->where('performance_cycle_id', $cycleId);
        }

        $objectives = $query->latest()->get()
            ->filter(fn (PerformanceObjective $o) => $o->progress < 100)
            ->values()
            ->map(fn (PerformanceObjective $o) => [
                'id' => $o->id,
                'employee_id' => $o->employee_id,
                'employee' => ['user' => ['name' => $o->employee?->user?->name]],
                'title' => $o->title,
                'progress' => $o->progress,
            ]);

        return RequestResponse::ok('Critical objectives fetched successfully.', $objectives);
    }

    public function fetchReview(Business $business, PerformanceCycle $cycle, Employee $employee)
    {
        if ((int) $cycle->business_id !== (int) $business->id || (int) $employee->business_id !== (int) $business->id) {
            abort(404);
        }
        if (!$this->canManagePerformanceFor($employee)) {
            abort(403, 'You are not authorized to view this employee\'s review.');
        }

        $review = PerformanceReview::firstOrCreate(
            ['performance_cycle_id' => $cycle->id, 'employee_id' => $employee->id],
            ['business_id' => $business->id, 'reviewer_id' => $employee->manager_id, 'status' => 'pending_self']
        );

        return RequestResponse::ok('Review fetched successfully.', $review->fresh());
    }

    public function submitSelfAssessment(Request $request, Business $business, PerformanceReview $review)
    {
        if ((int) $review->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Review not found for this business.', 404);
        }

        $employee = auth()->user()->activeEmployee();
        if (!$employee || (int) $employee->id !== (int) $review->employee_id) {
            return RequestResponse::badRequest('Only the reviewed employee can submit their self-assessment.', 403);
        }

        $validated = $request->validate([
            'self_assessment' => 'required|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $review) {
            $review->update([
                'self_assessment' => $validated['self_assessment'],
                'kpi_score' => $review->computeKpiScore(),
                'okr_score' => $review->computeOkrScore(),
                'status' => 'pending_manager',
                'self_submitted_at' => now(),
            ]);

            return RequestResponse::ok('Self-assessment submitted successfully.', $review->fresh());
        });
    }

    public function submitManagerAssessment(Request $request, Business $business, PerformanceReview $review)
    {
        if ((int) $review->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Review not found for this business.', 404);
        }

        $managerEmployee = auth()->user()->activeEmployee();
        $isLineManager = $managerEmployee && (int) $managerEmployee->id === (int) $review->reviewer_id;
        $activeRole = strtolower((string) session('active_role'));
        $isHr = in_array($activeRole, ['business-hr', 'business-admin'], true);

        if (!$isLineManager && !$isHr) {
            return RequestResponse::badRequest('Only the assigned reviewer or HR can submit this assessment.', 403);
        }

        $validated = $request->validate([
            'manager_assessment' => 'required|string',
            'competency_score' => 'required|numeric|min:0|max:100',
        ]);

        return $this->handleTransaction(function () use ($validated, $review) {
            $review->update([
                'manager_assessment' => $validated['manager_assessment'],
                'competency_score' => $validated['competency_score'],
            ]);

            $review->update([
                'overall_score' => $review->computeOverallScore(),
                'status' => 'completed',
                'manager_submitted_at' => now(),
            ]);

            return RequestResponse::ok('Manager assessment submitted successfully.', $review->fresh());
        });
    }
}
