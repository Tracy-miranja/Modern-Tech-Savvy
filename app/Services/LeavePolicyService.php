<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeavePeriod;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LeavePolicyService
{
    /** Helper: treat null/0 as wildcard for foreign keys */
    private function isWildcardId($value): bool
    {
        // Some UIs store "All" as 0; treat both NULL and 0 as wildcard
        return is_null($value) || (is_numeric($value) && (int)$value === 0);
    }

    /** Helper: normalize gender to 'all' | 'male' | 'female' */
    private function normalizeGender(?string $g): string
    {
        $g = strtolower(trim((string)$g));
        if ($g === '' || $g === 'all' || $g === 'any' || $g === 'both' || $g === 'all genders') {
            return 'all';
        }
        // Common variants
        if ($g === 'm') $g = 'male';
        if ($g === 'f') $g = 'female';
        return $g;
    }

    /**
     * Resolve the applicable leave policy for an employee and leave type on a given date.
     * Considers: department, job category, gender, and effective dates.
     */
    public function resolvePolicy(int $leaveTypeId, Employee $employee, Carbon $onDate): ?LeavePolicy
    {
        $leaveType = LeaveType::find($leaveTypeId);

        // Only check is_active on leave_types if that column exists
        if (
            !$leaveType ||
            (Schema::hasColumn('leave_types', 'is_active') && !$leaveType->is_active)
        ) {
            Log::info("Leave type {$leaveTypeId} not found" . (Schema::hasColumn('leave_types','is_active') ? ' or inactive' : ''));
            return null;
        }

        // Base query for policies: effective window only
        $q = LeavePolicy::query()
            ->where('leave_type_id', $leaveTypeId)
            ->whereDate('effective_date', '<=', $onDate)
            ->where(function ($q) use ($onDate) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', $onDate);
            });

        // Apply is_active ONLY if the column exists
        if (Schema::hasColumn('leave_policies', 'is_active')) {
            $q->where('is_active', 1);
        }

        $policies = $q->get();

        if ($policies->isEmpty()) {
            Log::info("No policies found for leave type {$leaveTypeId} on {$onDate->toDateString()}");
            return null;
        }

        // Gender filter (match or 'all')
        $empGender = strtolower((string)($employee->gender ?? ''));
        $policies = $policies->filter(function ($p) use ($empGender) {
            $policyGender = $this->normalizeGender($p->gender_applicable ?? null);
            return $policyGender === 'all' || $policyGender === $empGender;
        });

        if ($policies->isEmpty()) {
            Log::info("No gender-matching policies for employee {$employee->id}");
            return null;
        }

        // Department/job category filters (match or wildcard: null/0)
        $policies = $policies->filter(function ($p) use ($employee) {
            return $this->isWildcardId($p->department_id) || (int)$p->department_id === (int)$employee->department_id;
        });

        $policies = $policies->filter(function ($p) use ($employee) {
            return $this->isWildcardId($p->job_category_id) || (int)$p->job_category_id === (int)$employee->job_category_id;
        });

        if ($policies->isEmpty()) {
            Log::info("No matching policies after dept/job filtering for employee {$employee->id}");
            return null;
        }

        // Choose the most specific one (non-wildcards outrank wildcards)
        return $policies->sortByDesc(function ($p) {
            $score = 0;
            if (!$this->isWildcardId($p->department_id)) $score += 10;
            if (!$this->isWildcardId($p->job_category_id)) $score += 10;
            $pg = $this->normalizeGender($p->gender_applicable ?? null);
            if ($pg !== 'all') $score += 5;
            return $score;
        })->first();
    }

    /**
     * Compute entitled days for an employee based on policy.
     * Handles: proration for new employees, minimum service requirements.
     */
    public function computeEntitledDays(LeavePolicy $policy, Employee $employee, LeavePeriod $period): float
    {
        $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : null;
        $periodStart = Carbon::parse($period->start_date);
        $periodEnd = Carbon::parse($period->end_date);

        // Minimum service requirement
        if ($policy->minimum_service_days_required > 0) {
            if (!$hireDate) {
                Log::warning("Employee {$employee->id} has no hire_date. Cannot compute entitlement.");
                return 0;
            }

            $serviceDays = $hireDate->diffInDays(now());

            if ($serviceDays < $policy->minimum_service_days_required) {
                Log::info("Employee {$employee->id} doesn't meet minimum service days ({$serviceDays}/{$policy->minimum_service_days_required})");
                return 0;
            }
        }

        $defaultDays = (float) $policy->default_days;

        // Proration if hired during this period
        if ($policy->prorated_for_new_employees && $hireDate && $hireDate->between($periodStart, $periodEnd)) {
            $totalPeriodDays = $periodStart->diffInDays($periodEnd) + 1;
            $workedDays = $hireDate->diffInDays($periodEnd) + 1;
            $proratedDays = ($defaultDays / $totalPeriodDays) * $workedDays;

            Log::info("Prorated entitlement for employee {$employee->id}: " .
                round($proratedDays, 2) . " days (hired {$hireDate->toDateString()}, worked {$workedDays}/{$totalPeriodDays} days)");

            return round($proratedDays, 2);
        }

        return $defaultDays;
    }

    /**
     * Calculate carryover from previous period with policy limits.
     */
    public function calculateCarryover(Employee $employee, LeaveType $leaveType, LeavePeriod $currentPeriod, LeavePolicy $policy): float
    {
        // Find previous period
        $previousPeriod = LeavePeriod::where('business_id', $currentPeriod->business_id)
            ->where('end_date', '<', $currentPeriod->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$previousPeriod) {
            Log::info("No previous period found for carryover calculation");
            return 0;
        }

        // Get previous entitlement
        $previousEntitlement = LeaveEntitlement::where([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $previousPeriod->id,
        ])->first();

        if (!$previousEntitlement) {
            Log::info("No previous entitlement found for employee {$employee->id}");
            return 0;
        }

        // Unused days
        $unused = max(0, (float)$previousEntitlement->days_remaining);

        // Policy limit
        $maxCarryover = (float) ($policy->max_carryover_days ?? 0);
        $carryover = min($unused, $maxCarryover);

        Log::info("Carryover for employee {$employee->id}, leave type {$leaveType->id}: " .
            "{$carryover} days (unused: {$unused}, max allowed: {$maxCarryover})");

        return $carryover;
    }

    /**
     * Calculate accrued days based on policy frequency and time elapsed.
     */
    public function calculateAccruedDays(LeaveEntitlement $entitlement, LeavePolicy $policy, Carbon $asOfDate): float
    {
        $period = $entitlement->leavePeriod;
        if (!$period || !(property_exists($period, 'can_accrue') ? $period->can_accrue : true)) {
            return (float)($entitlement->accrued_days ?? 0);
        }

        $leaveType = $entitlement->leaveType;
        if (!$leaveType || !(Schema::hasColumn('leave_types', 'allowance_accruable') ? $leaveType->allowance_accruable : true)) {
            return (float)($entitlement->accrued_days ?? 0);
        }

        $periodStart = Carbon::parse($period->start_date);
        $periodEnd = Carbon::parse($period->end_date);

        // Don't accrue if period hasn't started
        if ($asOfDate->lt($periodStart)) {
            return 0;
        }

        // Don't accrue beyond period end
        $effectiveDate = $asOfDate->copy()->min($periodEnd);

        $lastAccrued = $entitlement->last_accrued_at
            ? Carbon::parse($entitlement->last_accrued_at)
            : $periodStart->copy();

        // Already up to date?
        if ($lastAccrued->gte($effectiveDate)) {
            return (float)($entitlement->accrued_days ?? 0);
        }

        $accrualAmount = (float) $policy->accrual_amount;
        $frequency = strtolower($policy->accrual_frequency ?? 'monthly');

        $intervalsEarned = 0;

        switch ($frequency) {
            case 'monthly':
                $intervalsEarned = $lastAccrued->diffInMonths($effectiveDate);
                break;

            case 'quarterly':
                $monthsDiff = $lastAccrued->diffInMonths($effectiveDate);
                $intervalsEarned = intdiv($monthsDiff, 3);
                break;

            case 'yearly':
                $intervalsEarned = $lastAccrued->diffInYears($effectiveDate);
                break;

            default:
                Log::warning("Unknown accrual frequency: {$frequency}");
                return (float)($entitlement->accrued_days ?? 0);
        }

        if ($intervalsEarned <= 0) {
            return (float)($entitlement->accrued_days ?? 0);
        }

        $newAccrual = $intervalsEarned * $accrualAmount;
        $totalAccrued = (float)($entitlement->accrued_days ?? 0) + $newAccrual;

        Log::info("Accrual calculated for entitlement {$entitlement->id}: " .
            "{$intervalsEarned} {$frequency} intervals × {$accrualAmount} = {$newAccrual} days " .
            "(total: {$totalAccrued})");

        return round($totalAccrued, 2);
    }

    /**
     * Check if an employee is eligible for a leave type based on ALL policy criteria.
     */
    public function isEmployeeEligible(Employee $employee, LeaveType $leaveType, Carbon $onDate): bool
    {
        $policy = $this->resolvePolicy($leaveType->id, $employee, $onDate);

        if (!$policy) {
            Log::info("Employee {$employee->id} not eligible: No matching policy for leave type {$leaveType->id}");
            return false;
        }

        // Gender check
        $policyGender = strtolower($policy->gender_applicable ?? 'all');
        $employeeGender = strtolower($employee->gender ?? '');

        if ($policyGender !== 'all' && $policyGender !== $employeeGender) {
            Log::info("Employee {$employee->id} not eligible: Gender mismatch (policy: {$policyGender}, employee: {$employeeGender})");
            return false;
        }

        // Department check (if policy specifies a department)
        if ($policy->department_id && $policy->department_id !== $employee->department_id) {
            Log::info("Employee {$employee->id} not eligible: Department mismatch");
            return false;
        }

        // Job category check (if policy specifies a category)
        if ($policy->job_category_id && $policy->job_category_id !== $employee->job_category_id) {
            Log::info("Employee {$employee->id} not eligible: Job category mismatch");
            return false;
        }

        // Minimum service requirement check
        if ($policy->minimum_service_days_required > 0) {
            $hireDate = $employee->hire_date ? Carbon::parse($employee->hire_date) : null;
            if (!$hireDate) {
                Log::info("Employee {$employee->id} not eligible: No hire date");
                return false;
            }

            $serviceDays = $hireDate->diffInDays($onDate);
            if ($serviceDays < $policy->minimum_service_days_required) {
                Log::info("Employee {$employee->id} not eligible: Insufficient service days ({$serviceDays}/{$policy->minimum_service_days_required})");
                return false;
            }
        }

        return true;
    }

    /**
     * Create or update entitlement with full policy enforcement.
     */
    public function createOrUpdateEntitlement(
        Employee $employee,
        LeaveType $leaveType,
        LeavePeriod $period,
        LeavePolicy $policy
    ): ?LeaveEntitlement {
        $periodStart = Carbon::parse($period->start_date);

        // Check eligibility based on ALL policy criteria
        if (!$this->isEmployeeEligible($employee, $leaveType, $periodStart)) {
            Log::info("Skipping entitlement creation: Employee {$employee->id} not eligible for leave type {$leaveType->id}");
            return null;
        }

        // Calculate entitled days (with proration if applicable)
        $entitledDays = $this->computeEntitledDays($policy, $employee, $period);

        if ($entitledDays <= 0) {
            Log::info("Skipping entitlement: Employee {$employee->id} entitled to 0 days");
            return null;
        }

        // Calculate carryover from previous period
        $carryover = $this->calculateCarryover($employee, $leaveType, $period, $policy);

        // Find or create entitlement
        $entitlement = LeaveEntitlement::firstOrNew([
            'business_id' => $employee->business_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
        ]);

        $isNew = !$entitlement->exists;

        // Set base values
        $entitlement->entitled_days = $entitledDays;

        // Preserve existing accrued days or start at 0
        if ($isNew) {
            $entitlement->accrued_days = 0;
            $entitlement->last_accrued_at = $period->start_date;
        }

        // Calculate total days: entitled + carryover + accrued
        $entitlement->total_days = $entitledDays + $carryover + (float)($entitlement->accrued_days ?? 0);

        // Calculate days taken from approved requests within this period
        $daysTaken = LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereNotNull('approved_by')
            ->whereNull('rejection_reason')
            ->whereBetween('start_date', [$period->start_date, $period->end_date])
            ->sum('total_days');

        $entitlement->days_taken = (float)$daysTaken;
        $entitlement->days_remaining = max(0, $entitlement->total_days - $entitlement->days_taken);

        $entitlement->save();

        Log::info("Entitlement " . ($isNew ? 'created' : 'updated') . " for employee {$employee->id}, " .
            "leave type {$leaveType->id}: {$entitledDays} entitled, {$carryover} carryover, " .
            "{$entitlement->accrued_days} accrued = {$entitlement->total_days} total, " .
            "{$entitlement->days_taken} taken, {$entitlement->days_remaining} remaining");

        return $entitlement;
    }

    /**
     * Process accruals for all entitlements in a period.
     */
    public function processAccruals(LeavePeriod $period, Carbon $asOfDate = null): int
    {
        $asOfDate = $asOfDate ?? now();
        $processed = 0;

        $entitlements = LeaveEntitlement::where('leave_period_id', $period->id)
            ->with(['leaveType', 'employee', 'leavePeriod'])
            ->get();

        foreach ($entitlements as $entitlement) {
            try {
                $policy = $this->resolvePolicy(
                    $entitlement->leave_type_id,
                    $entitlement->employee,
                    $asOfDate
                );

                if (!$policy) {
                    Log::info("No policy found for entitlement {$entitlement->id}, skipping accrual");
                    continue;
                }

                $oldAccrued = (float)($entitlement->accrued_days ?? 0);
                $newAccrued = $this->calculateAccruedDays($entitlement, $policy, $asOfDate);

                if ($newAccrued != $oldAccrued) {
                    $entitlement->accrued_days = $newAccrued;
                    $entitlement->last_accrued_at = $asOfDate;

                    // Recalculate totals
                    $entitlement->total_days = (float)$entitlement->entitled_days + $newAccrued;
                    $entitlement->days_remaining = max(0, $entitlement->total_days - (float)$entitlement->days_taken);

                    $entitlement->save();
                    $processed++;

                    Log::info("Accrual processed for entitlement {$entitlement->id}: {$oldAccrued} → {$newAccrued} days");
                }
            } catch (\Exception $e) {
                Log::error("Error processing accrual for entitlement {$entitlement->id}: " . $e->getMessage());
            }
        }

        Log::info("Processed {$processed} accruals for period {$period->id} ({$period->name})");

        return $processed;
    }
}
