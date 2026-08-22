<?php

namespace App\Services;

use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Resolves each employee's Expected Working Hours, using the exact same
 * specificity-scoring approach as LeavePolicyService::resolvePolicy() -
 * employee-specific overrides everything; otherwise the most specific of
 * department+job_category -> department-only -> job_category-only ->
 * business-wide default wins.
 */
class AttendancePolicyService
{
    public function resolveHoursPerDay(Employee $employee): float
    {
        $policies = AttendancePolicy::where('business_id', $employee->business_id)
            ->where('is_active', true)
            ->get();

        if ($policies->isEmpty()) {
            return 8.0; // sane fallback if nothing has been configured yet
        }

        $employeeSpecific = $policies->first(fn (AttendancePolicy $p) => (int) $p->employee_id === (int) $employee->id);
        if ($employeeSpecific) {
            return (float) $employeeSpecific->expected_hours_per_day;
        }

        $matching = $policies->filter(function (AttendancePolicy $p) use ($employee) {
            if ($p->employee_id) {
                return false; // already handled above; an employee-scoped row never matches anyone else
            }
            $deptOk = !$p->department_id || (int) $p->department_id === (int) $employee->department_id;
            $jobOk = !$p->job_category_id || (int) $p->job_category_id === (int) $employee->job_category_id;
            return $deptOk && $jobOk;
        });

        if ($matching->isEmpty()) {
            return 8.0;
        }

        $best = $matching->sortByDesc(function (AttendancePolicy $p) {
            $score = 0;
            if ($p->department_id) $score += 10;
            if ($p->job_category_id) $score += 10;
            return $score;
        })->first();

        return (float) $best->expected_hours_per_day;
    }

    /**
     * The effective expected hours for an employee over a period: working
     * days per their own WorkSchedule (or every calendar day if they have
     * none, i.e. a flexible worker with no fixed week), minus business
     * holidays, times their resolved per-day expectation. Deliberately
     * dynamic rather than a flat monthly number, so a month with an extra
     * public holiday doesn't silently over-target everyone - see GUIDE plan.
     */
    public function resolveExpectedHoursForPeriod(Employee $employee, Carbon $start, Carbon $end): float
    {
        $perDay = $this->resolveHoursPerDay($employee);
        $schedule = WorkSchedule::getActiveSchedule($employee->id, $start, $employee->business_id);

        $workingDays = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $isWorkingDay = $schedule ? $schedule->isWorkingDay($date) : true;
            if (!$isWorkingDay) {
                continue;
            }

            $holiday = Holiday::isHoliday($employee->business_id, $date);
            if ($holiday && !$holiday->is_working_day) {
                continue;
            }

            $workingDays++;
        }

        return round($workingDays * $perDay, 2);
    }
}
