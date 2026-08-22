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
            ?? optional($e->employmentDetails)->employment_date
            ?? null;

        return $date ? Carbon::parse($date) : null;
    }

    /** Department to use for policy matching */
    private function empDeptId(Employee $e): ?int
    {
        return $e->department_id ?? optional($e->employmentDetails)->department_id ?? null;
    }

    /** Job category to use for policy matching */
    private function empJobCatId(Employee $e): ?int
    {
        return $e->job_category_id ?? optional($e->employmentDetails)->job_category_id ?? null;
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
        $default = (float) $policy->default_days;

        // Minimum service days gate
        $hiredAt = $employee->employmentDetails?->employment_date ?? $employee->created_at;
        if ($hiredAt && !$hiredAt instanceof Carbon) {
            $hiredAt = Carbon::parse($hiredAt);
        }

        if ($policy->minimum_service_days_required && $hiredAt) {
            // Signed diff: negative when hired AFTER the period start (i.e. not enough service yet).
            $daysServed = $hiredAt->diffInDays($period->start_date, false);
            if ($daysServed < (int) $policy->minimum_service_days_required) {
                return 0.0;
            }
        }

        $defaultDays = (float) $policy->default_days;

        $periodStart = Carbon::parse($period->start_date);
        $periodEnd   = Carbon::parse($period->end_date);

        if ($policy->prorated_for_new_employees && $hiredAt && $hiredAt->between($periodStart, $periodEnd)) {
            $totalPeriodDays = $periodStart->diffInDays($periodEnd) + 1;
            $workedDays      = $hiredAt->diffInDays($periodEnd) + 1;
            $proratedDays    = ($defaultDays / $totalPeriodDays) * $workedDays;

            Log::info("Prorated entitlement for employee {$employee->id}: " . round($proratedDays, 2) .
                " days (employment_date {$hiredAt->toDateString()}, worked {$workedDays}/{$totalPeriodDays} days)");

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

        $unused    = max(0, (float) $previousEntitlement->days_remaining);
        $carryover = $this->applyCarryoverTypeAndCap($policy, $unused);

        Log::info("Carryover for employee {$employee->id}, leave type {$leaveType->id}: {$carryover} days (unused: {$unused}, type: {$policy->carryover_type}, cap: {$policy->max_carryover_days})");

        return $carryover;
    }

    /**
     * The type/value → amount computation, shared by calculateCarryover()
     * (adjacent-period auto carryover) and computeCarryover() (HR-driven
     * carryover between explicitly chosen periods) so the two never drift
     * apart on what "full/fixed/percent" actually means.
     *
     * 'full' reproduces the original min(unused, cap) behavior exactly -
     * the default, so businesses that never touch the new fields see no
     * change. max_carryover_days always stays the hard ceiling on top of
     * whichever type is chosen, not replaced by it.
     */
    private function applyCarryoverTypeAndCap(LeavePolicy $policy, float $unused): float
    {
        $type = $policy->carryover_type ?? 'full';
        $value = (float) ($policy->carryover_value ?? 0);

        $computed = match ($type) {
            'fixed'   => min($value, $unused),
            'percent' => $unused * ($value / 100),
            default   => $unused, // 'full'
        };

        $cap = (float) ($policy->max_carryover_days ?? 0);

        return $cap > 0 ? min($computed, $cap) : max(0.0, $computed);
    }

    /**
     * The date carried-over days stop being usable, or null if the policy
     * has no expiry configured - matches today's "never expires" behavior
     * by default. Computed from the period the carryover is landing IN
     * (not the one it came from), so "3 months into the new period" always
     * means 3 months of the new period, regardless of the previous one's
     * length.
     */
    public function calculateCarryoverExpiryDate(LeavePeriod $currentPeriod, LeavePolicy $policy): ?Carbon
    {
        if (!$policy->carryover_expiry_months) {
            return null;
        }

        return Carbon::parse($currentPeriod->start_date)->addMonths((int) $policy->carryover_expiry_months);
    }

    /**
     * Simple policy-capped carryover for a known previous balance - used by
     * LeaveEntitlementController::processCarryover(), which lets HR carry
     * from an explicitly chosen source period to an explicitly chosen
     * destination period (not necessarily adjacent ones), unlike
     * calculateCarryover() above which always auto-discovers whichever
     * period immediately precedes the given one.
     */
    public function computeCarryover(LeavePolicy $policy, float $previousBalance): float
    {
        return $this->applyCarryoverTypeAndCap($policy, max(0.0, $previousBalance));
    }

    public function calculateAccruedDays(LeaveEntitlement $entitlement, LeavePolicy $policy, Carbon $asOfDate): float
    {
        $period = $entitlement->leavePeriod;
        if (!$period || !(property_exists($period, 'can_accrue') ? $period->can_accrue : true)) {
            return (float)($entitlement->accrued_days ?? 0);
        }

        $leaveType = $entitlement->leaveType;
        $accruableFlag = Schema::hasColumn('leave_types', 'allowance_accruable')
            ? (bool)($leaveType->allowance_accruable ?? false)
            : true;
        // A policy with a real accrual_amount configured is accruable
        // regardless of the separate allowance_accruable toggle - see the
        // matching comment in LeaveEntitlementController::store().
        if (!$leaveType || !($accruableFlag || (float)($policy->accrual_amount ?? 0) > 0)) {
            return (float)($entitlement->accrued_days ?? 0);
        }

        $periodStart = Carbon::parse($period->start_date);
        $periodEnd   = Carbon::parse($period->end_date);

        if ($asOfDate->lt($periodStart)) return 0.0;

        // Anchor as requested
        $anchor = $this->accrualAnchor($entitlement->employee, $period, $policy);
        if ($anchor->gt($periodEnd)) {
            // Joined after the period ends => no accrual
            return 0.0;
        }

        $effectiveDate = $asOfDate->copy()->min($periodEnd);

        // Whole intervals from anchor to effective
        $amount  = (float)($policy->accrual_amount ?? 0);
        $freq    = strtolower($policy->accrual_frequency ?? 'monthly');

        $intervals = match ($freq) {
            'monthly'   => $anchor->diffInMonths($effectiveDate),
            'quarterly' => intdiv($anchor->diffInMonths($effectiveDate), 3),
            'yearly'    => $anchor->diffInYears($effectiveDate),
            default     => 0,
        };

        if ($intervals <= 0 || $amount <= 0) {
            // If anchor is within the same month and you want “partial-month accruals”, implement here.
            return 0.0;
        }

        $targetAccrued = $intervals * $amount;

        // Cap by base entitlement for the period
        $cap = $this->accrualCapForPeriod($policy);
        if ($cap > 0) {
            $targetAccrued = min($targetAccrued, $cap);
        }

        // Return the **total** accrued to date (not delta)
        return round($targetAccrued, 2);
    }

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

    // Deliberately NOT skipped when the policy's default_days is 0 - some
    // leave types (compensatory "off days" for working a public holiday,
    // etc.) have no baseline grant at all and exist purely so HR can apply
    // ad-hoc adjustments (applyAdjustment()) once a day is actually
    // earned. Skipping row creation here would leave nothing for
    // LeaveEntitlementController::adjust() to adjust - the row must exist
    // with a 0 baseline so it's visible in the entitlements table and
    // ready to receive a grant the moment one is earned.
    $carryover = $this->calculateCarryover($employee, $leaveType, $period, $policy);
    $carryoverExpiryDate = $carryover > 0 ? $this->calculateCarryoverExpiryDate($period, $policy) : null;

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
    $entitlement->carryover_expiry_date = $carryoverExpiryDate;

    // See LeaveEntitlementController::store()'s matching comment - a
    // policy with accrual_amount > 0 is treated as accruable even if the
    // separate allowance_accruable toggle was left off.
    $isAccruable = !Schema::hasColumn('leave_types','allowance_accruable')
        ? true
        : ((bool)$leaveType->allowance_accruable || (float)($policy->accrual_amount ?? 0) > 0);

    // accrued_days only carries real meaning for accruable types (the
    // fraction of entitled_days unlocked so far, grown over time by
    // processAccruals()). For non-accruable types we still mirror it to
    // entitled_days purely so the entitlements table's "Accrued" column
    // reads sensibly - LeaveEntitlement::recalculateTotals() below never
    // sums accrued_days and entitled_days together, so this mirroring is
    // cosmetic only and can't double-count.
    if ($isNew) {
        $entitlement->accrued_days     = $isAccruable ? 0.0 : (float)$entitledDays;
        $entitlement->last_accrued_at  = Carbon::parse($period->start_date);
    } elseif (!$isAccruable) {
        $entitlement->accrued_days = (float)$entitledDays;
    }

    // total_days/days_taken/days_pending/days_remaining are all derived by
    // the single canonical formula - see LeaveEntitlement::recalculateTotals().
    $entitlement->recalculateTotals();

    Log::info("Entitlement " . ($isNew ? 'created' : 'updated') . " for employee {$employee->id}, leave type {$leaveType->id}: {$entitledDays} entitled, {$carryover} carryover, {$entitlement->accrued_days} accrued = {$entitlement->total_days} total.");

    return $entitlement;
}


    /**
     * Forfeits unused carryover once its expiry date passes - called daily
     * from leave:run-accruals, same automatic cadence as accruals
     * themselves. Heuristic (deliberately not a full FIFO leave-day
     * ledger): whatever of the entitlement's ORIGINAL carryover_days
     * hasn't been "covered" by approved usage since the period started is
     * considered still-unused carryover and gets clawed back via the same
     * applyAdjustment() mechanism as any other manual HR correction, so
     * every forfeiture leaves a normal, auditable adjustment row with a
     * clear reason - not a silent balance change.
     */
    public function forfeitExpiredCarryover(Carbon $asOfDate = null): int
    {
        $asOfDate = $asOfDate ?? now();
        $forfeitedCount = 0;

        LeaveEntitlement::whereNotNull('carryover_expiry_date')
            ->whereDate('carryover_expiry_date', '<=', $asOfDate)
            ->where('carryover_days', '>', 0)
            ->get()
            ->each(function (LeaveEntitlement $entitlement) use (&$forfeitedCount) {
                $unusedCarryover = max(0.0, (float) $entitlement->carryover_days - (float) $entitlement->days_taken);

                if ($unusedCarryover > 0) {
                    $entitlement->applyAdjustment(-$unusedCarryover, 'Carryover expired');
                    $forfeitedCount++;
                }

                // Cleared regardless of whether anything was actually
                // forfeited, so an already-fully-used carryover allocation
                // doesn't keep getting re-evaluated on every future run.
                $entitlement->update(['carryover_expiry_date' => null]);
            });

        return $forfeitedCount;
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
                    $entitlement->accrued_days    = $targetAccrued;
                    $entitlement->last_accrued_at = $asOfDate;
                    $entitlement->recalculateTotals();
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

    public function buildEntitlementSnapshot(
        Employee $employee,
        LeaveType $leaveType,
        LeavePeriod $period,
        LeavePolicy $policy,
        Carbon $asOf
    ): array {
        // anchor date like we discussed (later of employment date and period start)
        $periodStart = Carbon::parse($period->start_date);
        $periodEnd   = Carbon::parse($period->end_date);
        $employment  = $employee->employment_date
            ?? optional($employee->employmentDetails)->employment_date
            ?? null;

        $eligibilityDate = $periodStart->copy();
        if ($employment) {
            $emp = Carbon::parse($employment);
            if ($emp->gt($eligibilityDate)) {
                $eligibilityDate = $emp->copy();
            }
        }
        if ($eligibilityDate->gt($periodEnd)) {
            // joined after period; nothing to accrue
            return [
                'entitled' => 0.0,
                'carryover'=> 0.0,
                'accrued'  => 0.0,
                'total'    => 0.0,
            ];
        }

        // base for reference
        $entitledDays = (float)($policy->default_days ?? 0);

        // carryover as you already do
        $carryover = $this->calculateCarryover($employee, $leaveType, $period, $policy);

        // should this type accrue? (see LeaveEntitlementController::store()'s
        // matching comment - a real accrual_amount counts too)
        $isAccruable = !Schema::hasColumn('leave_types', 'allowance_accruable')
            ? true
            : ((bool)$leaveType->allowance_accruable || (float)($policy->accrual_amount ?? 0) > 0);

        // yearly = front-load; non-accruable = front-load
        $freq = strtolower((string)($policy->accrual_frequency ?? ''));
        $frontLoad = (!$isAccruable) || ($isAccruable && $freq === 'yearly');

        // prepare a transient entitlement to pass into calculateAccruedDays if needed
        $tmp = new LeaveEntitlement([
            'employee_id'     => $employee->id,
            'leave_type_id'   => $leaveType->id,
            'leave_period_id' => $period->id,
            'accrued_days'    => 0,
            'last_accrued_at' => $period->start_date,
        ]);
        $tmp->setRelation('leaveType', $leaveType);
        $tmp->setRelation('leavePeriod', $period);
        $tmp->setRelation('employee', $employee);

        $accrued = $frontLoad
            ? $entitledDays
            : (float)$this->calculateAccruedDays($tmp, $policy, $asOf);

        // your new invariant
        $total = (float)$carryover + (float)$accrued;

        return [
            'entitled' => $entitledDays,
            'carryover'=> (float)$carryover,
            'accrued'  => (float)$accrued,
            'total'    => (float)$total,
        ];
    }

}
