<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskCategory extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'sequence_order',
        'color',
        'is_active',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_task_category_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }
}
