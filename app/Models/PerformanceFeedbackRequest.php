<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceFeedbackRequest extends Model
{
    protected $fillable = [
        'business_id',
        'performance_cycle_id',
        'subject_employee_id',
        'reviewer_employee_id',
        'requested_by',
        'status',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function subject()
    {
        return $this->belongsTo(Employee::class, 'subject_employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function response()
    {
        return $this->hasOne(PerformanceFeedbackResponse::class);
    }
}
