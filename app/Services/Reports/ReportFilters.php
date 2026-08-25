<?php

namespace App\Services\Reports;

use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportFilters
{
    public ?Carbon $startDate = null;
    public ?Carbon $endDate = null;

    public array $employeeIds = [];
    public ?int $departmentId = null;
    public ?int $jobCategoryId = null;
    public ?int $leavePeriodId = null;

    public array $leaveTypeIds = [];
    public ?int $courseId = null;
    public ?int $projectId = null;

    public static function fromRequest(Request $request): self
    {
        $filters = new self();

        if ($request->filled('date')) {

            $filters->startDate = Carbon::parse($request->input('date'))->startOfDay();
            $filters->endDate = Carbon::parse($request->input('date'))->endOfDay();
        } else {
            $filters->startDate = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'))->startOfDay()
                : null;
            $filters->endDate = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'))->endOfDay()
                : null;
        }

        $filters->employeeIds = array_map('intval', (array) $request->input('employee_ids', []));
        $filters->departmentId = $request->filled('department_id') ? (int) $request->input('department_id') : null;
        $filters->jobCategoryId = $request->filled('job_category_id') ? (int) $request->input('job_category_id') : null;
        $filters->leavePeriodId = $request->filled('leave_period_id') ? (int) $request->input('leave_period_id') : null;
        $filters->leaveTypeIds = array_map('intval', (array) $request->input('leave_type_ids', []));
        $filters->courseId = $request->filled('course_id') ? (int) $request->input('course_id') : null;
        $filters->projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;

        return $filters;
    }

    public function applyToEmployeeScopedQuery($query, string $employeeRelation = 'employee')
    {
        if (!empty($this->employeeIds)) {
            $query->whereIn($this->employeeColumn($query), $this->employeeIds);
        }

        if ($this->departmentId) {
            $query->whereHas($employeeRelation, function ($q) {
                $q->where('department_id', $this->departmentId);
            });
        }

        if ($this->jobCategoryId) {

            $query->whereHas("{$employeeRelation}.employmentDetails", function ($q) {
                $q->where('job_category_id', $this->jobCategoryId);
            });
        }

        return $query;
    }

    private function employeeColumn($query): string
    {
        return $query->getModel()->getTable() . '.employee_id';
    }

    public function matchingEmployees(Business $business): Collection
    {
        $query = \App\Models\Employee::where('business_id', $business->id);

        if (!empty($this->employeeIds)) {
            $query->whereIn('id', $this->employeeIds);
        }
        if ($this->departmentId) {
            $query->where('department_id', $this->departmentId);
        }
        if ($this->jobCategoryId) {

            $query->whereHas('employmentDetails', function ($q) {
                $q->where('job_category_id', $this->jobCategoryId);
            });
        }

        return $query->with('user:id,name')->get();
    }

    public function periodLabel(): string
    {
        if (!$this->startDate || !$this->endDate) {
            return 'All time';
        }

        if ($this->startDate->isSameDay($this->endDate)) {
            return $this->startDate->format('jS M Y');
        }

        return $this->startDate->format('jS M Y') . ' - ' . $this->endDate->format('jS M Y');
    }
}
