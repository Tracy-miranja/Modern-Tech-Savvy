<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffboardingTask extends Model
{
    protected $fillable = [
        'checklist_id',
        'business_id',
        'task_key',
        'name',
        'sequence_order',
        'is_done',
        'completed_by_user_id',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_done' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function checklist()
    {
        return $this->belongsTo(OffboardingChecklist::class, 'checklist_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
