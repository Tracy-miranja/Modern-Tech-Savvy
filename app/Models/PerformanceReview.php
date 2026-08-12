<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = [
        'business_id',
        'performance_cycle_id',
        'employee_id',
        'reviewer_id',
        'kpi_score',
        'okr_score',
        'competency_score',
        'overall_score',
        'self_assessment',
        'manager_assessment',
        'status',
        'self_submitted_at',
        'manager_submitted_at',
    ];

    protected $casts = [
        'kpi_score' => 'float',
        'okr_score' => 'float',
        'competency_score' => 'float',
        'overall_score' => 'float',
        'self_submitted_at' => 'datetime',
        'manager_submitted_at' => 'datetime',
    ];

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

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    /**
     * Average progress (0-100) across the employee's objectives for this
     * cycle, weighted by each objective's weight.
     */
    public function computeOkrScore(): float
    {
        $objectives = PerformanceObjective::where('performance_cycle_id', $this->performance_cycle_id)
            ->where('employee_id', $this->employee_id)
            ->with('keyResults')
            ->get();

        $totalWeight = $objectives->sum('weight');
        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weightedProgress = $objectives->sum(fn (PerformanceObjective $o) => $o->progress * $o->weight);

        return round($weightedProgress / $totalWeight, 2);
    }

    /**
     * Average KPI progress (0-100) across every KPI assigned to this
     * employee, using each KPI's latest recorded result against its target.
     */
    public function computeKpiScore(): float
    {
        $kpis = Kpi::where('employee_id', $this->employee_id)->with('results')->get();

        if ($kpis->isEmpty()) {
            return 0.0;
        }

        return round($kpis->avg(fn (Kpi $kpi) => $kpi->getProgressPercentage()), 2);
    }

    /**
     * Blends kpi/okr/competency scores using the parent cycle's weights.
     */
    public function computeOverallScore(): float
    {
        $cycle = $this->cycle;
        if (!$cycle) {
            return 0.0;
        }

        $kpi = (float) ($this->kpi_score ?? 0);
        $okr = (float) ($this->okr_score ?? 0);
        $competency = (float) ($this->competency_score ?? 0);

        $totalWeight = (float) $cycle->kpi_weight + (float) $cycle->okr_weight + (float) $cycle->competency_weight;
        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weighted = ($kpi * $cycle->kpi_weight) + ($okr * $cycle->okr_weight) + ($competency * $cycle->competency_weight);

        return round($weighted / $totalWeight, 2);
    }
}
