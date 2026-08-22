<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Models\Employee;
use App\Models\Warning;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Services\DisciplinaryStageTypeService;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeWarningIssued;
use App\Mail\EmployeeWarningResolved;

class WarningController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Employee Warnings';
        $description = 'Manage employee warnings for disciplinary purposes. Issue, review, or resolve warnings as needed.';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $employees = $business->employees;
        $locations = $business->locations;
        $departments = \App\Models\Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = \App\Models\JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $warnings = Warning::where('business_id', $business->id)
            ->with('employee.user', 'issuedBy')
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('employees.warning.index', compact('page', 'description', 'employees', 'locations', 'departments', 'jobCategories', 'warnings'));
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $query = Warning::where('business_id', $business->id)
                ->with('employee.user', 'issuedBy', 'stageType');

            // "Cases" is a filtered view of the same warnings table, not a
            // separate entity - only stages a business has flagged
            // is_disciplinary_case count (see disciplinary_stage_types).
            if ($request->input('scope') === 'cases') {
                $query->disciplinaryCases();
            }

            $perPage = min((int) $request->input('per_page', 12), 100) ?: 12;
            $warnings = $query->orderBy('issue_date', 'desc')->paginate($perPage);

            $partial = $request->input('view') === 'list' ? 'employees.warning._rows' : 'employees.warning._cards';
            $warningsTable = view($partial, ['warnings' => $warnings])->render();

            return RequestResponse::ok('Warnings fetched successfully.', [
                'html' => $warningsTable,
                'count' => $warnings->total(),
                'current_page' => $warnings->currentPage(),
                'last_page' => $warnings->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch warnings:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch warnings.', [
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'stage_type_id' => 'nullable|integer|exists:disciplinary_stage_types,id',
            'case_type' => 'nullable|in:' . implode(',', Warning::STAGES),
            'severity' => 'nullable|in:low,medium,high',
            'previous_case_id' => 'nullable|exists:warnings,id',
            'issue_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $request) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            if (!empty($validatedData['previous_case_id'])) {
                $previousCase = Warning::where('business_id', $business->id)
                    ->where('id', $validatedData['previous_case_id'])
                    ->where('employee_id', $validatedData['employee_id'])
                    ->first();
                if (!$previousCase) {
                    return RequestResponse::badRequest('Validation failed.', [
                        'errors' => ['previous_case_id' => 'The prior case must belong to the same employee.']
                    ]);
                }
            }

            // stage_type_id is the authoritative, business-configurable
            // stage (Phase 3) - case_type stays populated too (existing
            // mailables/enum-driven code still reads it) by mirroring the
            // stage's slug when it matches one of the 5 stock stages, or
            // falling back to its own default otherwise (a custom stage
            // like "Coaching Session" has no equivalent enum value).
            $stageType = null;
            if (!empty($validatedData['stage_type_id'])) {
                $stageType = DisciplinaryStageType::where('business_id', $business->id)->find($validatedData['stage_type_id']);
                if (!$stageType) {
                    return RequestResponse::badRequest('Stage not found for this business.');
                }
            }

            $caseType = $validatedData['case_type']
                ?? ($stageType && in_array($stageType->slug, Warning::STAGES, true) ? $stageType->slug : 'written_warning');

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('disciplinary', 'public');
            }

            $warning = Warning::create([
                'employee_id' => $validatedData['employee_id'],
                'business_id' => $business->id,
                'case_type' => $caseType,
                'stage_type_id' => $stageType?->id,
                'severity' => $validatedData['severity'] ?? 'medium',
                'previous_case_id' => $validatedData['previous_case_id'] ?? null,
                'issue_date' => $validatedData['issue_date'],
                'reason' => $validatedData['reason'],
                'description' => $validatedData['description'] ?? null,
                'attachment' => $attachmentPath,
                'status' => 'active',
                'issued_by' => auth()->user()->id,
                'response_due_at' => ($stageType && $stageType->requires_response) ? now()->addDays(7) : null,
            ]);

            // Send email notification to the employee
            $employee = $warning->employee;
            if ($employee && $employee->user && $employee->user->email) {
                Mail::to($employee->user->email)->send(new EmployeeWarningIssued($warning));
            }

            return RequestResponse::created('Disciplinary case recorded successfully.', $warning->id);
        });
    }

    /**
     * Case detail page - the one part of Disciplinary that genuinely
     * warrants a full page rather than a modal (a full escalation timeline
     * + investigations + minutes has too much content) - every action on
     * it (issue show cause, log investigation, record minutes, escalate)
     * is still a modal.
     */
    public function show(Request $request, Business $business, Warning $warning)
    {
        if ((int) $warning->business_id !== (int) $business->id) {
            abort(404);
        }

        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $warning->load([
            'employee.user', 'employee.department', 'stageType', 'issuedBy',
            'previousCase.stageType', 'nextCases.stageType',
            'investigations.investigator.user', 'minutes',
        ]);

        $stageTypes = DisciplinaryStageType::where('business_id', $business->id)->where('is_active', true)->ordered()->get();
        $employees = Employee::where('business_id', $business->id)->with('user')->get(['id', 'user_id']);

        $page = 'Disciplinary Case';

        return view('employees.warning.show', compact('page', 'business', 'warning', 'stageTypes', 'employees'));
    }

    /**
     * Creates the next case in the chain (previous_case_id = this one),
     * using this business's CONFIGURED stage order (Warning::suggestedNextStageType())
     * rather than the old hardcoded ladder. The prior case is marked
     * resolved-by-escalation, not left dangling as still "active".
     */
    public function escalate(Request $request, $id)
    {
        $validated = $request->validate([
            'issue_date' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ]);

        return $this->handleTransaction(function () use ($validated, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warning = Warning::where('business_id', $business->id)->find($id);
            if (!$warning) {
                return RequestResponse::badRequest('Disciplinary case not found.', 404);
            }

            $nextStageType = $warning->suggestedNextStageType();
            $nextSlug = $warning->suggestedNextStage();

            if (!$nextSlug) {
                return RequestResponse::badRequest('This case is already at the final stage - nothing further to escalate to.');
            }

            // case_type is an ENUM restricted to the 5 stock values - a
            // custom stage's slug (e.g. "coaching_session") can't be stored
            // there directly, so it falls back to a default enum value
            // (mirrors store()'s same fallback). $nextSlug on its own is
            // NOT a safe fallback here: when stage_type_id is set,
            // suggestedNextStage() just returns the same custom slug too.
            $caseType = match (true) {
                $nextStageType && in_array($nextStageType->slug, Warning::STAGES, true) => $nextStageType->slug,
                $nextStageType !== null => 'written_warning',
                default => $nextSlug,
            };

            $escalated = Warning::create([
                'employee_id' => $warning->employee_id,
                'business_id' => $business->id,
                'case_type' => $caseType,
                'stage_type_id' => $nextStageType?->id,
                'severity' => $warning->severity,
                'previous_case_id' => $warning->id,
                'issue_date' => $validated['issue_date'] ?? now()->toDateString(),
                'reason' => $validated['reason'] ?? $warning->reason,
                'status' => 'active',
                'issued_by' => auth()->user()->id,
                'response_due_at' => ($nextStageType && $nextStageType->requires_response) ? now()->addDays(7) : null,
            ]);

            $warning->update([
                'status' => 'resolved',
                'resolution_notes' => trim(($warning->resolution_notes ? $warning->resolution_notes . ' ' : '') . "Escalated to case #{$escalated->id}."),
            ]);

            $employee = $escalated->employee;
            if ($employee && $employee->user && $employee->user->email) {
                Mail::to($employee->user->email)->send(new EmployeeWarningIssued($escalated));
            }

            return RequestResponse::created('Case escalated.', $escalated->load('stageType'));
        });
    }

    /**
     * Employee's Show Cause response - generalized onto ANY stage
     * configured with requires_response=true (not one hardcoded case
     * type), scoped to the caller's own employee record exactly like acknowledge().
     */
    public function submitResponse(Request $request, $id)
    {
        $validated = $request->validate([
            'employee_response' => 'required|string|max:5000',
        ]);

        $employee = auth()->user()->activeEmployee();
        if (!$employee) {
            return RequestResponse::badRequest('No employee record for the current user.');
        }

        return $this->handleTransaction(function () use ($validated, $id, $employee) {
            $warning = Warning::where('id', $id)->where('employee_id', $employee->id)->first();
            if (!$warning) {
                return RequestResponse::badRequest('Disciplinary case not found for this employee.', 404);
            }

            $warning->update([
                'employee_response' => $validated['employee_response'],
                'employee_responded_at' => now(),
            ]);

            return RequestResponse::ok('Response submitted.');
        });
    }

    /**
     * "My Disciplinary Cases" - the employee portal has never had a browse
     * view for an employee's own warnings (only the acknowledge action) -
     * this is that view, forced to the caller's own employee record only.
     */
    public function myIndex(Request $request, Business $business)
    {
        $page = 'My Disciplinary Cases';
        $employee = $request->user()->activeEmployee();

        $warnings = $employee
            ? Warning::where('business_id', $business->id)
                ->where('employee_id', $employee->id)
                ->with(['issuedBy:id,name', 'stageType'])
                ->orderByDesc('issue_date')
                ->get()
            : collect();

        return view('employees.warning.my-index', compact('page', 'business', 'warnings'));
    }

    /**
     * Employee acknowledges receipt of a disciplinary case.
     */
    public function acknowledge(Request $request, $id)
    {
        $employee = auth()->user()->activeEmployee();
        if (!$employee) {
            return RequestResponse::badRequest('No employee record for the current user.');
        }

        return $this->handleTransaction(function () use ($id, $employee) {
            $warning = Warning::where('id', $id)
                ->where('employee_id', $employee->id)
                ->first();

            if (!$warning) {
                return RequestResponse::badRequest('Disciplinary case not found for this employee.', 404);
            }

            if (!$warning->acknowledged_at) {
                $warning->update([
                    'acknowledged_at' => now(),
                    'acknowledged_by' => $employee->id,
                ]);
            }

            return RequestResponse::ok('Acknowledged.');
        });
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'warning_id' => 'nullable|exists:warnings,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $employees = $business->employees;
        $warning = null;

        if (!empty($validatedData['warning_id'])) {
            $warning = Warning::where('business_id', $business->id)
                ->where('id', $validatedData['warning_id'])
                ->firstOrFail();
        }

        $form = view('employees.warning._form', compact('warning', 'employees'))->render();
        return RequestResponse::ok('Warning form loaded successfully.', $form);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'warning_id' => 'required|exists:warnings,id',
            'employee_id' => 'required|exists:employees,id',
            'case_type' => 'nullable|in:' . implode(',', Warning::STAGES),
            'severity' => 'nullable|in:low,medium,high',
            'issue_date' => 'required|date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,resolved',
            'resolution_notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warning = Warning::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($warning->id != $validatedData['warning_id']) {
                return RequestResponse::badRequest('Warning ID mismatch.');
            }

            $previousStatus = $warning->status; // Store previous status
            $warning->update([
                'employee_id' => $validatedData['employee_id'],
                'case_type' => $validatedData['case_type'] ?? $warning->case_type,
                'severity' => $validatedData['severity'] ?? $warning->severity,
                'issue_date' => $validatedData['issue_date'],
                'reason' => $validatedData['reason'],
                'description' => $validatedData['description'] ?? null,
                'status' => $validatedData['status'],
                'resolution_notes' => $validatedData['resolution_notes'] ?? $warning->resolution_notes,
                'issued_by' => auth()->user()->id,
            ]);

            // Check if status changed to 'resolved' and send email
            if ($validatedData['status'] === 'resolved' && $previousStatus !== 'resolved') {
                $employee = $warning->employee;
                if ($employee && $employee->user && $employee->user->email) {
                    Mail::to($employee->user->email)->send(new EmployeeWarningResolved($warning));
                }
            }

            return RequestResponse::ok('Warning updated successfully.');
        });
    }

    public function destroy(Request $request, $id)
    {
        $validatedData = $request->validate([
            'warning_id' => 'required|exists:warnings,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warning = Warning::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($warning->id != $validatedData['warning_id']) {
                return RequestResponse::badRequest('Warning ID mismatch.');
            }

            $warning->delete();

            return RequestResponse::ok('Warning deleted successfully.');
        });
    }
}