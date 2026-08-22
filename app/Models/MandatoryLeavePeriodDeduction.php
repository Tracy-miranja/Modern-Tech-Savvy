<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandatoryLeavePeriodDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'mandatory_leave_period_id',
        'employee_id',
        'leave_entitlement_id',
        'days_deducted',
    ];

    protected $casts = [
        'days_deducted' => 'float',
    ];

    public function mandatoryLeavePeriod(): BelongsTo
    {
        return $this->belongsTo(MandatoryLeavePeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveEntitlement(): BelongsTo
    {
        return $this->belongsTo(LeaveEntitlement::class);
    }
}
