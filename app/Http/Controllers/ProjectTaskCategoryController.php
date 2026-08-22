<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ProjectTaskCategory;
use App\Http\RequestResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Settings: business-configurable task categories/labels. Mirrors
 * CourseCategoryController.
 */
class ProjectTaskCategoryController extends Controller
{
    public function fetch(Request $request, Business $business)
    {
        $categories = ProjectTaskCategory::where('business_id', $business->id)
            ->withCount('tasks')
            ->ordered()
            ->get();

        return RequestResponse::ok('Task categories fetched.', $categories);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'sequence_order' => 'nullable|integer|min:1',
        ]);

        $slug = $this->uniqueSlug($business, $validated['name']);
        $nextOrder = $validated['sequence_order'] ?? ((int) ProjectTaskCategory::where('business_id', $business->id)->max('sequence_order') + 1);

        $category = ProjectTaskCategory::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'sequence_order' => $nextOrder,
            'color' => $validated['color'] ?? '#0d6efd',
        ]);

        return RequestResponse::created('Task category created.', $category);
    }

    public function update(Request $request, Business $business, ProjectTaskCategory $category)
    {
        if ((int) $category->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Category not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'sequence_order' => 'nullable|integer|min:1',
        ]);

        if (!empty($validated['name']) && $validated['name'] !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($business, $validated['name'], $category->id);
        }

        $category->update($validated);

        return RequestResponse::ok('Task category updated.', $category->fresh());
    }

    public function destroy(Request $request, Business $business, ProjectTaskCategory $category)
    {
        if ((int) $category->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Category not found for this business.', 404);
        }

        if ($category->tasks()->exists()) {
            return RequestResponse::badRequest('This category is in use - reassign or deactivate it instead of deleting.');
        }

        $category->delete();

        return RequestResponse::ok('Task category deleted.');
    }

    public function reorder(Request $request, Business $business)
    {
        $validated = $request->validate([
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'integer',
        ]);

        $ownedCount = ProjectTaskCategory::where('business_id', $business->id)->whereIn('id', $validated['ordered_ids'])->count();
        if ($ownedCount !== count($validated['ordered_ids'])) {
            return RequestResponse::badRequest('One or more categories do not belong to this business.');
        }

        foreach ($validated['ordered_ids'] as $index => $id) {
            ProjectTaskCategory::where('business_id', $business->id)
                ->where('id', $id)
                ->update(['sequence_order' => $index + 1]);
        }

        return RequestResponse::ok('Order saved.');
    }

    private function uniqueSlug(Business $business, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (
            ProjectTaskCategory::where('business_id', $business->id)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$suffix);
        }

        return $slug;
    }
}
