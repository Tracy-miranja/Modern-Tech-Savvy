<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceFeedbackController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceFeedbackResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for OKR Phase 04: peer/360 feedback. Nominations,
 * the reviewer's own inbox, decline, and the behavioral-question-only
 * submission that never carries a numeric score.
 */
class OkrPeerFeedbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'hrmamsol',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);

        DB::purge('mysql');
        DB::connection('mysql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployee(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'OKRF-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => 1,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return [$user->fresh(), $employee->fresh()];
    }

    private function makeCycle(): PerformanceCycle
    {
        return PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Feedback Cycle ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);
    }

    public function test_subject_can_nominate_a_peer_to_give_feedback(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        [, $peer] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);

        $controller = new PerformanceFeedbackController();
        $request = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $peer->id,
        ]);
        $request->setUserResolver(fn () => $subjectUser);

        $response = $controller->store($request, $business, $subject)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode());

        $this->assertSame(1, PerformanceFeedbackRequest::where('subject_employee_id', $subject->id)
            ->where('reviewer_employee_id', $peer->id)
            ->where('status', 'pending')
            ->count());
    }

    public function test_cannot_nominate_the_subject_as_their_own_reviewer(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);

        $controller = new PerformanceFeedbackController();
        $request = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $subject->id,
        ]);
        $request->setUserResolver(fn () => $subjectUser);

        $response = $controller->store($request, $business, $subject)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_duplicate_nomination_for_the_same_cycle_is_rejected(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        [, $peer] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);
        $controller = new PerformanceFeedbackController();

        $data = ['performance_cycle_id' => $cycle->id, 'reviewer_employee_id' => $peer->id];
        $first = Request::create('/feedback', 'POST', $data);
        $first->setUserResolver(fn () => $subjectUser);
        $controller->store($first, $business, $subject)->toResponse($first);

        $second = Request::create('/feedback', 'POST', $data);
        $second->setUserResolver(fn () => $subjectUser);
        $response = $controller->store($second, $business, $subject)->toResponse($second);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(1, PerformanceFeedbackRequest::where('subject_employee_id', $subject->id)->count());
    }

    public function test_a_stranger_cannot_nominate_peers_for_someone_elses_feedback(): void
    {
        [, $subject] = $this->makeEmployee();
        [$strangerUser,] = $this->makeEmployee();
        [, $peer] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($strangerUser);

        $controller = new PerformanceFeedbackController();
        $request = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $peer->id,
        ]);
        $request->setUserResolver(fn () => $strangerUser);

        $response = $controller->store($request, $business, $subject)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_reviewer_sees_the_nomination_in_their_own_inbox(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        [$peerUser, $peer] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);
        $controller = new PerformanceFeedbackController();
        $storeRequest = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $peer->id,
        ]);
        $storeRequest->setUserResolver(fn () => $subjectUser);
        $controller->store($storeRequest, $business, $subject)->toResponse($storeRequest);

        $this->actingAs($peerUser);
        $inboxRequest = Request::create('/feedback/inbox', 'GET', ['performance_cycle_id' => $cycle->id]);
        $inboxRequest->setUserResolver(fn () => $peerUser);
        $response = $controller->fetchMyInbox($inboxRequest, $business)->toResponse($inboxRequest);
        $payload = json_decode($response->getContent(), true);

        $subjectIds = collect($payload['data'])->pluck('subject_employee_id');
        $this->assertTrue($subjectIds->contains($subject->id));
    }

    public function test_reviewer_can_submit_behavioral_feedback_with_no_score_fields(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        [$peerUser, $peer] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);
        $controller = new PerformanceFeedbackController();
        $storeRequest = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $peer->id,
        ]);
        $storeRequest->setUserResolver(fn () => $subjectUser);
        $controller->store($storeRequest, $business, $subject)->toResponse($storeRequest);
        $feedbackRequest = PerformanceFeedbackRequest::where('subject_employee_id', $subject->id)->first();

        $this->actingAs($peerUser);
        $submitRequest = Request::create("/feedback/{$feedbackRequest->id}/response", 'POST', [
            'answers' => [
                'strengths' => 'Always delivers ahead of schedule.',
                'growth_areas' => 'Could delegate more.',
                'collaboration_example' => 'Paired with design to unblock the launch.',
                'additional_comments' => 'Great teammate.',
            ],
        ]);
        $submitRequest->setUserResolver(fn () => $peerUser);
        $response = $controller->submitResponse($submitRequest, $business, $feedbackRequest)->toResponse($submitRequest);
        $this->assertSame(201, $response->getStatusCode());

        $this->assertSame('submitted', $feedbackRequest->fresh()->status);
        $savedResponse = PerformanceFeedbackResponse::where('performance_feedback_request_id', $feedbackRequest->id)->first();
        $this->assertNotNull($savedResponse);
        $this->assertSame(array_keys(PerformanceFeedbackResponse::QUESTIONS), array_keys($savedResponse->answers));
    }

    public function test_only_the_nominated_reviewer_can_submit_or_decline(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        [, $peer] = $this->makeEmployee();
        [$strangerUser,] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);
        $controller = new PerformanceFeedbackController();
        $storeRequest = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $peer->id,
        ]);
        $storeRequest->setUserResolver(fn () => $subjectUser);
        $controller->store($storeRequest, $business, $subject)->toResponse($storeRequest);
        $feedbackRequest = PerformanceFeedbackRequest::where('subject_employee_id', $subject->id)->first();

        $this->actingAs($strangerUser);
        $declineRequest = Request::create("/feedback/{$feedbackRequest->id}/decline", 'POST');
        $declineRequest->setUserResolver(fn () => $strangerUser);
        $response = $controller->decline($declineRequest, $business, $feedbackRequest)->toResponse($declineRequest);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('pending', $feedbackRequest->fresh()->status);
    }

    public function test_reviewer_can_decline_a_nomination(): void
    {
        [$subjectUser, $subject] = $this->makeEmployee();
        [$peerUser, $peer] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($subjectUser);
        $controller = new PerformanceFeedbackController();
        $storeRequest = Request::create('/feedback', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'reviewer_employee_id' => $peer->id,
        ]);
        $storeRequest->setUserResolver(fn () => $subjectUser);
        $controller->store($storeRequest, $business, $subject)->toResponse($storeRequest);
        $feedbackRequest = PerformanceFeedbackRequest::where('subject_employee_id', $subject->id)->first();

        $this->actingAs($peerUser);
        $declineRequest = Request::create("/feedback/{$feedbackRequest->id}/decline", 'POST');
        $declineRequest->setUserResolver(fn () => $peerUser);
        $response = $controller->decline($declineRequest, $business, $feedbackRequest)->toResponse($declineRequest);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('declined', $feedbackRequest->fresh()->status);
    }
}
