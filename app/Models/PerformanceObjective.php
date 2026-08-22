<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceObjective extends Model
{
    /**
     * Ordered tiers of the cascade - a child's scope must be strictly more
     * junior than its parent's (individual can align to department or
     * company; department can align to company; company aligns to nothing).
     */
    public const SCOPES = ['company', 'department', 'individual'];

    /**
     * Stretch-goal grading bands (Google OKR philosophy): a mid-range score
     * on a genuinely hard objective is the goal, not a full house.
     */
    public const GRADE_BANDS = [
        'red' => [0.0, 0.3],
        'amber' => [0.31, 0.6],
        'green' => [0.61, 0.8],
        'blue' => [0.81, 1.0],
    ];

    protected $fillable = [
        'business_id',
        'performance_cycle_id',
        'employee_id',
        'scope',
        'parent_objective_id',
        'department_id',
        'title',
        'description',
        'weight',
        'status',
        'confidence',
        'final_score',
        'alignment_status',
        'created_by',
    ];

    protected $casts = [
        'weight' => 'float',
        'final_score' => 'float',
    ];

    protected $appends = ['grade_band'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function parentObjective()
    {
        return $this->belongsTo(PerformanceObjective::class, 'parent_objective_id');
    }

    public function childObjectives()
    {
        return $this->hasMany(PerformanceObjective::class, 'parent_objective_id');
    }

    public function keyResults()
    {
        return $this->hasMany(PerformanceKeyResult::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Who can approve a proposed alignment to this objective: whoever owns
     * it (the department HOD for a departmental objective, the exec who set
     * a company pillar), or business HR/admin as a fallback. One approval
     * step, matching the department-owner-approves model.
     */
    public function canApproveAlignment(Employee $employee, string $activeRole): bool
    {
        if ((int) $this->employee_id === (int) $employee->id) {
            return true;
        }

        return in_array(strtolower($activeRole), ['business-hr', 'business-admin'], true);
    }

    /**
     * Weighted average progress (0-100) across this objective's key results.
     */
    public function getProgressAttribute(): float
    {
        $keyResults = $this->keyResults;
        $totalWeight = $keyResults->sum('weight');

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weightedProgress = $keyResults->sum(fn (PerformanceKeyResult $kr) => $kr->progress * $kr->weight);

        return round($weightedProgress / $totalWeight, 2);
    }

    /**
     * Auto-flags on_track / at_risk / critical from progress vs. time
     * elapsed in the cycle - a key result can be at 50% and still be fine
     * three months out, but critical with two days left.
     */
    public function computeConfidence(): string
    {
        $cycle = $this->cycle;
        if (!$cycle || !$cycle->start_date || !$cycle->end_date) {
            return 'on_track';
        }

        $totalDays = max(1, $cycle->start_date->diffInDays($cycle->end_date));
        $elapsedDays = min($totalDays, max(0, $cycle->start_date->diffInDays(now())));
        $timeElapsedFraction = $elapsedDays / $totalDays;

        $actualProgress = $this->progress; // 0-100
        $expectedProgress = $timeElapsedFraction * 100;
        $gap = $expectedProgress - $actualProgress;

        // Near the end of the cycle, any meaningful shortfall is critical
        // regardless of the raw gap - there's no runway left to close it.
        if ($timeElapsedFraction >= 0.85 && $actualProgress < 90) {
            return 'critical';
        }

        if ($gap <= 10) {
            return 'on_track';
        }
        if ($gap <= 25) {
            return 'at_risk';
        }
        return 'critical';
    }

    /**
     * Recomputes and persists confidence - called whenever a key result's
     * progress changes.
     */
    public function refreshConfidence(): void
    {
        $confidence = $this->computeConfidence();
        if ($confidence !== $this->confidence) {
            $this->confidence = $confidence;
            $this->save();
        }
    }

    /**
     * 0.0-1.0 stretch-goal score from final progress - computed once, when
     * the cycle closes, so it reflects where the objective actually landed.
     */
    public function computeFinalScore(): float
    {
        return round(min(100, max(0, $this->progress)) / 100, 2);
    }

    public function gradeBand(): ?string
    {
        return self::gradeBandForScore($this->final_score);
    }

    /**
     * Shared band lookup for any 0.0-1.0 stretch-goal score - used by
     * gradeBand() for an objective's own final_score, and reused by
     * PerformanceReportController to grade a review's overall_score
     * (0-100, so divide by 100 first) the same way for display consistency.
     */
    public static function gradeBandForScore(?float $score0to1): ?string
    {
        if ($score0to1 === null) {
            return null;
        }

        foreach (self::GRADE_BANDS as $band => [$min, $max]) {
            if ($score0to1 >= $min && $score0to1 <= $max) {
                return $band;
            }
        }

        return null;
    }

    /**
     * Eloquent accessor so `grade_band` is appended to the JSON payload -
     * the frontend badges key off this without the controller needing to
     * attach it manually.
     */
    public function getGradeBandAttribute(): ?string
    {
        return $this->gradeBand();
    }
}
