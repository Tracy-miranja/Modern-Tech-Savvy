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
        'entitled_days',
        'accrued_days',
        'total_days',
        'days_taken',
        'days_remaining',
        'last_accrued_at',
        'carryover_days',
    ];

    protected $casts = [
        'entitled_days'   => 'float',
        'accrued_days'    => 'float',
        'total_days'      => 'float',
        'days_taken'      => 'float',
        'days_remaining'  => 'float',
        'last_accrued_at' => 'datetime',
        'carryover_days'  => 'float',
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
     * Recalculate derived fields and persist.
     */
/**
 * Recalculate derived fields and persist.
 * Formula: total_days = entitled_days + accrued_days
 *          days_remaining = total_days - days_taken
 */
    public function calculateRemainingDays(): void
    {
        $entitled = (float)($this->entitled_days ?? 0);
        $accrued  = (float)($this->accrued_days ?? 0);
        $taken    = (float)($this->days_taken ?? 0);
        $carryover = (float)($this->carryover_days ?? 0);

        // Total days includes accrued + carryover (carryover is added to entitled when entitlement is created)
        $this->total_days     = $accrued + $carryover;
        $this->days_remaining = max(0, $this->total_days - $taken);
        $this->save();
    }

    /**
     * Compute and persist remaining days by summing approved usage.
     * This method recalculates days_taken from actual approved requests.
     */
public function getRemainingDays(): float
{
    // Determine the date window using the entitlement's leave period when possible
    if ($this->leavePeriod && $this->leavePeriod->start_date && $this->leavePeriod->end_date) {
        $from = \Carbon\Carbon::parse($this->leavePeriod->start_date)->startOfDay();
        $to   = \Carbon\Carbon::parse($this->leavePeriod->end_date)->endOfDay();
    } else {
        // Fallback: current year window
        $from = now()->copy()->startOfYear();
        $to   = now()->copy()->endOfYear();
    }

    // Sum approved usage that overlaps this window
    $approvedUsed = LeaveRequest::query()
        ->where('employee_id', $this->employee_id)
        ->where('leave_type_id', $this->leave_type_id)
        ->whereNotNull('approved_by')
        ->whereNull('rejection_reason')
        ->whereDate('start_date', '<=', $to)
        ->whereDate('end_date', '>=', $from)
        ->sum('total_days');

    $entitled  = (float)($this->entitled_days ?? 0);
    $accrued   = (float)($this->accrued_days ?? 0);
    $carryover = (float)($this->carryover_days ?? 0);
    $total     = $accrued + $carryover;

    $this->days_taken     = (float)$approvedUsed;
    $this->total_days     = $total;
    $this->days_remaining = max(0, $total - $this->days_taken);

    $this->save();

    return $this->days_remaining;
}


    /**
     * Add days back to entitlement (used when leave request is cancelled/rejected).
     */
    public function addBackDays(float $days): void
    {
        if ($days <= 0) return;

        // Reduce taken, but never below 0
        $this->days_taken = max(0, (float)($this->days_taken ?? 0) - $days);

        // Recompute derived fields
        $entitled = (float)($this->entitled_days ?? 0);
        $carryover = (float)($this->carryover_days ?? 0);
        $accrued  = (float)($this->accrued_days ?? 0);
        $this->total_days     = $accrued + $carryover;
        $this->days_remaining = max(0, $this->total_days - (float)$this->days_taken);

        $this->save();
    }

    /**
     * Recompute days_taken and days_remaining from approved leaves for the entitlement scope.
     * Uses the entitlement's leave period bounds when available.
     */
    public static function recomputeUsageFor(int $employeeId, int $leaveTypeId, int $businessId): void
    {
        /** @var self|null $entitlement */
        $entitlement = self::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('business_id', $businessId)
            ->with('leavePeriod')
            ->orderByDesc('id')
            ->first();

        if (!$entitlement) {
            return;
        }

        if ($entitlement->leavePeriod && $entitlement->leavePeriod->start_date && $entitlement->leavePeriod->end_date) {
            $from = Carbon::parse($entitlement->leavePeriod->start_date)->startOfDay();
            $to   = Carbon::parse($entitlement->leavePeriod->end_date)->endOfDay();
        } else {
            $from = now()->copy()->startOfYear();
            $to   = now()->copy()->endOfYear();
        }

        $used = LeaveRequest::query()
            ->where('business_id', $businessId)
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereNotNull('approved_by')
            ->whereNull('rejection_reason')
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get()
            ->sum(function (LeaveRequest $lr) use ($from, $to) {
                $start = $lr->start_date->copy()->startOfDay()->max($from);
                $end   = $lr->end_date->copy()->startOfDay()->min($to);
                if ($end->lt($start)) return 0;
                $type = $lr->leaveType ?: LeaveType::find($lr->leave_type_id);
                return LeaveRequest::calculateTotalDays($start, $end, (bool)$lr->half_day, $type);
            });

        $entitlement->days_taken = (float) max(0, $used);

        $entitled = (float) ($entitlement->entitled_days ?? 0);
        $accrued  = (float) ($entitlement->accrued_days ?? 0);
        $carryover = (float) ($entitlement->carryover_days ?? 0);
        $entitlement->total_days     = $carryover + $accrued;
        $entitlement->days_remaining = max(0, $entitlement->total_days - $entitlement->days_taken);

        $entitlement->save();
    }

    /**
     * Apply policy-calculated numbers (entitled, carryover, accrued) to entitlement.
     * This is typically called during policy synchronization.
     *
     * @param float $entitled Base entitled days from policy
     * @param float $carryover Carryover days from previous period
     * @param float $accrued Current accrued days
     */
    public function applyPolicyNumbers(float $entitled, float $carryover = 0.0, float $accrued = 0.0): void
    {
        $this->entitled_days = $entitled; // Include carryover in entitled_days
        $this->accrued_days  = $accrued;
        $this->carryover_days = $carryover;
        $this->total_days    = $carryover + $accrued;

        // days_taken stays as-is; recalc remaining:
        $taken = (float) ($this->days_taken ?? 0);
        $this->days_remaining = max(0, $this->total_days - $taken);
    }

}
