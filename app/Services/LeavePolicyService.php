<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeavePeriod;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LeavePolicyService
{
    /** Treat null/0 as “ALL” */
    private function isWildcardId($value): bool
    {
        return is_null($value) || (is_numeric($value) && (int)$value === 0);
    }

    /** Normalize gender */
    private function normalizeGender(?string $g): string
    {
        $g = strtolower(trim((string)$g));
        if ($g === '' || $g === 'all' || $g === 'any' || $g === 'both' || $g === 'all genders') return 'all';
        if ($g === 'm') return 'male';
        if ($g === 'f') return 'female';
        return $g;
    }

    /** Pull employment_date from Employee or related EmploymentDetail */
    private function empEmploymentDate(Employee $e): ?Carbon
    {
        $date = $e->employment_date
            ?? $e->employmentDetail->employment_date
            ?? null;

        return $date ? Carbon::parse($date) : null;
    }

    /** Department to use for policy matching */
    private function empDeptId(Employee $e): ?int
    {
        return $e->department_id ?? $e->employmentDetail->department_id ?? null;
    }

    /** Job category to use for policy matching */
    private function empJobCatId(Employee $e): ?int
    {
        return $e->job_category_id ?? $e->employmentDetail->job_category_id ?? null;
    }

    /**
     * Resolve applicable policy for employee + leave type on a date.
     */
    public function resolvePolicy(int $leaveTypeId, Employee $employee, Carbon $onDate): ?LeavePolicy
    {
        $leaveType = LeaveType::find($leaveTypeId);

        if (
            !$leaveType ||
            (Schema::hasColumn('leave_types', 'is_active') && !$leaveType->is_active)
        ) {
            Log::info("Leave type {$leaveTypeId} not found" . (Schema::hasColumn('leave_types','is_active') ? ' or inactive' : ''));
            return null;
        }

        $q = LeavePolicy::query()
            ->where('leave_type_id', $leaveTypeId)
            ->whereDate('effective_date', '<=', $onDate)
            ->where(function ($q) use ($onDate) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $onDate);
            });

        if (Schema::hasColumn('leave_policies', 'is_active')) {
            $q->where('is_active', 1);
        }

        $policies = $q->get();
        if ($policies->isEmpty()) {
            Log::info("No policies found for leave type {$leaveTypeId} on {$onDate->toDateString()}");
            return null;
        }

        $empGender = strtolower((string)($employee->gender ?? ''));
        $empDeptId = $this->empDeptId($employee);
        $empJobId  = $this->empJobCatId($employee);

        // Gender
        $policies = $policies->filter(function ($p) use ($empGender) {
            $pg = $this->normalizeGender($p->gender_applicable ?? null);
            return $pg === 'all' || $pg === $empGender;
        });
        if ($policies->isEmpty()) {
            Log::info("No gender-matching policies for employee {$employee->id}");
            return null;
        }

        // Department
        $policies = $policies->filter(function ($p) use ($empDeptId) {
            return $this->isWildcardId($p->department_id) || (int)$p->department_id === (int)$empDeptId;
        });

        // Job category
        $policies = $policies->filter(function ($p) use ($empJobId) {
            return $this->isWildcardId($p->job_category_id) || (int)$p->job_category_id === (int)$empJobId;
        });

        if ($policies->isEmpty()) {
            Log::info("No matching policies after dept/job filtering for employee {$employee->id}");
            return null;
        }

        // Most specific first
        return $policies->sortByDesc(function ($p) {
            $score = 0;
            if (!$this->isWildcardId($p->department_id)) $score += 10;
            if (!$this->isWildcardId($p->job_category_id)) $score += 10;
            if ($this->normalizeGender($p->gender_applicable ?? null) !== 'all') $score += 5;
            return $score;
        })->first();
    }

    /**
     * Compute entitled days (uses employment_date for service/proration)
     */
    public function computeEntitledDays(LeavePolicy $policy, Employee $employee, LeavePeriod $period): float
    {
        $employmentDate = $this->empEmploymentDate($employee);
        $periodStart = Carbon::parse($period->start_date);
        $periodEnd   = Carbon::parse($period->end_date);

        if ($policy->minimum_service_days_required > 0) {
            if (!$employmentDate) {
                Log::warning("Employee {$employee->id} has no employment_date. Cannot compute entitlement.");
                return 0;
            }
            $serviceDays = $employmentDate->diffInDays(now());
            if ($serviceDays < $policy->minimum_service_days_required) {
                Log::info("Employee {$employee->id} doesn't meet minimum service days ({$serviceDays}/{$policy->minimum_service_days_required})");
                return 0;
            }
        }

        $defaultDays = (float) $policy->default_days;

        if ($policy->prorated_for_new_employees && $employmentDate && $employmentDate->between($periodStart, $periodEnd)) {
            $totalPeriodDays = $periodStart->diffInDays($periodEnd) + 1;
            $workedDays      = $employmentDate->diffInDays($periodEnd) + 1;
            $proratedDays    = ($defaultDays / $totalPeriodDays) * $workedDays;

            Log::info("Prorated entitlement for employee {$employee->id}: " . round($proratedDays, 2) .
                " days (employment_date {$employmentDate->toDateString()}, worked {$workedDays}/{$totalPeriodDays} days)");

            return round($proratedDays, 2);
        }

        return $defaultDays;
    }

    public function calculateCarryover(Employee $employee, LeaveType $leaveType, LeavePeriod $currentPeriod, LeavePolicy $policy): float
    {
        $previousPeriod = LeavePeriod::where('business_id', $currentPeriod->business_id)
            ->where('end_date', '<', $currentPeriod->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$previousPeriod) {
            Log::info("No previous period found for carryover calculation");
            return 0;
        }

        $previousEntitlement = LeaveEntitlement::where([
            'employee_id'    => $employee->id,
            'leave_type_id'  => $leaveType->id,
            'leave_period_id'=> $previousPeriod->id,
        ])->first();

        if (!$previousEntitlement) {
            Log::info("No previous entitlement found for employee {$employee->id}");
            return 0;
        }

        $unused      = max(0, (float)$previousEntitlement->days_remaining);
        $maxCarryover= (float) ($policy->max_carryover_days ?? 0);
        $carryover   = min($unused, $maxCarryover);

        Log::info("Carryover for employee {$employee->id}, leave type {$leaveType->id}: {$carryover} days (unused: {$unused}, max allowed: {$maxCarryover})");

        return $carryover;
    }

public function calculateAccruedDays(LeaveEntitlement $entitlement, LeavePolicy $policy, Carbon $asOfDate): float
{
    $period = $entitlement->leavePeriod;
    if (!$period || !(property_exists($period, 'can_accrue') ? $period->can_accrue : true)) {
        return (float)($entitlement->accrued_days ?? 0);
    }

    $leaveType = $entitlement->leaveType;
    // If the leave type does NOT accrue, keep whatever is in accrued_days (likely full upfront from creation)
    if (!$leaveType || !(Schema::hasColumn('leave_types', 'allowance_accruable') ? $leaveType->allowance_accruable : true)) {
        return (float)($entitlement->accrued_days ?? 0);
    }

    $periodStart = Carbon::parse($period->start_date);
    $periodEnd   = Carbon::parse($period->end_date);
    if ($asOfDate->lt($periodStart)) return 0.0;

    $anchor = $this->accrualAnchor($entitlement->employee, $period, $policy);
    if ($anchor->gt($periodEnd)) return 0.0;

    $effectiveDate = $asOfDate->copy()->min($periodEnd);

    $amount  = (float)($policy->accrual_amount ?? 0);
    $freq    = strtolower($policy->accrual_frequency ?? 'monthly');

    // >>> key change for yearly: credit once at anchor (period start) instead of waiting a whole year
    $intervals = match ($freq) {
        'yearly'    => ($effectiveDate->lt($anchor) ? 0 : 1),
        'monthly'   => $anchor->diffInMonths($effectiveDate),
        'quarterly' => intdiv($anchor->diffInMonths($effectiveDate), 3),
        default     => 0,
    };

    if ($intervals <= 0 || $amount <= 0) {
        return 0.0;
    }

    $targetAccrued = $intervals * $amount;

    // Cap by base entitlement for the period
    $cap = $this->accrualCapForPeriod($policy);
    if ($cap > 0) {
        $targetAccrued = min($targetAccrued, $cap);
    }

    return round($targetAccrued, 2);
}


    /** 
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
        $periodEnd   = Carbon::parse($period->end_date);

        if ($asOfDate->lt($periodStart)) return 0;

        $effectiveDate = $asOfDate->copy()->min($periodEnd);
        $lastAccrued   = $entitlement->last_accrued_at ? Carbon::parse($entitlement->last_accrued_at) : $periodStart->copy();

        if ($lastAccrued->gte($effectiveDate)) return (float)($entitlement->accrued_days ?? 0);

        $accrualAmount = (float) $policy->accrual_amount;
        $frequency     = strtolower($policy->accrual_frequency ?? 'monthly');

        switch ($frequency) {
            case 'monthly':
                $intervalsEarned = $lastAccrued->diffInMonths($effectiveDate);
                break;
            case 'quarterly':
                $intervalsEarned = intdiv($lastAccrued->diffInMonths($effectiveDate), 3);
                break;
            case 'yearly':
                $intervalsEarned = $lastAccrued->diffInYears($effectiveDate);
                break;
            default:
                Log::warning("Unknown accrual frequency: {$frequency}");
                return (float)($entitlement->accrued_days ?? 0);
        }

        if ($intervalsEarned <= 0) return (float)($entitlement->accrued_days ?? 0);

        $newAccrual   = $intervalsEarned * $accrualAmount;
        $totalAccrued = (float)($entitlement->accrued_days ?? 0) + $newAccrual;

        Log::info("Accrual calculated for entitlement {$entitlement->id}: {$intervalsEarned} {$frequency} intervals × {$accrualAmount} = {$newAccrual} days (total: {$totalAccrued})");

        return round($totalAccrued, 2);
    }
         */
    /**
     * Eligibility check (uses employment_date & dept/job from EmploymentDetail if needed)
     */
    public function isEmployeeEligible(Employee $employee, LeaveType $leaveType, Carbon $onDate): bool
    {
        $policy = $this->resolvePolicy($leaveType->id, $employee, $onDate);
        if (!$policy) {
            Log::info("Employee {$employee->id} not eligible: No matching policy for leave type {$leaveType->id}");
            return false;
        }

        $policyGender   = strtolower($policy->gender_applicable ?? 'all');
        $employeeGender = strtolower($employee->gender ?? '');

        if ($policyGender !== 'all' && $policyGender !== $employeeGender) {
            Log::info("Employee {$employee->id} not eligible: Gender mismatch (policy: {$policyGender}, employee: {$employeeGender})");
            return false;
        }

        $empDeptId = $this->empDeptId($employee);
        if ($policy->department_id && (int)$policy->department_id !== (int)$empDeptId) {
            Log::info("Employee {$employee->id} not eligible: Department mismatch");
            return false;
        }

        $empJobId = $this->empJobCatId($employee);
        if ($policy->job_category_id && (int)$policy->job_category_id !== (int)$empJobId) {
            Log::info("Employee {$employee->id} not eligible: Job category mismatch");
            return false;
        }

        if ($policy->minimum_service_days_required > 0) {
            $employmentDate = $this->empEmploymentDate($employee);
            if (!$employmentDate) {
                Log::info("Employee {$employee->id} not eligible: No employment_date");
                return false;
            }
            $serviceDays = $employmentDate->diffInDays($onDate);
            if ($serviceDays < $policy->minimum_service_days_required) {
                Log::info("Employee {$employee->id} not eligible: Insufficient service days ({$serviceDays}/{$policy->minimum_service_days_required})");
                return false;
            }
        }

        return true;
    }

public function createOrUpdateEntitlement(Employee $employee, LeaveType $leaveType, LeavePeriod $period, LeavePolicy $policy): ?LeaveEntitlement
{
    $periodStart = Carbon::parse($period->start_date);

    if (!$this->isEmployeeEligible($employee, $leaveType, $periodStart)) {
        Log::info("Skipping entitlement creation: Employee {$employee->id} not eligible for leave type {$leaveType->id}");
        return null;
    }

    $entitledDays = $this->computeEntitledDays($policy, $employee, $period);
    if ($entitledDays <= 0) {
        Log::info("Skipping entitlement: Employee {$employee->id} entitled to 0 days");
        return null;
    }

    $carryover = $this->calculateCarryover($employee, $leaveType, $period, $policy);

    // Keep policy default days for reference only
    $entitledDays = (float)($policy->default_days ?? 0);

    $entitlement = LeaveEntitlement::firstOrNew([
        'business_id'     => $employee->business_id,
        'employee_id'     => $employee->id,
        'leave_type_id'   => $leaveType->id,
        'leave_period_id' => $period->id,
    ]);

    $isNew = !$entitlement->exists;

    $entitlement->entitled_days   = $entitledDays;
    $entitlement->carryover_days  = $carryover;

    // >>> key change: if NOT accruable, credit full upfront into accrued_days
    $isAccruable = !Schema::hasColumn('leave_types','allowance_accruable')
        ? true
        : (bool)$leaveType->allowance_accruable;

    if ($isNew) {
        $entitlement->accrued_days     = $isAccruable ? 0.0 : (float)$entitledDays;
        $entitlement->last_accrued_at  = Carbon::parse($period->start_date);
    } else {
        // If already exists and type is non-accruable, make sure accrued mirrors entitled
        if (!$isAccruable) {
            $entitlement->accrued_days = (float)$entitledDays;
        }
    }

    // total_days = accrued + carryover
    $entitlement->total_days     = (float)$entitlement->carryover_days + (float)$entitlement->accrued_days;

    // Compute usage inside period and remaining
    $daysTaken = LeaveRequest::where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->whereNotNull('approved_by')
        ->whereNull('rejection_reason')
        ->whereBetween('start_date', [$period->start_date, $period->end_date])
        ->sum('total_days');

    $entitlement->days_taken     = (float)$daysTaken;
    $entitlement->days_remaining = max(0, $entitlement->total_days - $entitlement->days_taken);

    $entitlement->save();

    Log::info("Entitlement " . ($isNew ? 'created' : 'updated') . " for employee {$employee->id}, leave type {$leaveType->id}: {$entitledDays} entitled, {$carryover} carryover, {$entitlement->accrued_days} accrued = {$entitlement->total_days} total.");

    return $entitlement;
}


    public function processAccruals(LeavePeriod $period, Carbon $asOfDate = null): int
    {
        $asOfDate = $asOfDate ?? now();
        $processed = 0;

        $entitlements = LeaveEntitlement::where('leave_period_id', $period->id)
            ->with(['leaveType', 'employee', 'leavePeriod'])
            ->get();

        foreach ($entitlements as $entitlement) {
            try {
                $policy = $this->resolvePolicy($entitlement->leave_type_id, $entitlement->employee, $asOfDate);
                if (!$policy) {
                    Log::info("No policy found for entitlement {$entitlement->id}, skipping accrual");
                    continue;
                }

                $oldAccrued = (float)($entitlement->accrued_days ?? 0);
                $targetAccrued = $this->calculateAccruedDays($entitlement, $policy, $asOfDate);

                if ($targetAccrued !== $oldAccrued) {
                    $entitlement->accrued_days   = $targetAccrued;
                    $entitlement->last_accrued_at= $asOfDate;
                    $entitlement->total_days     = (float)$entitlement->carryover_days + $targetAccrued;
                    $entitlement->days_remaining = max(0, $entitlement->total_days - (float)$entitlement->days_taken);
                    $entitlement->save();
                    $processed++;

                    Log::info("Accrual processed for entitlement {$entitlement->id}: {$oldAccrued} → {$targetAccrued} days");
                }
            } catch (\Exception $e) {
                Log::error("Error processing accrual for entitlement {$entitlement->id}: " . $e->getMessage());
            }
        }

        Log::info("Processed {$processed} accruals for period {$period->id} ({$period->name})");
        return $processed;
    }

    private function accrualAnchor(Employee $e, LeavePeriod $period, LeavePolicy $policy): Carbon
    {
        $employment = $this->empEmploymentDate($e);
        $start = Carbon::parse($period->start_date);

        if ($policy->prorated_for_new_employees && $employment) {
            // accrue from employment date, but never before period start
            return $employment->gt($start) ? $employment->copy() : $start->copy();
        }
        return $start->copy();
    }

    private function accrualCapForPeriod(LeavePolicy $policy): float
    {
        // Cap = the base entitled days for the period
        return (float)($policy->default_days ?? 0);
    }

}
