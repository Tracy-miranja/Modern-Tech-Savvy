<?php

namespace App\Services;

use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Models\Warning;

class DisciplinaryStageTypeService
{

    private const DEFAULT_STAGES = [
        ['name' => 'Verbal Warning', 'slug' => 'verbal_warning', 'is_terminal' => false, 'requires_response' => false, 'is_disciplinary_case' => false],
        ['name' => 'Written Warning', 'slug' => 'written_warning', 'is_terminal' => false, 'requires_response' => true, 'is_disciplinary_case' => false],
        ['name' => 'Final Warning', 'slug' => 'final_warning', 'is_terminal' => false, 'requires_response' => true, 'is_disciplinary_case' => true],
        ['name' => 'Suspension', 'slug' => 'suspension', 'is_terminal' => false, 'requires_response' => true, 'is_disciplinary_case' => true],
        ['name' => 'Termination', 'slug' => 'termination', 'is_terminal' => true, 'requires_response' => true, 'is_disciplinary_case' => true],
    ];

    public function ensureSeeded(Business $business): void
    {
        if (DisciplinaryStageType::where('business_id', $business->id)->exists()) {
            return;
        }

        $created = [];
        foreach (self::DEFAULT_STAGES as $i => $stage) {
            $created[$stage['slug']] = DisciplinaryStageType::create([
                'business_id' => $business->id,
                'name' => $stage['name'],
                'slug' => $stage['slug'],
                'sequence_order' => $i + 1,
                'is_terminal' => $stage['is_terminal'],
                'requires_response' => $stage['requires_response'],
                'is_disciplinary_case' => $stage['is_disciplinary_case'],
            ]);
        }

        Warning::where('business_id', $business->id)
            ->whereNull('stage_type_id')
            ->get()
            ->each(function (Warning $warning) use ($created) {
                if (isset($created[$warning->case_type])) {
                    $warning->update(['stage_type_id' => $created[$warning->case_type]->id]);
                }
            });
    }

    public function renumber(Business $business, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            DisciplinaryStageType::where('business_id', $business->id)
                ->where('id', $id)
                ->update(['sequence_order' => $index + 1]);
        }
    }

    public function isLastActiveTerminalStage(DisciplinaryStageType $stageType): bool
    {
        if (!$stageType->is_terminal || !$stageType->is_active) {
            return false;
        }

        $otherActiveTerminalCount = DisciplinaryStageType::where('business_id', $stageType->business_id)
            ->where('id', '!=', $stageType->id)
            ->where('is_terminal', true)
            ->where('is_active', true)
            ->count();

        return $otherActiveTerminalCount === 0;
    }
}
