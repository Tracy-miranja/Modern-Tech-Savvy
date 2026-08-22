<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\Business;
use Illuminate\Support\Carbon;

class LeaveEntitlement extends Model
{
    use HasFactory, HasStatuses, LogsActivity;

    protected $fillable = [
        'business_id',
        'employee_id',
        'leave_type_id',
        'leave_period_id',
        'carryover_days',
        'carryover_expiry_date',
        'adjustment_days',
        'adjustment_reason',
        'policy_snapshot',
        'entitled_days',
        'accrued_days',
        'total_days',
        'days_taken',
        'days_pending',
        'days_remaining',
        'last_accrued_at',
    ];

    protected $casts = [
        'carryover_days'  => 'float',
        'carryover_expiry_date' => 'date',
        'adjustment_days' => 'float',
        'policy_snapshot' => 'array',
        'entitled_days'   => 'float',
        'accrued_days'    => 'float',
        'total_days'      => 'float',
        'days_taken'      => 'float',
        'days_pending'    => 'float',
        'days_remaining'  => 'float',
        'last_accrued_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leavePeriod()
    {
        return $this->belongsTo(LeavePeriod::class);
    }

    /**
     * The date window usage/pending sums are scoped to - the entitlement's
     * own leave period when known, otherwise the current calendar year.
     * Centralized so every caller uses the exact same window; previously
     * getRemainingDays() and recomputeUsageFor() each resolved this
     * independently and had drifted slightly out of sync with each other.
     */
    private function resolveWindow(): array
    {
        if ($this->leavePeriod && $this->leavePeriod->start_date && $this->leavePeriod->end_date) {
            return [
                Carbon::parse($this->leavePeriod->start_date)->startOfDay(),
                Carbon::parse($this->leavePeriod->end_date)->endOfDay(),
            ];
        }

        return [now()->copy()->startOfYear(), now()->copy()->endOfYear()];
    }

    /**
     * Sums LeaveRequest days overlapping the window, clamped to the window
     * bounds so a request spanning a period boundary only counts the
     * portion that actually falls inside it.
     */
    private function sumRequestDaysInWindow(Carbon $from, Carbon $to, bool $approvedOnly): float
    {
        $query = LeaveRequest::query()
            ->where('business_id', $this->business_id)
            ->where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->with('employee')
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from);

        if ($approvedOnly) {
            // Approved = decided and not rejected (matches LeaveRequest::getStatusAttribute()).
            $query->whereNotNull('approved_by')->whereNull('rejection_reason');
        } else {
            // Pending = not yet decided either way (matches LeaveRequest::getStatusAttribute()).
            $query->whereNull('approved_by')->whereNull('rejection_reason');
        }

        // A cancelled request (whether it was pending or approved) must not
        // count as usage in either branch, so cancelling actually restores
        // the employee's balance.
        $query->whereNull('cancelled_at');

        return (float) $query->get()->sum(function (LeaveRequest $lr) use ($from, $to) {
            $start = $lr->start_date->copy()->startOfDay()->max($from);
            $end   = $lr->end_date->copy()->startOfDay()->min($to);
            if ($end->lt($start)) return 0;
            $type = $lr->leaveType ?: LeaveType::find($lr->leave_type_id);
            // Without business_id, calculateTotalDays() silently skips BOTH
            // public-holiday and non-working-day exclusion (they're gated
            // on it being non-null) - this was counting every calendar day
            // in the request as a leave day for days_taken/days_pending,
            // double-charging (or worse) the entitlement relative to the
            // holiday/weekend-aware total_days actually stored on the
            // request itself at submission time.
            return LeaveRequest::calculateTotalDays(
                $start,
                $end,
                (bool) $lr->half_day,
                $type,
                $this->business_id,
                $lr->employee?->location_id
            );
        });
    }

    /**
     * THE single source of truth for total_days/days_remaining. Every other
     * method in this class, LeavePolicyService, and
     * LeaveEntitlementController delegates here instead of re-deriving the
     * formula - that duplication is exactly how entitled_days/adjustment_days
     * ended up silently dropped in some code paths and double-counted in
     * others.
     *
     * usable_from_grant is EITHER accrued_days (accruable types - the
     * fraction of entitled_days unlocked so far) OR entitled_days
     * (non-accruable types - the whole grant is available immediately).
     * The two are never summed together: that double-counting (entitled +
     * accrued, when accrued already equals or is a fraction of entitled)
     * was the root cause of balances inflating on every leave approval or
     * manual adjustment.
     *
     * days_pending (sum of not-yet-decided requests) is deducted alongside
     * days_taken so a second request can't be submitted on top of a first
     * one still awaiting approval if together they'd exceed the balance -
     * previously only approved usage was checked, so two simultaneous
     * pending requests could jointly overshoot the entitlement undetected
     * until one of them was approved.
     */
    public function recalculateTotals(): void
    {
        $entitled   = (float) ($this->entitled_days ?? 0);
        $accrued    = (float) ($this->accrued_days ?? 0);
        $carryover  = (float) ($this->carryover_days ?? 0);
        $adjustment = (float) ($this->adjustment_days ?? 0);

        // allowance_accruable defaults to true at the DB level, so it alone
        // isn't a safe signal - plenty of entitlements (manual creates, the
        // legacy edit form, most of the test suite) never go through the
        // accrual pipeline at all and just expect entitled_days to be the
        // whole pool, with accrued_days as a separate additive top-up (0 in
        // practice). last_accrued_at is only ever set by
        // LeavePolicyService::createOrUpdateEntitlement()/processAccruals()
        // (for BOTH accruable and non-accruable types - the latter mirrors
        // accrued_days = entitled_days there), so its presence is what
        // actually marks an entitlement as accrual-managed - only THEN
        // does accrued_days already represent the full usable pool and
        // must be used instead of entitled_days rather than summed with it.
        $isAccrualManaged = $this->last_accrued_at !== null;

        $usableFromGrant = $isAccrualManaged ? $accrued : ($entitled + $accrued);

        [$from, $to] = $this->resolveWindow();
        $this->days_taken   = $this->sumRequestDaysInWindow($from, $to, true);
        $this->days_pending = $this->sumRequestDaysInWindow($from, $to, false);

        $this->total_days     = $usableFromGrant + $carryover + $adjustment;
        $this->days_remaining = max(0.0, $this->total_days - $this->days_taken - $this->days_pending);

        $this->save();
    }

    /**
     * Recalculate derived fields and persist.
     * Formula: see recalculateTotals() - this is now a thin, backward
     * compatible alias.
     */
    public function calculateRemainingDays(): void
    {
        $this->recalculateTotals();
    }

    /**
     * Compute and persist remaining days. Scoped to this entitlement's own
     * leave period, so balances from other periods/years for the same
     * employee+leave type never bleed in.
     */
    public function getRemainingDays(): float
    {
        $this->recalculateTotals();
        return $this->days_remaining;
    }

    /**
     * Recompute days_taken, days_pending and days_remaining from live
     * LeaveRequest data for the entitlement scope. Uses the entitlement's
     * leave period bounds when available.
     */
    public static function recomputeUsageFor(int $employeeId, int $leaveTypeId, int $businessId): void
    {
        /** @var self|null $entitlement */
        $entitlement = self::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('business_id', $businessId)
            ->with('leavePeriod', 'leaveType')
            ->orderByDesc('id')
            ->first();

        if (!$entitlement) {
            return;
        }

        $entitlement->recalculateTotals();
    }

    /**
     * Apply policy-calculated numbers (entitled, carryover, accrued) to
     * entitlement. This is typically called during policy synchronization.
     *
     * @param float $entitled Base entitled days from policy
     * @param float $carryover Carryover days from previous period
     * @param float $accrued Current accrued days
     */
    public function applyPolicyNumbers(float $entitled, float $carryover = 0.0, float $accrued = 0.0): void
    {
        $this->entitled_days  = $entitled;
        $this->accrued_days   = $accrued;
        $this->carryover_days = $carryover;
        // adjustment_days is a separate, manually-applied correction layer -
        // untouched by re-resolving the policy's own numbers.
        $this->recalculateTotals();
    }

    /**
     * Applies an incremental correction (positive to grant extra days,
     * negative to claw back) with a required reason - cumulative across
     * multiple adjustments, unlike the blunt full-overwrite update().
     */
    public function applyAdjustment(float $deltaDays, string $reason): void
    {
        $this->adjustment_days = (float) ($this->adjustment_days ?? 0) + $deltaDays;
        $this->adjustment_reason = $reason;
        $this->recalculateTotals();
    }
}
