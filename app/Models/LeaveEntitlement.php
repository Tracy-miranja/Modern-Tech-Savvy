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

            $query->whereNotNull('approved_by')->whereNull('rejection_reason');
        } else {

            $query->whereNull('approved_by')->whereNull('rejection_reason');
        }

        $query->whereNull('cancelled_at');

        return (float) $query->get()->sum(function (LeaveRequest $lr) use ($from, $to) {
            $start = $lr->start_date->copy()->startOfDay()->max($from);
            $end   = $lr->end_date->copy()->startOfDay()->min($to);
            if ($end->lt($start)) return 0;
            $type = $lr->leaveType ?: LeaveType::find($lr->leave_type_id);

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

    public function recalculateTotals(): void
    {
        $entitled   = (float) ($this->entitled_days ?? 0);
        $accrued    = (float) ($this->accrued_days ?? 0);
        $carryover  = (float) ($this->carryover_days ?? 0);
        $adjustment = (float) ($this->adjustment_days ?? 0);

        $isAccrualManaged = $this->last_accrued_at !== null;

        $usableFromGrant = $isAccrualManaged ? $accrued : ($entitled + $accrued);

        [$from, $to] = $this->resolveWindow();
        $this->days_taken   = $this->sumRequestDaysInWindow($from, $to, true);
        $this->days_pending = $this->sumRequestDaysInWindow($from, $to, false);

        $this->total_days     = $usableFromGrant + $carryover + $adjustment;
        $this->days_remaining = max(0.0, $this->total_days - $this->days_taken - $this->days_pending);

        $this->save();
    }

    public function calculateRemainingDays(): void
    {
        $this->recalculateTotals();
    }

    public function getRemainingDays(): float
    {
        $this->recalculateTotals();
        return $this->days_remaining;
    }

    public static function recomputeUsageFor(int $employeeId, int $leaveTypeId, int $businessId): void
    {

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

    public function applyPolicyNumbers(float $entitled, float $carryover = 0.0, float $accrued = 0.0): void
    {
        $this->entitled_days  = $entitled;
        $this->accrued_days   = $accrued;
        $this->carryover_days = $carryover;

        $this->recalculateTotals();
    }

    public function applyAdjustment(float $deltaDays, string $reason): void
    {
        $this->adjustment_days = (float) ($this->adjustment_days ?? 0) + $deltaDays;
        $this->adjustment_reason = $reason;
        $this->recalculateTotals();
    }
}
