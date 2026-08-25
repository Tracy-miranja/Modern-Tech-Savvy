<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\KpiResult;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\HandleTransactions;
use Illuminate\Support\Str;
use App\Notifications\KpiAssigned;
use App\Notifications\KpiReviewed;

class KPIsController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Key Performance Indicators';
        $description = 'Manage and create KPIs to track business, department, location, job category, and employee performance.';
        $business = Business::findBySlug(session('active_business_slug'));

        if (!$business) {
            return redirect()->route('business.select')->with('error', 'No active business selected.');
        }

        $kpis = Kpi::where('business_id', $business->id)
            ->with(['results', 'employee.user', 'location', 'business', 'department', 'jobCategory'])
            ->get();

        return view('kpis.index', compact('page', 'description', 'kpis', 'business'));
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active business selected.',
                    'data' => null
                ], 400);
            }

            $kpis = Kpi::where('business_id', $business->id)
                ->with(['results', 'employee.user', 'location', 'business', 'department', 'jobCategory'])
                ->get();

            $businessKpis = $kpis->where('employee_id', null)
                ->where('location_id', null)
                ->where('department_id', null)
                ->where('job_category_id', null);
            $locationKpis = $kpis->where('location_id', '!=', null);
            $departmentKpis = $kpis->where('department_id', '!=', null);
            $jobCategoryKpis = $kpis->where('job_category_id', '!=', null);
            $employeeKpis = $kpis->where('employee_id', '!=', null);

            $businessKpisTable = view('kpis._table', ['kpis' => $businessKpis])->render();
            $locationKpisTable = view('kpis._table', ['kpis' => $locationKpis])->render();
            $departmentKpisTable = view('kpis._table', ['kpis' => $departmentKpis])->render();
            $jobCategoryKpisTable = view('kpis._table', ['kpis' => $jobCategoryKpis])->render();
            $employeeKpisTable = view('kpis._table', ['kpis' => $employeeKpis])->render();

            return response()->json([
                'success' => true,
                'message' => 'KPIs fetched successfully.',
                'data' => [
                    'business_html' => $businessKpisTable,
                    'location_html' => $locationKpisTable,
                    'department_html' => $departmentKpisTable,
                    'job_category_html' => $jobCategoryKpisTable,
                    'employee_html' => $employeeKpisTable,
                    'count' => $kpis->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch KPIs:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KPIs.',
                'data' => ['errors' => [$e->getMessage()]]
            ], 400);
        }
    }

    public function create(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return redirect()->route('business.select')->with('error', 'No active business selected.');
        }

        $employees = Employee::where('business_id', $business->id)->with('user')->get();
        $locations = Location::where('business_id', $business->id)->get();
        $departments = Department::where('business_id', $business->id)->get();
        $jobCategories = JobCategory::where('business_id', $business->id)->get();

        $kpis = Kpi::where('business_id', $business->id)
            ->with(['results', 'employee.user', 'location', 'business', 'department', 'jobCategory'])
            ->get();

        return view('kpis.create', compact('business', 'employees', 'locations', 'departments', 'jobCategories', 'kpis'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'model_type' => 'required|string|in:App\Models\Attendance,App\Models\Application,App\Models\EmployeePayroll,App\Models\Overtime,App\Models\LeaveRequest,App\Models\Task,App\Models\Advance,App\Models\Loan,App\Models\JobPost,manual',
            'description' => 'nullable|string',
            'calculation_method' => 'nullable|string|in:percentage,count,average,sum,ratio',
            'target_value' => 'nullable|numeric',
            'comparison_operator' => 'nullable|string|in:>=,<=,=',
            'business_id' => 'nullable|exists:businesses,id',
            'location_id' => 'nullable|exists:locations,id',
            'employee_id' => 'nullable|exists:employees,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_category_id' => 'nullable|exists:job_categories,id',
        ], [
            'business_id.exists' => 'The selected business is invalid.',
            'location_id.exists' => 'The selected location is invalid.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'department_id.exists' => 'The selected department is invalid.',
            'job_category_id.exists' => 'The selected job category is invalid.',
        ]);

        $assignmentFields = ['business_id', 'location_id', 'employee_id', 'department_id', 'job_category_id'];
        $selectedAssignments = array_filter($assignmentFields, fn($field) => !empty($validatedData[$field]));
        if (count($selectedAssignments) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Exactly one assignment type (business, location, employee, department, or job category) must be selected.',
                'data' => null
            ], 400);
        }

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found.',
                    'data' => null
                ], 400);
            }

            $slugParts = [$business->company_name];
            $assignmentLabel = '';

            if (!empty($validatedData['location_id'])) {
                $location = Location::find($validatedData['location_id']);
                $slugParts[] = $location->name ?? '';
                $assignmentLabel = "Location: {$location->name}";
            }
            if (!empty($validatedData['department_id'])) {
                $department = Department::find($validatedData['department_id']);
                $slugParts[] = $department->name ?? '';
                $assignmentLabel = "Department: {$department->name}";
            }
            if (!empty($validatedData['job_category_id'])) {
                $jobCategory = JobCategory::find($validatedData['job_category_id']);
                $slugParts[] = $jobCategory->name ?? '';
                $assignmentLabel = "Job Category: {$jobCategory->name}";
            }
            if (!empty($validatedData['employee_id'])) {
                $employee = Employee::find($validatedData['employee_id']);
                $slugParts[] = $employee->user->name ?? '';
                $assignmentLabel = "Employee: {$employee->user->name}";
            }
            if (
                empty($validatedData['location_id']) && empty($validatedData['employee_id']) &&
                empty($validatedData['department_id']) && empty($validatedData['job_category_id'])
            ) {
                $assignmentLabel = "Business: {$business->company_name}";
            }

            $slug = Str::slug(implode('-', $slugParts) . '-' . $validatedData['name']);

            $kpi = Kpi::create([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'model_type' => $validatedData['model_type'],
                'description' => $validatedData['description'] ?? null,
                'calculation_method' => $validatedData['calculation_method'] ?? null,
                'target_value' => $validatedData['target_value'] ?? null,
                'comparison_operator' => $validatedData['comparison_operator'] ?? null,
                'business_id' => $validatedData['business_id'] ?? $business->id,
                'location_id' => $validatedData['location_id'] ?? null,
                'employee_id' => $validatedData['employee_id'] ?? null,
                'department_id' => $validatedData['department_id'] ?? null,
                'job_category_id' => $validatedData['job_category_id'] ?? null,
            ]);

            $this->notifyKpiAssignment($kpi, $assignmentLabel);

            return response()->json([
                'success' => true,
                'message' => 'KPI created successfully.',
                'data' => $kpi->id
            ], 201);
        });
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'kpi_id' => 'nullable|exists:kpis,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found.',
                'data' => null
            ], 400);
        }

        $modelTypes = [
            'App\Models\Attendance' => 'Attendance',
            'App\Models\Application' => 'Job Applications',
            'App\Models\EmployeePayroll' => 'Payroll',
            'App\Models\Overtime' => 'Overtime',
            'App\Models\LeaveRequest' => 'Leave Requests',
            'App\Models\Task' => 'Tasks',
            'App\Models\Advance' => 'Advances',
            'App\Models\Loan' => 'Loans',
            'App\Models\JobPost' => 'Job Posts',
            'manual' => 'Manual Indicator',
        ];
        $calculationMethods = ['percentage', 'count', 'average', 'sum', 'ratio'];
        $comparisonOperators = ['>=', '<=', '='];
        $employees = Employee::where('business_id', $business->id)->with('user')->get();
        $locations = Location::where('business_id', $business->id)->get();
        $departments = Department::where('business_id', $business->id)->get();
        $jobCategories = JobCategory::where('business_id', $business->id)->get();
        $kpi = null;

        if (!empty($validatedData['kpi_id'])) {
            $kpi = Kpi::where('id', $validatedData['kpi_id'])
                ->with(['employee.user', 'location', 'business', 'department', 'jobCategory'])
                ->firstOrFail();
        }

        $form = view('kpis._form', compact('kpi', 'modelTypes', 'calculationMethods', 'comparisonOperators', 'employees', 'locations', 'departments', 'jobCategories', 'business'))->render();
        return response()->json([
            'success' => true,
            'message' => 'KPI form loaded successfully.',
            'data' => $form
        ], 200);
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
            'name' => 'required|string|max:255',
            'model_type' => 'required|string|in:App\Models\Attendance,App\Models\Application,App\Models\EmployeePayroll,App\Models\Overtime,App\Models\LeaveRequest,App\Models\Task,App\Models\Advance,App\Models\Loan,App\Models\JobPost,manual',
            'description' => 'nullable|string',
            'calculation_method' => 'nullable|string|in:percentage,count,average,sum,ratio',
            'target_value' => 'nullable|numeric',
            'comparison_operator' => 'nullable|string|in:>=,<=,=',
            'business_id' => 'nullable|exists:businesses,id',
            'location_id' => 'nullable|exists:locations,id',
            'employee_id' => 'nullable|exists:employees,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_category_id' => 'nullable|exists:job_categories,id',
        ]);

        $assignmentFields = ['business_id', 'location_id', 'employee_id', 'department_id', 'job_category_id'];
        $selectedAssignments = array_filter($assignmentFields, fn($field) => !empty($validatedData[$field]));
        if (count($selectedAssignments) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Exactly one assignment type (business, location, employee, department, or job category) must be selected.',
                'data' => null
            ], 400);
        }

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found.',
                    'data' => null
                ], 400);
            }

            $kpi = Kpi::where('id', $validatedData['kpi_id'])->firstOrFail();

            $slugParts = [$business->company_name];
            $assignmentLabel = '';

            if (!empty($validatedData['location_id'])) {
                $location = Location::find($validatedData['location_id']);
                $slugParts[] = $location->name ?? '';
                $assignmentLabel = "Location: {$location->name}";
            }
            if (!empty($validatedData['department_id'])) {
                $department = Department::find($validatedData['department_id']);
                $slugParts[] = $department->name ?? '';
                $assignmentLabel = "Department: {$department->name}";
            }
            if (!empty($validatedData['job_category_id'])) {
                $jobCategory = JobCategory::find($validatedData['job_category_id']);
                $slugParts[] = $jobCategory->name ?? '';
                $assignmentLabel = "Job Category: {$jobCategory->name}";
            }
            if (!empty($validatedData['employee_id'])) {
                $employee = Employee::find($validatedData['employee_id']);
                $slugParts[] = $employee->user->name ?? '';
                $assignmentLabel = "Employee: {$employee->user->name}";
            }
            if (
                empty($validatedData['location_id']) && empty($validatedData['employee_id']) &&
                empty($validatedData['department_id']) && empty($validatedData['job_category_id'])
            ) {
                $assignmentLabel = "Business: {$business->company_name}";
            }

            $slug = Str::slug(implode('-', $slugParts) . '-' . $validatedData['name']);

            $kpi->update([
                'name' => $validatedData['name'],
                'slug' => $slug,
                'model_type' => $validatedData['model_type'],
                'description' => $validatedData['description'] ?? null,
                'calculation_method' => $validatedData['calculation_method'] ?? null,
                'target_value' => $validatedData['target_value'] ?? null,
                'comparison_operator' => $validatedData['comparison_operator'] ?? null,
                'business_id' => $validatedData['business_id'] ?? $business->id,
                'location_id' => $validatedData['location_id'] ?? null,
                'employee_id' => $validatedData['employee_id'] ?? null,
                'department_id' => $validatedData['department_id'] ?? null,
                'job_category_id' => $validatedData['job_category_id'] ?? null,
            ]);

            $this->notifyKpiAssignment($kpi, $assignmentLabel);

            return response()->json([
                'success' => true,
                'message' => 'KPI updated successfully.',
                'data' => null
            ], 200);
        });
    }

    public function destroy(Request $request)
    {
        $validatedData = $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found.',
                    'data' => null
                ], 400);
            }

            $kpi = Kpi::where('id', $validatedData['kpi_id'])->firstOrFail();
            $kpi->delete();

            return response()->json([
                'success' => true,
                'message' => 'KPI deleted successfully.',
                'data' => null
            ], 200);
        });
    }

    public function results(Request $request)
    {
        $validatedData = $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
            'review_id' => 'nullable|exists:kpi_results,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found.',
                'data' => null
            ], 400);
        }

        $kpi = Kpi::where('id', $validatedData['kpi_id'])
            ->with(['results', 'employee.user', 'location', 'business', 'department', 'jobCategory'])
            ->firstOrFail();

        $review = null;
        if (!empty($validatedData['review_id'])) {
            $review = KpiResult::findOrFail($validatedData['review_id']);
        } else {
            $review = $kpi->results()->latest()->first();
        }

        $results_view = view('kpis._results', compact('kpi', 'review'))->render();
        return response()->json([
            'success' => true,
            'message' => 'KPI results fetched successfully.',
            'data' => $results_view
        ], 200);
    }

    public function review(Request $request)
    {
        $validated = $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
            'rating_value' => 'required|numeric|between:-9999999999999.99,9999999999999.99',
            'comment' => 'nullable|string|max:65535',
            'review_id' => 'nullable|exists:kpi_results,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $kpi = Kpi::findOrFail($validated['kpi_id']);
            $result = $validated['review_id']
                ? KpiResult::findOrFail($validated['review_id'])
                : new KpiResult();

            $result->kpi_id = $validated['kpi_id'];
            $result->model_type = $kpi->model_type;
            $result->model_id = $kpi->id;
            $result->result_value = $validated['rating_value'];
            $result->comment = $validated['comment'] ?? null;
            $result->measured_at = today();
            $result->meets_target = $this->checkTargetMet($kpi, $validated['rating_value']) ?? 0;

            $result->save();

            $this->notifyKpiReview($kpi, $result->result_value, $result->meets_target ? 'Met' : 'Not Met', $result->comment);

            return response()->json([
                'success' => true,
                'message' => 'Review saved successfully.',
                'data' => $result->id
            ], 200);
        });
    }

    public function deleteReview(Request $request)
    {
        $validated = $request->validate([
            'review_id' => 'required|exists:kpi_results,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $review = KpiResult::findOrFail($validated['review_id']);
            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully.',
                'data' => null
            ], 200);
        });
    }

    protected function checkTargetMet($kpi, $ratingValue)
    {
        if (!$kpi->target_value || !$kpi->comparison_operator) {
            return null;
        }

        switch ($kpi->comparison_operator) {
            case '>=':
                return $ratingValue >= $kpi->target_value;
            case '<=':
                return $ratingValue <= $kpi->target_value;
            case '=':
                return $ratingValue == $kpi->target_value;
            default:
                return false;
        }
    }

    public function fetchCards(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active business selected.',
                    'data' => null
                ], 400);
            }

            $kpis = Kpi::where('business_id', $business->id)
                ->with(['results', 'employee.user', 'location', 'business', 'department', 'jobCategory'])
                ->get();

            $cardsHtml = view('kpis._kpi_cards', ['kpis' => $kpis])->render();

            return response()->json([
                'success' => true,
                'message' => 'KPI cards fetched successfully.',
                'data' => [
                    'cards_html' => $cardsHtml
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch KPI cards:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KPI cards.',
                'data' => ['errors' => [$e->getMessage()]]
            ], 400);
        }
    }

    protected function notifyKpiAssignment($kpi, $assignmentLabel)
    {
        if ($kpi->employee_id) {
            $employee = Employee::find($kpi->employee_id);
            $employee->user->notify(new KpiAssigned($kpi, $assignmentLabel));
        } elseif ($kpi->department_id) {
            $employees = Employee::where('department_id', $kpi->department_id)->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiAssigned($kpi, $assignmentLabel));
            }
        } elseif ($kpi->job_category_id) {
            $employees = Employee::whereHas('employmentDetails', fn($q) => $q->where('job_category_id', $kpi->job_category_id))->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiAssigned($kpi, $assignmentLabel));
            }
        } elseif ($kpi->location_id) {
            $employees = Employee::where('location_id', $kpi->location_id)->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiAssigned($kpi, $assignmentLabel));
            }
        } elseif ($kpi->business_id) {
            $employees = Employee::where('business_id', $kpi->business_id)->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiAssigned($kpi, $assignmentLabel));
            }
        }
    }

    protected function notifyKpiReview($kpi, $ratingValue, $status, $comment)
    {
        if ($kpi->employee_id) {
            $employee = Employee::find($kpi->employee_id);
            $employee->user->notify(new KpiReviewed($kpi, $ratingValue, $status, $comment));
        } elseif ($kpi->department_id) {
            $employees = Employee::where('department_id', $kpi->department_id)->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiReviewed($kpi, $ratingValue, $status, $comment));
            }
        } elseif ($kpi->job_category_id) {
            $employees = Employee::whereHas('employmentDetails', fn($q) => $q->where('job_category_id', $kpi->job_category_id))->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiReviewed($kpi, $ratingValue, $status, $comment));
            }
        } elseif ($kpi->location_id) {
            $employees = Employee::where('location_id', $kpi->location_id)->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiReviewed($kpi, $ratingValue, $status, $comment));
            }
        } elseif ($kpi->business_id) {
            $employees = Employee::where('business_id', $kpi->business_id)->get();
            foreach ($employees as $employee) {
                $employee->user->notify(new KpiReviewed($kpi, $ratingValue, $status, $comment));
            }
        }
    }
}
