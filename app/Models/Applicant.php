<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Spatie\ModelStatus\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Experience;

class Applicant extends Model
{
    use HasFactory, HasStatuses, LogsActivity;

    protected $casts = [
    'professional_qualifications' => 'array',
    'professional_membership' => 'array',
    'pwd_details' => 'array',
    'salary_expectation' => 'decimal:2',
    'age' => 'integer',
];


    protected $fillable = [
        'user_id',
        'idnumber',
        'phone',
        'whatsapp_no',
        'age',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'gender',
        'specialization',
        'academic_level',
        'professional_qualifications',
    'professional_membership',
    'pwd',
        'salary_expectation',
        'linkedin_profile',
        'portfolio_url',
        'summary',
        'current_job_title',
        'current_company',
        'experience_level',
        'job_preferences',
        'source',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }


    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'applicant_skills')
            ->withPivot('skill_level');
    }
}
