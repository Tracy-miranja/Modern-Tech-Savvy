<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'business_id',
        'title',
        'description',
        'course_category_id',
        'provider',
        'duration_hours',
        'status',
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function mandates()
    {
        return $this->hasMany(CourseMandate::class);
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
