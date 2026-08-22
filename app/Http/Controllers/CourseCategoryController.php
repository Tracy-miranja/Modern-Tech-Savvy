<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\CourseCategory;
use App\Http\RequestResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Learning Management Settings - business-configurable course categories,
 * replacing the module's original free-text category field.
 */
class CourseCategoryController extends Controller
{
    public function fetch(Request $request, Business $business)
    {
        $categories = CourseCategory::where('business_id', $business->id)
            ->withCount('courses')
            ->orderBy('name')
            ->get();

        return RequestResponse::ok('Course categories fetched.', $categories);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $slug = $this->uniqueSlug($business, $validated['name']);

        $category = CourseCategory::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return RequestResponse::created('Course category created.', $category);
    }

    public function update(Request $request, Business $business, CourseCategory $category)
    {
        if ((int) $category->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Category not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['name']) && $validated['name'] !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($business, $validated['name'], $category->id);
        }

        $category->update($validated);

        return RequestResponse::ok('Course category updated.', $category->fresh());
    }

    public function destroy(Request $request, Business $business, CourseCategory $category)
    {
        if ((int) $category->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Category not found for this business.', 404);
        }

        if ($category->courses()->exists()) {
            return RequestResponse::badRequest('This category is in use - reassign or deactivate it instead of deleting.');
        }

        $category->delete();

        return RequestResponse::ok('Course category deleted.');
    }

    private function uniqueSlug(Business $business, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (
            CourseCategory::where('business_id', $business->id)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$suffix);
        }

        return $slug;
    }
}
