<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTimeLog extends Model
{
    protected $fillable = [
        'project_id',
        'project_task_id',
        'business_id',
        'employee_id',
        'date',
        'hours',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
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
