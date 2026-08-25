<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplinaryStageType extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'sequence_order',
        'is_terminal',
        'requires_response',
        'is_disciplinary_case',
        'approver_role',
        'notify_roles',
        'is_active',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_terminal' => 'boolean',
        'requires_response' => 'boolean',
        'is_disciplinary_case' => 'boolean',
        'notify_roles' => 'array',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function warnings()
    {
        return $this->hasMany(Warning::class, 'stage_type_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }

    public function nextStage(): ?self
    {
        return static::where('business_id', $this->business_id)
            ->where('is_active', true)
            ->where('sequence_order', '>', $this->sequence_order)
            ->orderBy('sequence_order')
            ->first();
    }
}
