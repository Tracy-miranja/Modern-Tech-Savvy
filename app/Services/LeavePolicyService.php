<?php

namespace App\Services;

use App\Models\LeavePolicy;
use App\Models\Employee;
use App\Models\LeavePeriod;
use Illuminate\Support\Carbon;

class LeavePolicyService
{
    /**
     * Find the applicable policy for an employee + leave type on a given date.
     * Matching priority:
     *  - exact (department, job category, gender)
     *  - gender=all fallback if needed
     *
     * If multiple match, prefer the most specific (not "all") and latest effective_date.
     */
    public function resolvePolicy(int $leaveTypeId, Employee $employee, Carbon $onDate): ?LeavePolicy
    {
        $query = LeavePolicy::query()
            ->where('leave_type_id', $leaveTypeId)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $onDate)
            ->where(function ($q) use ($onDate) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', $onDate);
            })
            ->where(function ($q) use ($employee) {
                $q->where('department_id', $employee->department_id)
                  ->orWhereNull('department_id');
            })
            ->where(function ($q) use ($employee) {
                $q->where('job_category_id', $employee->job_category_id)
                  ->orWhereNull('job_category_id');
            })
            ->where(function ($q) use ($employee) {
                $gender = strtolower($employee->gender ?? 'all'); // assume: 'male'|'female' or null
                $q->where('gender_applicable', 'all')
                  ->orWhere('gender_applicable', $gender);
            });

        // Prefer most specific + most recent effective_date
        return $query
            ->orderByRaw("CASE WHEN department_id IS NULL THEN 1 ELSE 0 END ASC")
            ->orderByRaw("CASE WHEN job_category_id IS NULL THEN 1 ELSE 0 END ASC")
            ->orderByRaw("CASE WHEN gender_applicable = 'all' THEN 1 ELSE 0 END ASC")
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * Compute the entitled days for the period, enforcing min service days and optional proration.
     */
    public function computeEntitledDays(LeavePolicy $policy, Employee $employee, LeavePeriod $period): float
    {
        $default = (float) $policy->default_days;

        // Minimum service days gate
        $hiredAt = optional($employee->hired_at) ?: optional($employee->created_at);
        if ($policy->minimum_service_days_required && $hiredAt) {
            $daysServed = $hiredAt->diffInDays($period->start_date);
            if ($daysServed < (int) $policy->minimum_service_days_required) {
                return 0.0;
            }
        }

        // Proration for new employees inside the active period
        if ($policy->prorated_for_new_employees && $hiredAt) {
            // If hired after period start, prorate linearly by remaining months
            $start = $period->start_date->copy()->startOfDay();
            $end   = $period->end_date->copy()->endOfDay();

            // If hired within the period, prorate by fraction of period worked
            $effectiveStart = $hiredAt->greaterThan($start) ? $hiredAt : $start;
            $totalDays = max(1, $start->diffInDays($end) + 1); // avoid /0
            $workedDays = max(0, $effectiveStart->diffInDays($end) + 1);
            $prorated = $default * ($workedDays / $totalDays);

            return round($prorated, 2);
        }

        return $default;
    }

    /**
     * Compute carryover allowed into this period.
     * (Here we just clamp by max_carryover_days; customize if your period tracks balances.)
     */
    public function computeCarryover(LeavePolicy $policy, float $previousBalance): float
    {
        $maxCarry = (float) $policy->max_carryover_days;
        return max(0.0, min($previousBalance, $maxCarry));
    }
}
