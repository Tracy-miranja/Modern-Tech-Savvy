<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceKeyResult extends Model
{
    protected $fillable = [
        'performance_objective_id',
        'description',
        'target_value',
        'current_value',
        'unit',
        'weight',
    ];

    protected $casts = [
        'target_value' => 'float',
        'current_value' => 'float',
        'weight' => 'float',
    ];

    public function objective()
    {
        return $this->belongsTo(PerformanceObjective::class, 'performance_objective_id');
    }

    public function getProgressAttribute(): float
    {
        $target = (float) $this->target_value;
        if ($target <= 0) {
            return 0.0;
        }

        return round(min(100, max(0, ((float) $this->current_value / $target) * 100)), 2);
    }
}
