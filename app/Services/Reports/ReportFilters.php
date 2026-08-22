<?php

namespace App\Services\Reports;

use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * One shared filter resolver for every report across every module (see
 * GUIDE-plan: Attendance/Leave/Disciplinary/Performance reports). Every
 * report controller builds its query through this instead of hand-rolling
 * its own filter parsing, so "filter and enforce on export" is structural -
 * a report can't accidentally forget to apply a filter server-side that it
 * only applied client-side.
 */
class ReportFilters
{
    public ?Carbon $startDate = null;
    public ?Carbon $endDate = null;
    /** @var int[] */
    public array $employeeIds = [];
    public ?int $departmentId = null;
    public ?int $jobCategoryId = null;
    public ?int $leavePeriodId = null;
    /** @var int[] */
    public array $leaveTypeIds = [];
    public ?int $courseId = null;
    public ?int $projectId = null;

    public static function fromRequest(Request $request): self
    {
        $filters = new self();

        if ($request->filled('date')) {
            // Single-day reports (e.g. Daily) - start and end are the same day.
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

    /**
     * Applies the resolved employee/department/job-category filters onto a
     * query that joins/relates through an `employee` relation - the
     * dimensions every report in the plan shares regardless of module.
     */
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
            // employees.job_category_id isn't a real column - the job
            // category lives on employment_details (Employee's own
            // job_category_id is a PHP-level accessor falling back to it,
            // see Employee::getJobCategoryIdAttribute()), so this must
            // filter through that relation, not a raw column.
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

    /**
     * Every employee matching department/job-category (independent of
     * whether they have any records in the reported data) - used so a
     * Summary/Monthly report can still show an employee with zero
     * attendance rows as an explicit zero rather than silently omitting
     * them.
     */
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
            // Same reasoning as applyToEmployeeScopedQuery() - job category
            // lives on employment_details, not a real employees column.
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
