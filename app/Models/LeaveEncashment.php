<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveEncashment extends Model
{
    protected $fillable = [
        'business_id',
        'employee_id',
        'leave_type_id',
        'leave_period_id',
        'days_requested',
        'daily_rate',
        'amount',
        'status',
        'requested_at',
        'approved_by',
        'rejection_reason',
        'disbursed_at',
        'disbursed_note',
    ];

    protected $casts = [
        'days_requested' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leavePeriod()
    {
        return $this->belongsTo(LeavePeriod::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
