<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'department_id',
        'manager_employee_id',
        'status',
        'start_date',
        'end_date',
        'budget',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(ProjectMember::class)->whereNull('left_at');
    }

    public function timeLogs()
    {
        return $this->hasMany(ProjectTimeLog::class);
    }
}
