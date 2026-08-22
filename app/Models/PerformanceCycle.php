<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceCycle extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'kpi_weight',
        'okr_weight',
        'competency_weight',
        'status',
        'lock_goals_on_start',
        'self_review_due_date',
        'manager_review_due_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'self_review_due_date' => 'date',
        'manager_review_due_date' => 'date',
        'kpi_weight' => 'float',
        'okr_weight' => 'float',
        'competency_weight' => 'float',
        'lock_goals_on_start' => 'boolean',
    ];

    /**
     * Matches the DB column default so a freshly-created in-memory instance
     * reads correctly without a round-trip refresh() - Eloquent's create()
     * doesn't pull DB-applied defaults back into the model.
     */
    protected $attributes = [
        'lock_goals_on_start' => true,
    ];

    /**
     * Whether new objectives/key results can still be added. Goal-setting
     * stays open through the normal active period - it only locks once the
     * cycle is closed, or once its self-review window has opened (past
     * self_review_due_date), so goals can't be quietly adjusted right as
     * evaluation begins. Progress against existing key results is never
     * blocked by this - only the ability to add new commitments.
     */
    public function goalsAreLocked(): bool
    {
        if (!$this->lock_goals_on_start) {
            return false;
        }

        if ($this->status === 'closed') {
            return true;
        }

        return $this->self_review_due_date && now()->greaterThan($this->self_review_due_date);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function objectives()
    {
        return $this->hasMany(PerformanceObjective::class);
    }

    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Whether the three weights actually add up to 100%, since HR can set
     * them freely per cycle.
     */
    public function weightsAreBalanced(): bool
    {
        $sum = (float) $this->kpi_weight + (float) $this->okr_weight + (float) $this->competency_weight;
        return abs($sum - 100.0) < 0.01;
    }
}
