<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'course_id',
        'course_session_id',
        'course_mandate_id',
        'business_id',
        'employee_id',
        'status',
        'enrolled_at',
        'completed_at',
        'score',
        'certificate_issued',
        'certificate_number',
        'certificate_expiry_date',
        'notes',
        'session_reminder_sent_at',
        'certificate_expiry_reminder_sent_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'decimal:2',
        'certificate_issued' => 'boolean',
        'certificate_expiry_date' => 'date',
        'session_reminder_sent_at' => 'datetime',
        'certificate_expiry_reminder_sent_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function mandate()
    {
        return $this->belongsTo(CourseMandate::class, 'course_mandate_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
