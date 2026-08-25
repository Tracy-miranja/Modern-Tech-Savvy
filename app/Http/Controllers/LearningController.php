<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSession;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobCategory;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningController extends Controller
{
    use HandleTransactions;

    public function index(Business $business)
    {
        $page = 'Learning Management';
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('learning.index', compact('page', 'business', 'departments', 'jobCategories'));
    }

    // ---- Courses -----------------------------------------------------

    public function fetchCourses(Request $request, Business $business)
    {
        $query = Course::where('business_id', $business->id)
            ->with('category:id,name')
            ->withCount(['sessions', 'enrollments']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $courses = $query->orderBy('title')->get();

        return RequestResponse::ok('Courses fetched.', $courses);
    }

    public function courseOptions(Request $request, Business $business)
    {
        $courses = Course::where('business_id', $business->id)->orderBy('title')->get(['id', 'title']);

        return RequestResponse::ok('Courses fetched.', $courses);
    }

    public function storeCourse(Request $request, Business $business)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_category_id' => ['nullable', 'integer', Rule::exists('course_categories', 'id')->where('business_id', $business->id)],
            'provider' => 'nullable|string|max:150',
            'duration_hours' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,archived',
        ]);

        $course = Course::create($validated + ['business_id' => $business->id, 'status' => $validated['status'] ?? 'active']);

        return RequestResponse::created('Course created.', $course->load('category'));
    }

    public function updateCourse(Request $request, Business $business, Course $course)
    {
        if ((int) $course->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Course not found for this business.', 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'course_category_id' => ['nullable', 'integer', Rule::exists('course_categories', 'id')->where('business_id', $business->id)],
            'provider' => 'nullable|string|max:150',
            'duration_hours' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,archived',
        ]);

        $course->update($validated);

        return RequestResponse::ok('Course updated.', $course->fresh()->load('category'));
    }

    public function destroyCourse(Request $request, Business $business, Course $course)
    {
        if ((int) $course->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Course not found for this business.', 404);
        }

        if ($course->enrollments()->exists()) {
            return RequestResponse::badRequest('This course has enrollments - archive it instead of deleting.');
        }

        $course->delete();

        return RequestResponse::ok('Course deleted.');
    }

    // ---- Sessions ("Training Schedules") ------------------------------

    public function fetchSessions(Request $request, Business $business, Course $course)
    {
        if ((int) $course->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Course not found for this business.', 404);
        }

        $sessions = $course->sessions()->withCount('enrollments')->orderBy('start_date')->get();

        return RequestResponse::ok('Sessions fetched.', $sessions);
    }

    public function storeSession(Request $request, Business $business, Course $course)
    {
        if ((int) $course->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Course not found for this business.', 404);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled',
        ]);

        $session = CourseSession::create($validated + [
            'course_id' => $course->id,
            'business_id' => $business->id,
            'status' => $validated['status'] ?? 'scheduled',
        ]);

        return RequestResponse::created('Session added.', $session);
    }

    public function updateSession(Request $request, Business $business, CourseSession $courseSession)
    {
        if ((int) $courseSession->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Session not found for this business.', 404);
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled',
        ]);

        $courseSession->update($validated);

        return RequestResponse::ok('Session updated.', $courseSession->fresh());
    }

    public function destroySession(Request $request, Business $business, CourseSession $courseSession)
    {
        if ((int) $courseSession->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Session not found for this business.', 404);
        }

        if ($courseSession->enrollments()->exists()) {
            return RequestResponse::badRequest('This session has enrollments - remove or reassign them first.');
        }

        $courseSession->delete();

        return RequestResponse::ok('Session deleted.');
    }

    // ---- Enrollments ---------------------------------------------------

    public function fetchEnrollments(Request $request, Business $business)
    {
        $query = CourseEnrollment::where('business_id', $business->id)
            ->with(['course:id,title', 'session:id,start_date,location', 'employee.user:id,name']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $enrollments = $query->orderByDesc('enrolled_at')->get();

        return RequestResponse::ok('Enrollments fetched.', $enrollments);
    }

    public function storeEnrollment(Request $request, Business $business, Course $course)
    {
        if ((int) $course->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Course not found for this business.', 404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'course_session_id' => 'nullable|integer|exists:course_sessions,id',
        ]);

        return $this->handleTransaction(function () use ($validated, $business, $course) {
            $employee = Employee::where('business_id', $business->id)->find($validated['employee_id']);
            if (!$employee) {
                return RequestResponse::badRequest('Employee not found for this business.', 404);
            }

            if (!empty($validated['course_session_id'])) {
                $session = CourseSession::where('business_id', $business->id)
                    ->where('course_id', $course->id)
                    ->find($validated['course_session_id']);
                if (!$session) {
                    return RequestResponse::badRequest('Session not found for this course.', 404);
                }
            }

            if (CourseEnrollment::where('course_id', $course->id)->where('employee_id', $employee->id)->exists()) {
                return RequestResponse::badRequest('This employee is already enrolled in this course.');
            }

            $enrollment = CourseEnrollment::create([
                'course_id' => $course->id,
                'course_session_id' => $validated['course_session_id'] ?? null,
                'business_id' => $business->id,
                'employee_id' => $employee->id,
                'status' => 'enrolled',
                'enrolled_at' => now(),
            ]);

            return RequestResponse::created('Employee enrolled.', $enrollment->load('course', 'session', 'employee.user'));
        });
    }

    public function updateEnrollment(Request $request, Business $business, CourseEnrollment $enrollment)
    {
        if ((int) $enrollment->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Enrollment not found for this business.', 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:enrolled,in_progress,completed,dropped',
            'score' => 'nullable|numeric|min:0|max:100',
            'certificate_issued' => 'nullable|boolean',
            'certificate_number' => 'nullable|string|max:100',
            'certificate_expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['completed_at'] = $validated['status'] === 'completed'
            ? ($enrollment->completed_at ?? now())
            : null;

        if (!empty($validated['certificate_issued'])) {
            if (empty($validated['certificate_number']) && empty($enrollment->certificate_number)) {
                $prefix = $business->learning_certificate_number_prefix ?: 'CERT';
                $validated['certificate_number'] = $prefix . '-' . str_pad($enrollment->id, 5, '0', STR_PAD_LEFT);
            }
            if (empty($validated['certificate_expiry_date']) && empty($enrollment->certificate_expiry_date) && $business->learning_certificate_validity_months) {
                $validated['certificate_expiry_date'] = now()->addMonths($business->learning_certificate_validity_months)->toDateString();
            }
        }

        $enrollment->update($validated);

        return RequestResponse::ok('Enrollment updated.', $enrollment->fresh(['course', 'session', 'employee.user']));
    }

    public function destroyEnrollment(Request $request, Business $business, CourseEnrollment $enrollment)
    {
        if ((int) $enrollment->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Enrollment not found for this business.', 404);
        }

        $enrollment->delete();

        return RequestResponse::ok('Enrollment removed.');
    }

    // ---- Settings: certificate defaults + reminder days --------------

    public function updateSettings(Request $request, Business $business)
    {
        $validated = $request->validate([
            'learning_certificate_validity_months' => 'nullable|integer|min:1|max:120',
            'learning_certificate_number_prefix' => 'nullable|string|max:20',
            'learning_session_reminder_days' => 'required|integer|min:0|max:60',
            'learning_certificate_expiry_reminder_days' => 'required|integer|min:0|max:180',
        ]);

        $business->update($validated);

        return RequestResponse::ok('Learning settings updated.', $business->fresh());
    }
}
