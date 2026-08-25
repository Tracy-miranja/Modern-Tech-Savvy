<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Http\RequestResponse;
use App\Services\DisciplinaryStageTypeService;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DisciplinaryStageTypeController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $stageTypes = DisciplinaryStageType::where('business_id', $business->id)
            ->withCount('warnings')
            ->ordered()
            ->get();

        return RequestResponse::ok('Stage types fetched.', $stageTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_terminal' => 'nullable|boolean',
            'requires_response' => 'nullable|boolean',
            'is_disciplinary_case' => 'nullable|boolean',
            'approver_role' => 'nullable|string|max:100',
            'notify_roles' => 'nullable|array',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

            $slug = Str::slug($validated['name'], '_');
            if (DisciplinaryStageType::where('business_id', $business->id)->where('slug', $slug)->exists()) {
                return RequestResponse::badRequest('A stage with that name already exists.', [
                    'errors' => ['name' => 'A stage with that name already exists.'],
                ]);
            }

            $nextOrder = (int) DisciplinaryStageType::where('business_id', $business->id)->max('sequence_order') + 1;

            $stageType = DisciplinaryStageType::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'sequence_order' => $nextOrder,
                'is_terminal' => (bool) ($validated['is_terminal'] ?? false),
                'requires_response' => (bool) ($validated['requires_response'] ?? false),
                'is_disciplinary_case' => (bool) ($validated['is_disciplinary_case'] ?? false),
                'approver_role' => $validated['approver_role'] ?? null,
                'notify_roles' => $validated['notify_roles'] ?? null,
            ]);

            return RequestResponse::created('Stage created.', $stageType);
        });
    }

    public function update(Request $request, DisciplinaryStageType $stageType)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || (int) $stageType->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Stage not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'is_terminal' => 'nullable|boolean',
            'requires_response' => 'nullable|boolean',
            'is_disciplinary_case' => 'nullable|boolean',
            'approver_role' => 'nullable|string|max:100',
            'notify_roles' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        return $this->handleTransaction(function () use ($validated, $stageType) {
            $service = app(DisciplinaryStageTypeService::class);

            $removingTerminal = array_key_exists('is_terminal', $validated) && !$validated['is_terminal'];
            $disabling = array_key_exists('is_active', $validated) && !$validated['is_active'];

            if (($removingTerminal || $disabling) && $service->isLastActiveTerminalStage($stageType)) {
                return RequestResponse::badRequest(
                    'This is the only active terminal stage - every business needs at least one, or Termination becomes unreachable.'
                );
            }

            $stageType->update($validated);

            return RequestResponse::ok('Stage updated.', $stageType->fresh());
        });
    }

    public function destroy(Request $request, DisciplinaryStageType $stageType)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || (int) $stageType->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Stage not found for this business.', 404);
        }

        return $this->handleTransaction(function () use ($stageType, $business) {
            if ($stageType->warnings()->exists()) {
                return RequestResponse::badRequest('This stage has cases on record and cannot be deleted - disable it instead.');
            }

            $service = app(DisciplinaryStageTypeService::class);
            if ($service->isLastActiveTerminalStage($stageType)) {
                return RequestResponse::badRequest(
                    'This is the only active terminal stage - every business needs at least one, or Termination becomes unreachable.'
                );
            }

            $stageType->delete();

            $remainingIds = DisciplinaryStageType::where('business_id', $business->id)
                ->ordered()
                ->pluck('id')
                ->all();
            $service->renumber($business, $remainingIds);

            return RequestResponse::ok('Stage deleted.');
        });
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer|exists:disciplinary_stage_types,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $ownedCount = DisciplinaryStageType::where('business_id', $business->id)
                ->whereIn('id', $validated['ordered_ids'])
                ->count();
            if ($ownedCount !== count($validated['ordered_ids'])) {
                return RequestResponse::badRequest('One or more stages do not belong to this business.');
            }

            app(DisciplinaryStageTypeService::class)->renumber($business, $validated['ordered_ids']);

            return RequestResponse::ok('Order saved.');
        });
    }
}
