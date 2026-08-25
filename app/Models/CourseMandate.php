<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CourseMandate extends Model
{
    public const SCOPE_ORGANIZATION = 'organization';
    public const SCOPE_DEPARTMENT = 'department';
    public const SCOPE_JOB_CATEGORY = 'job_category';

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'business_id',
        'course_id',
        'scope_type',
        'scope_ids',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'scope_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function resolveAffectedEmployees(): Builder
    {
        $query = Employee::where('business_id', $this->business_id);

        if ($this->scope_type === self::SCOPE_DEPARTMENT) {
            $query->whereIn('department_id', (array) ($this->scope_ids ?? []));
        } elseif ($this->scope_type === self::SCOPE_JOB_CATEGORY) {

            $query->whereHas('employmentDetails', function ($q) {
                $q->whereIn('job_category_id', (array) ($this->scope_ids ?? []));
            });
        }

        return $query;
    }

    public function autoEnroll(): int
    {
        if (!$this->is_active) {
            return 0;
        }

        $alreadyEnrolledIds = CourseEnrollment::where('course_id', $this->course_id)
            ->pluck('employee_id');

        $toEnroll = $this->resolveAffectedEmployees()
            ->whereNotIn('id', $alreadyEnrolledIds)
            ->get();

        foreach ($toEnroll as $employee) {
            CourseEnrollment::create([
                'course_id' => $this->course_id,
                'course_mandate_id' => $this->id,
                'business_id' => $this->business_id,
                'employee_id' => $employee->id,
                'status' => 'enrolled',
                'enrolled_at' => now(),
            ]);
        }

        return $toEnroll->count();
    }
}
