<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ProjectTaskStatus;

/**
 * Owns the lazy-seed + guard-rail logic for business-configurable Kanban
 * columns - same shape as DisciplinaryStageTypeService.
 */
class ProjectTaskStatusService
{
    private const DEFAULT_STATUSES = [
        ['name' => 'To Do', 'slug' => 'to-do', 'color' => '#6c757d', 'is_done' => false],
        ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#0d6efd', 'is_done' => false],
        ['name' => 'Review', 'slug' => 'review', 'color' => '#fd7e14', 'is_done' => false],
        ['name' => 'Done', 'slug' => 'done', 'color' => '#198754', 'is_done' => true],
    ];

    /**
     * Ensures $business has at least one status configured, seeding the 4
     * stock columns the first time this is called for it. Idempotent - safe
     * to call from anywhere a status list is needed (Settings, the board,
     * task create/update), not just a one-time setup step.
     */
    public function ensureSeeded(Business $business): void
    {
        if (ProjectTaskStatus::where('business_id', $business->id)->exists()) {
            return;
        }

        foreach (self::DEFAULT_STATUSES as $i => $status) {
            ProjectTaskStatus::create([
                'business_id' => $business->id,
                'name' => $status['name'],
                'slug' => $status['slug'],
                'sequence_order' => $i + 1,
                'color' => $status['color'],
                'is_done' => $status['is_done'],
            ]);
        }
    }

    public function renumber(Business $business, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ProjectTaskStatus::where('business_id', $business->id)
                ->where('id', $id)
                ->update(['sequence_order' => $index + 1]);
        }
    }

    /**
     * True if $status is the ONLY active "done" column this business has -
     * removing that flag (or disabling/deleting the column) would leave no
     * way to ever mark a task complete through the board.
     */
    public function isLastActiveDoneStatus(ProjectTaskStatus $status): bool
    {
        if (!$status->is_done || !$status->is_active) {
            return false;
        }

        $otherActiveDoneCount = ProjectTaskStatus::where('business_id', $status->business_id)
            ->where('id', '!=', $status->id)
            ->where('is_done', true)
            ->where('is_active', true)
            ->count();

        return $otherActiveDoneCount === 0;
    }
}
