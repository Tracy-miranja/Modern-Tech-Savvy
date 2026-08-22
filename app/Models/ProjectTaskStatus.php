<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A business-configurable Kanban column (To Do, In Progress, Review, Done
 * by default, fully editable/extendable per business) - same shape as
 * DisciplinaryStageType.
 */
class ProjectTaskStatus extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'sequence_order',
        'color',
        'is_done',
        'is_active',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_done' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_task_status_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }
}
