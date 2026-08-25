<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warning extends Model
{
    use HasFactory;

    public const STAGES = ['verbal_warning', 'written_warning', 'final_warning', 'suspension', 'termination'];

    protected $fillable = [
        'employee_id',
        'business_id',
        'case_type',
        'stage_type_id',
        'severity',
        'previous_case_id',
        'issue_date',
        'reason',
        'description',
        'attachment',
        'status',
        'issued_by',
        'acknowledged_at',
        'acknowledged_by',
        'resolution_notes',
        'response_due_at',
        'employee_response',
        'employee_responded_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'acknowledged_at' => 'datetime',
        'response_due_at' => 'datetime',
        'employee_responded_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(Employee::class, 'acknowledged_by');
    }

    public function previousCase()
    {
        return $this->belongsTo(Warning::class, 'previous_case_id');
    }

    public function nextCases()
    {
        return $this->hasMany(Warning::class, 'previous_case_id');
    }

    public function stageType()
    {
        return $this->belongsTo(DisciplinaryStageType::class, 'stage_type_id');
    }

    public function scopeDisciplinaryCases($query)
    {
        return $query->whereHas('stageType', fn ($q) => $q->where('is_disciplinary_case', true));
    }

    public function investigations()
    {
        return $this->hasMany(DisciplinaryInvestigation::class);
    }

    public function minutes()
    {
        return $this->hasMany(DisciplinaryMinutes::class);
    }

    public function getEscalationLevelAttribute(): int
    {
        $level = 1;
        $current = $this->previousCase;
        while ($current) {
            $level++;
            $current = $current->previousCase;
        }
        return $level;
    }

    public function suggestedNextStage(): ?string
    {
        if ($this->stage_type_id) {
            return $this->suggestedNextStageType()?->slug;
        }

        $index = array_search($this->case_type, self::STAGES, true);
        if ($index === false || $index >= count(self::STAGES) - 1) {
            return null;
        }
        return self::STAGES[$index + 1];
    }

    public function suggestedNextStageType(): ?DisciplinaryStageType
    {
        if (!$this->stage_type_id) {
            return null;
        }

        $stageType = $this->stageType ?: DisciplinaryStageType::find($this->stage_type_id);

        return $stageType?->nextStage();
    }
}
