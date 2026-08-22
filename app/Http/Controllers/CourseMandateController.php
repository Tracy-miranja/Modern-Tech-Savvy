<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Course;
use App\Models\CourseMandate;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

/**
 * Learning Management Settings - mandatory/compliance courses. Creating or
 * widening a mandate immediately auto-enrolls every currently-matching
 * employee (CourseMandate::autoEnroll()); the daily learning:sync command
 * re-runs the same call so employees added after the mandate was saved
 * still get caught. See the course_mandates migration's docblock for why
 * this is deliberately additive-only.
 */
class CourseMandateController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request, Business $business)
    {
        $mandates = CourseMandate::where('business_id', $business->id)
            ->with('course:id,title')
            ->orderByDesc('id')
            ->get()
            ->map(function ($mandate) {
                $mandate->affected_employees_count = $mandate->resolveAffectedEmployees()->count();
                return $mandate;
            });

        return RequestResponse::ok('Course mandates fetched.', $mandates);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'scope_type' => 'required|in:organization,department,job_category',
            'scope_ids' => 'nullable|array',
            'scope_ids.*' => 'integer',
        ]);
        $validated = $this->normalizeScope($validated);

        $course = Course::where('business_id', $business->id)->find($validated['course_id']);
        if (!$course) {
            return RequestResponse::badRequest('Course not found for this business.', 404);
        }

        return $this->handleTransaction(function () use ($validated, $business, $request) {
            $mandate = CourseMandate::create($validated + [
                'business_id' => $business->id,
                'created_by' => $request->user()?->id,
            ]);

            $enrolledCount = $mandate->autoEnroll();

            return RequestResponse::created("Mandate created - {$enrolledCount} employee(s) auto-enrolled.", $mandate->fresh());
        });
    }

    /**
     * Scope and active-state only - a mandate's course is fixed at
     * creation (changing it would orphan the enrollments it already made).
     */
    public function update(Request $request, Business $business, CourseMandate $mandate)
    {
        if ((int) $mandate->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Mandate not found for this business.', 404);
        }

        $validated = $request->validate([
            'scope_type' => 'sometimes|required|in:organization,department,job_category',
            'scope_ids' => 'nullable|array',
            'scope_ids.*' => 'integer',
            'is_active' => 'nullable|boolean',
        ]);
        $validated = $this->normalizeScope($validated);

        return $this->handleTransaction(function () use ($validated, $mandate) {
            $mandate->update($validated);
            $enrolledCount = $mandate->autoEnroll();

            return RequestResponse::ok("Mandate updated - {$enrolledCount} newly-matching employee(s) auto-enrolled.", $mandate->fresh());
        });
    }

    public function destroy(Request $request, Business $business, CourseMandate $mandate)
    {
        if ((int) $mandate->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Mandate not found for this business.', 404);
        }

        // Deleting the mandate never touches existing enrollments it
        // created - they stay as ordinary enrollments (course_mandate_id
        // is nullOnDelete on the FK), preserving progress/certificates.
        $mandate->delete();

        return RequestResponse::ok('Mandate removed. Employees already enrolled through it keep their enrollment.');
    }

    private function normalizeScope(array $validated): array
    {
        if (($validated['scope_type'] ?? null) === CourseMandate::SCOPE_ORGANIZATION) {
            $validated['scope_ids'] = null;
        }

        return $validated;
    }
}
