<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\LeaveRequest;

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
    ];

    protected $casts = [
        'entitled_days'   => 'float',
        'accrued_days'    => 'float',
        'total_days'      => 'float',
        'days_taken'      => 'float',
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
     * Recalculate derived fields and persist.
     */
    public function calculateRemainingDays(): void
    {
        $entitled = (float)($this->entitled_days ?? 0);
        $accrued  = (float)($this->accrued_days ?? 0);
        $taken    = (float)($this->days_taken ?? 0);

        $this->total_days     = $entitled + $accrued;
        $this->days_remaining = max(0, $this->total_days - $taken);
        $this->save();
    }

    /**
     * Compute and persist remaining days by summing approved usage.
     */
    public function getRemainingDays(): float
    {
        $approvedUsed = LeaveRequest::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->whereNotNull('approved_by')
            ->whereNull('rejection_reason')
            ->sum('total_days');

        $entitled = (float)($this->entitled_days ?? 0);
        $accrued  = (float)($this->accrued_days ?? 0);
        $total    = $entitled + $accrued;

        $this->days_taken     = $approvedUsed;
        $this->total_days     = $total;
        $this->days_remaining = max(0, $total - $approvedUsed);
        $this->save();

        return $this->days_remaining;
    }

    public function applyPolicyNumbers(float $entitled, float $carryover = 0.0, float $accrued = 0.0): void
{
    $this->entitled_days = $entitled;
    $this->accrued_days  = $accrued;
    $this->total_days    = $entitled + $carryover + $accrued;
    // days_taken stays as-is; recalc remaining:
    $taken = (float) ($this->days_taken ?? 0);
    $this->days_remaining = max(0, $this->total_days - $taken);
}


}
