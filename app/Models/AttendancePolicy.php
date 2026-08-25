<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendancePolicy extends Model
{
    protected $fillable = [
        'business_id',
        'department_id',
        'job_category_id',
        'employee_id',
        'expected_hours_per_day',
        'is_active',
    ];

    protected $casts = [
        'expected_hours_per_day' => 'float',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeLabel(): string
    {
        if ($this->employee_id) {
            return 'Employee: ' . (optional(optional($this->employee)->user)->name ?? "#{$this->employee_id}");
        }
        if ($this->department_id && $this->job_category_id) {
            return ($this->department->name ?? 'Department') . ' / ' . ($this->jobCategory->name ?? 'Job Category');
        }
        if ($this->department_id) {
            return 'Department: ' . ($this->department->name ?? "#{$this->department_id}");
        }
        if ($this->job_category_id) {
            return 'Job Category: ' . ($this->jobCategory->name ?? "#{$this->job_category_id}");
        }
        return 'Business default';
    }
}
