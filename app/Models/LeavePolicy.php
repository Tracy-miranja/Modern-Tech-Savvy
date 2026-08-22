<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeavePolicy extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'leave_type_id',
        'department_id',
        'job_category_id',
        'gender_applicable',
        'default_days',
        'accrual_frequency',
        'accrual_amount',
        'max_carryover_days',
        'carryover_type',
        'carryover_value',
        'carryover_expiry_months',
        'prorated_for_new_employees',
        'minimum_service_days_required',
        'min_interval_days',
        'is_encashable',
        'max_encashable_days',
        'effective_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'accrual_amount' => 'decimal:2',
        'max_carryover_days' => 'integer',
        'carryover_value' => 'decimal:2',
        'carryover_expiry_months' => 'integer',
        'prorated_for_new_employees' => 'boolean',
        'minimum_service_days_required' => 'integer',
        'min_interval_days' => 'integer',
        'is_encashable' => 'boolean',
        'max_encashable_days' => 'integer',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function scopeActive($q)
{
    return $q->where('is_active', true);
}

public function scopeEffectiveOn($q, $date)
{
    return $q->whereDate('effective_date', '<=', $date)
             ->where(function ($q) use ($date) {
                 $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
             });
}

}
