<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceFeedbackResponse;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;

/**
 * OKR Phase 04: peer/360 feedback. Purely qualitative evidence gathered
 * from nominated peers about a subject employee - it never carries a
 * numeric score and never feeds into OKR/KPI grading.
 */
class PerformanceFeedbackController extends Controller
{
    use HandleTransactions;

    /**
     * Self, their direct line manager, or HR/admin can view/manage
     * feedback about an employee - mirrors PerformanceController's rule so
     * the same people who can see objectives can see 360 feedback.
     */
    private function canManagePerformanceFor(Employee $target): bool
    {
        $actingEmployee = auth()->user()->activeEmployee();
        if ($actingEmployee && (int) $actingEmployee->id === (int) $target->id) {
            return true;
        }
        if ($actingEmployee && (int) $target->manager_id === (int) $actingEmployee->id) {
            return true;
        }

        $activeRole = strtolower((string) session('active_role'));
        return in_array($activeRole, ['business-hr', 'business-admin'], true);
    }

    /**
     * The 360 feedback gathered about a subject employee for a cycle -
     * nominated peers, their status, and their submitted answers.
     */
    public function fetchForSubject(Request $request, Business $business, Employee $employee)
    {
        if (!$this->canManagePerformanceFor($employee)) {
            return RequestResponse::badRequest('You are not authorized to view this employee\'s feedback.', 403);
        }

        $cycleId = $request->integer('performance_cycle_id');

        $requests = PerformanceFeedbackRequest::where('business_id', $business->id)
            ->where('subject_employee_id', $employee->id)
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId))
            ->with(['reviewer.user:id,name', 'response'])
            ->latest()
            ->get();

        return RequestResponse::ok('Feedback requests fetched successfully.', $requests);
    }

    /**
     * The acting employee's own inbox - feedback they've been asked to
     * give about someone else, across any subject.
     */
    public function fetchMyInbox(Request $request, Business $business)
    {
        $actingEmployee = auth()->user()->activeEmployee();
        if (!$actingEmployee || (int) $actingEmployee->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('No employee record for this business.', 403);
        }

        $cycleId = $request->integer('performance_cycle_id');

        $requests = PerformanceFeedbackRequest::where('business_id', $business->id)
            ->where('reviewer_employee_id', $actingEmployee->id)
            ->where('status', 'pending')
            ->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId))
            ->with('subject.user:id,name')
            ->latest()
            ->get();

        return RequestResponse::ok('Feedback inbox fetched successfully.', $requests);
    }

    /**
     * Nominate a peer to give feedback about the subject employee - the
     * subject themselves (self-nomination of reviewers) or their
     * manager/HR can do this.
     */
    public function store(Request $request, Business $business, Employee $employee)
    {
        if (!$this->canManagePerformanceFor($employee)) {
            return RequestResponse::badRequest('You are not authorized to request feedback for this employee.', 403);
        }

        $validated = $request->validate([
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'reviewer_employee_id' => 'required|exists:employees,id',
        ]);

        if ((int) $validated['reviewer_employee_id'] === (int) $employee->id) {
            return RequestResponse::badRequest('An employee cannot be nominated to give feedback about themselves.', 422);
        }

        $reviewer = Employee::where('business_id', $business->id)->find($validated['reviewer_employee_id']);
        if (!$reviewer) {
            return RequestResponse::badRequest('Reviewer not found for this business.', 404);
        }

        $cycle = PerformanceCycle::where('business_id', $business->id)->find($validated['performance_cycle_id']);
        if (!$cycle) {
            return RequestResponse::badRequest('Cycle not found for this business.', 404);
        }

        $alreadyExists = PerformanceFeedbackRequest::where('performance_cycle_id', $cycle->id)
            ->where('subject_employee_id', $employee->id)
            ->where('reviewer_employee_id', $reviewer->id)
            ->exists();
        if ($alreadyExists) {
            return RequestResponse::badRequest('This peer has already been nominated for this cycle.', 422);
        }

        return $this->handleTransaction(function () use ($business, $employee, $reviewer, $cycle, $request) {
            $feedbackRequest = PerformanceFeedbackRequest::create([
                'business_id' => $business->id,
                'performance_cycle_id' => $cycle->id,
                'subject_employee_id' => $employee->id,
                'reviewer_employee_id' => $reviewer->id,
                'requested_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            return RequestResponse::created('Feedback requested successfully.', $feedbackRequest->load('reviewer.user:id,name'));
        });
    }

    /**
     * The nominated reviewer declines to give feedback.
     */
    public function decline(Request $request, Business $business, PerformanceFeedbackRequest $feedbackRequest)
    {
        if ((int) $feedbackRequest->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Feedback request not found for this business.', 404);
        }

        $actingEmployee = auth()->user()->activeEmployee();
        if (!$actingEmployee || (int) $actingEmployee->id !== (int) $feedbackRequest->reviewer_employee_id) {
            return RequestResponse::badRequest('Only the nominated reviewer can decline this request.', 403);
        }
        if ($feedbackRequest->status !== 'pending') {
            return RequestResponse::badRequest('This feedback request is no longer pending.', 422);
        }

        $feedbackRequest->update(['status' => 'declined']);

        return RequestResponse::ok('Feedback request declined.', $feedbackRequest->fresh());
    }

    /**
     * The nominated reviewer submits their behavioral feedback - every
     * question in the fixed set is required, and it's stored as-is with
     * no scoring applied.
     */
    public function submitResponse(Request $request, Business $business, PerformanceFeedbackRequest $feedbackRequest)
    {
        if ((int) $feedbackRequest->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Feedback request not found for this business.', 404);
        }

        $actingEmployee = auth()->user()->activeEmployee();
        if (!$actingEmployee || (int) $actingEmployee->id !== (int) $feedbackRequest->reviewer_employee_id) {
            return RequestResponse::badRequest('Only the nominated reviewer can submit this feedback.', 403);
        }
        if ($feedbackRequest->status !== 'pending') {
            return RequestResponse::badRequest('This feedback request is no longer pending.', 422);
        }

        $rules = [];
        foreach (array_keys(PerformanceFeedbackResponse::QUESTIONS) as $key) {
            $rules["answers.$key"] = 'required|string|max:2000';
        }
        $validated = $request->validate($rules);

        return $this->handleTransaction(function () use ($validated, $feedbackRequest) {
            $response = PerformanceFeedbackResponse::create([
                'performance_feedback_request_id' => $feedbackRequest->id,
                'answers' => $validated['answers'],
                'submitted_at' => now(),
            ]);

            $feedbackRequest->update(['status' => 'submitted']);

            return RequestResponse::created('Feedback submitted successfully.', $response);
        });
    }
}
