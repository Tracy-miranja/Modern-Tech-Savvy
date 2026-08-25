<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ProjectTaskStatus;
use App\Http\RequestResponse;
use App\Services\ProjectTaskStatusService;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectTaskStatusController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request, Business $business, ProjectTaskStatusService $service)
    {
        $service->ensureSeeded($business);

        $statuses = ProjectTaskStatus::where('business_id', $business->id)
            ->withCount('tasks')
            ->ordered()
            ->get();

        return RequestResponse::ok('Statuses fetched.', $statuses);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_done' => 'nullable|boolean',
            'sequence_order' => 'nullable|integer|min:1',
        ]);

        $slug = Str::slug($validated['name']);
        if (ProjectTaskStatus::where('business_id', $business->id)->where('slug', $slug)->exists()) {
            return RequestResponse::badRequest('A status with that name already exists.');
        }

        $nextOrder = $validated['sequence_order'] ?? ((int) ProjectTaskStatus::where('business_id', $business->id)->max('sequence_order') + 1);

        $status = ProjectTaskStatus::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sequence_order' => $nextOrder,
            'color' => $validated['color'] ?? '#6c757d',
            'is_done' => (bool) ($validated['is_done'] ?? false),
        ]);

        return RequestResponse::created('Status created.', $status);
    }

    public function update(Request $request, Business $business, ProjectTaskStatus $status, ProjectTaskStatusService $service)
    {
        if ((int) $status->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Status not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_done' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sequence_order' => 'nullable|integer|min:1',
        ]);

        $removingDone = array_key_exists('is_done', $validated) && !$validated['is_done'];
        $disabling = array_key_exists('is_active', $validated) && !$validated['is_active'];

        if (($removingDone || $disabling) && $service->isLastActiveDoneStatus($status)) {
            return RequestResponse::badRequest('This is the only active "done" column - every business needs at least one, or tasks can never be marked complete.');
        }

        $status->update($validated);

        return RequestResponse::ok('Status updated.', $status->fresh());
    }

    public function destroy(Request $request, Business $business, ProjectTaskStatus $status, ProjectTaskStatusService $service)
    {
        if ((int) $status->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Status not found for this business.', 404);
        }

        if ($status->tasks()->exists()) {
            return RequestResponse::badRequest('This status has tasks on it - move or delete them first, or disable the status instead.');
        }

        if ($service->isLastActiveDoneStatus($status)) {
            return RequestResponse::badRequest('This is the only active "done" column - every business needs at least one, or tasks can never be marked complete.');
        }

        $status->delete();

        $remainingIds = ProjectTaskStatus::where('business_id', $business->id)->ordered()->pluck('id')->all();
        $service->renumber($business, $remainingIds);

        return RequestResponse::ok('Status deleted.');
    }

    public function reorder(Request $request, Business $business, ProjectTaskStatusService $service)
    {
        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer',
        ]);

        $ownedCount = ProjectTaskStatus::where('business_id', $business->id)->whereIn('id', $validated['ordered_ids'])->count();
        if ($ownedCount !== count($validated['ordered_ids'])) {
            return RequestResponse::badRequest('One or more statuses do not belong to this business.');
        }

        $service->renumber($business, $validated['ordered_ids']);

        return RequestResponse::ok('Order saved.');
    }
}
