<?php

namespace App\Services;

use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Models\Warning;

/**
 * Owns the lazy-seed + backfill + guard-rail logic for business-configurable
 * disciplinary stages (see GUIDE plan Phase 3 and the
 * create_disciplinary_stage_types_table migration's docblock).
 */
class DisciplinaryStageTypeService
{
    // is_disciplinary_case: from Final Warning onward, a warning stops
    // being a routine warning and becomes a formal disciplinary case
    // (investigation/minutes/escalation apparatus) - business-configurable
    // per stage on the Configure tab, this is just the stock default.
    private const DEFAULT_STAGES = [
        ['name' => 'Verbal Warning', 'slug' => 'verbal_warning', 'is_terminal' => false, 'requires_response' => false, 'is_disciplinary_case' => false],
        ['name' => 'Written Warning', 'slug' => 'written_warning', 'is_terminal' => false, 'requires_response' => true, 'is_disciplinary_case' => false],
        ['name' => 'Final Warning', 'slug' => 'final_warning', 'is_terminal' => false, 'requires_response' => true, 'is_disciplinary_case' => true],
        ['name' => 'Suspension', 'slug' => 'suspension', 'is_terminal' => false, 'requires_response' => true, 'is_disciplinary_case' => true],
        ['name' => 'Termination', 'slug' => 'termination', 'is_terminal' => true, 'requires_response' => true, 'is_disciplinary_case' => true],
    ];

    /**
     * Ensures $business has at least one stage type configured, seeding the
     * 5 stock stages the first time this is called for it, and backfilling
     * any pre-existing Warning rows whose case_type matches a newly-seeded
     * stage's slug so old cases immediately have a real stage_type_id
     * instead of staying null forever. Idempotent - safe to call from
     * anywhere stage types are needed (Configure, issuing a case,
     * escalation, reports), not just a one-time setup step.
     */
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

    /**
     * Renumbers sequence_order to a contiguous 1..N run in the order given -
     * called after any add/destroy/reorder so gaps never accumulate.
     */
    public function renumber(Business $business, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            DisciplinaryStageType::where('business_id', $business->id)
                ->where('id', $id)
                ->update(['sequence_order' => $index + 1]);
        }
    }

    /**
     * True if $stageType is the ONLY active, terminal stage this business
     * has - i.e. removing its terminal status (or disabling/deleting it)
     * would leave the business with no reachable end stage (no way to ever
     * reach Termination-equivalent through the UI).
     */
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
