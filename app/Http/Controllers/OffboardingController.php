<?php

namespace App\Http\Controllers;

use App\Http\RequestResponse;
use App\Models\Business;
use App\Models\OffboardingChecklist;
use App\Models\OffboardingTask;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

/**
 * Offboarding - GUIDE plan Phase 4. One page: a "who's currently
 * offboarding" list (active + completed tabs), each row's checklist opened
 * in a modal with inline task checkboxes - modals over pages, matching the
 * rest of this suite, since a handful of tasks is never "too large for a modal".
 */
class OffboardingController extends Controller
{
    use HandleTransactions;

    public function index(Business $business)
    {
        $page = 'Offboarding';

        $checklists = OffboardingChecklist::where('business_id', $business->id)
            ->with(['employee.user', 'employee.department', 'tasks'])
            ->orderByDesc('initiated_at')
            ->get();

        $active = $checklists->where('status', 'in_progress')->values();
        $completed = $checklists->where('status', 'completed')->values();

        return view('offboarding.index', compact('page', 'business', 'active', 'completed'));
    }

    private function ownedChecklist(Business $business, int $checklistId): ?OffboardingChecklist
    {
        return OffboardingChecklist::where('business_id', $business->id)->find($checklistId);
    }

    public function updateTask(Request $request, Business $business, int $checklistId, OffboardingTask $task)
    {
        $checklist = $this->ownedChecklist($business, $checklistId);
        if (!$checklist || (int) $task->checklist_id !== (int) $checklist->id) {
            return RequestResponse::badRequest('Task not found for this checklist.', 404);
        }

        $validated = $request->validate([
            'is_done' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $task, $checklist) {
            $task->update([
                'is_done' => $validated['is_done'],
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $task->notes,
                'completed_by_user_id' => $validated['is_done'] ? auth()->id() : null,
                'completed_at' => $validated['is_done'] ? now() : null,
            ]);

            $checklist->refreshStatus();

            return RequestResponse::ok('Task updated.', $task->fresh());
        });
    }

    public function storeTask(Request $request, Business $business, int $checklistId)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);

        return $this->handleTransaction(function () use ($validated, $business, $checklistId) {
            $checklist = $this->ownedChecklist($business, $checklistId);
            if (!$checklist) {
                return RequestResponse::badRequest('Checklist not found for this business.', 404);
            }

            $nextOrder = (int) $checklist->tasks()->max('sequence_order') + 1;
            $task = $checklist->tasks()->create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'sequence_order' => $nextOrder,
            ]);

            $checklist->refreshStatus();

            return RequestResponse::created('Task added.', $task);
        });
    }

    public function destroyTask(Request $request, Business $business, int $checklistId, OffboardingTask $task)
    {
        $checklist = $this->ownedChecklist($business, $checklistId);
        if (!$checklist || (int) $task->checklist_id !== (int) $checklist->id) {
            return RequestResponse::badRequest('Task not found for this checklist.', 404);
        }

        $task->delete();
        $checklist->refreshStatus();

        return RequestResponse::ok('Task removed.');
    }
}
