<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskComment extends Model
{
    protected $fillable = [
        'project_task_id',
        'business_id',
        'employee_id',
        'comment',
    ];

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
