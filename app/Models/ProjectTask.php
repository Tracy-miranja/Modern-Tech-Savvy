<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $fillable = [
        'project_id',
        'business_id',
        'project_task_status_id',
        'project_task_category_id',
        'assignee_employee_id',
        'title',
        'description',
        'priority',
        'due_date',
        'estimated_hours',
        'position',
        'completed_at',
        'due_reminder_sent_at',
        'overdue_reminder_sent_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'position' => 'integer',
        'completed_at' => 'datetime',
        'due_reminder_sent_at' => 'datetime',
        'overdue_reminder_sent_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function status()
    {
        return $this->belongsTo(ProjectTaskStatus::class, 'project_task_status_id');
    }

    public function category()
    {
        return $this->belongsTo(ProjectTaskCategory::class, 'project_task_category_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id');
    }

    public function comments()
    {
        return $this->hasMany(ProjectTaskComment::class)->latest();
    }

    public function timeLogs()
    {
        return $this->hasMany(ProjectTimeLog::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && !$this->completed_at && $this->due_date->isPast();
    }
}
