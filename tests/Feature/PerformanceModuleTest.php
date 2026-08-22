<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\PerformanceCycle;
use App\Models\PerformanceObjective;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the Performance module: company-defined cycle
 * weighting, OKR progress roll-up, KPI/OKR/competency score blending, the
 * self -> manager review flow, and organogram-based authorization.
 */
class PerformanceModuleTest extends TestCase
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

    private function makeEmployeeUser(?int $managerId = null): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'manager_id' => $managerId,
            'employee_code' => 'PERF-' . uniqid(),
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

    private function makeCycle(array $overrides = []): PerformanceCycle
    {
        return PerformanceCycle::create(array_merge([
            'business_id' => 1,
            'name' => 'Cycle ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 40,
            'okr_weight' => 40,
            'competency_weight' => 20,
            'status' => 'active',
        ], $overrides));
    }

    public function test_cycle_creation_rejects_weights_that_do_not_sum_to_100(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        $business = Business::find(1);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new PerformanceController();
        $request = Request::create('/performance/cycles', 'POST', [
            'name' => 'Bad Weights Cycle',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 50,
            'okr_weight' => 30,
            'competency_weight' => 30, // sums to 110
        ]);
        $request->setUserResolver(fn () => $hrUser);

        $response = $controller->storeCycle($request, $business)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame(0, PerformanceCycle::where('name', 'Bad Weights Cycle')->count());
    }

    public function test_cycle_creation_succeeds_when_weights_sum_to_100(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        $business = Business::find(1);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new PerformanceController();
        $request = Request::create('/performance/cycles', 'POST', [
            'name' => 'Good Weights Cycle',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 30,
            'okr_weight' => 50,
            'competency_weight' => 20,
        ]);
        $request->setUserResolver(fn () => $hrUser);

        $response = $controller->storeCycle($request, $business)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, PerformanceCycle::where('name', 'Good Weights Cycle')->count());
    }

    public function test_key_result_progress_rolls_up_into_objective_and_okr_score(): void
    {
        [, $employee] = $this->makeEmployeeUser();
        $cycle = $this->makeCycle();

        $objective = PerformanceObjective::create([
            'business_id' => 1,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'title' => 'Ship the new feature',
            'weight' => 100,
            'status' => 'on_track',
        ]);

        $objective->keyResults()->create([
            'description' => 'Complete backend',
            'target_value' => 10,
            'current_value' => 10, // 100%
            'weight' => 50,
        ]);
        $objective->keyResults()->create([
            'description' => 'Complete frontend',
            'target_value' => 10,
            'current_value' => 5, // 50%
            'weight' => 50,
        ]);

        // Weighted average: (100*50 + 50*50) / 100 = 75
        $this->assertSame(75.0, $objective->fresh()->progress);

        $review = \App\Models\PerformanceReview::create([
            'business_id' => 1,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'status' => 'pending_self',
        ]);

        $this->assertSame(75.0, $review->computeOkrScore());
    }

    public function test_overall_score_blends_kpi_okr_competency_using_cycle_weights(): void
    {
        [, $employee] = $this->makeEmployeeUser();
        $cycle = $this->makeCycle([
            'kpi_weight' => 40,
            'okr_weight' => 40,
            'competency_weight' => 20,
        ]);

        $review = \App\Models\PerformanceReview::create([
            'business_id' => 1,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'kpi_score' => 80,
            'okr_score' => 60,
            'competency_score' => 100,
            'status' => 'pending_manager',
        ]);

        // (80*40 + 60*40 + 100*20) / 100 = 76
        $this->assertSame(76.0, $review->computeOverallScore());
    }

    public function test_full_review_flow_self_then_manager_produces_completed_status_and_score(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$employeeUser, $employee] = $this->makeEmployeeUser($managerEmployee->id);
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        $controller = new PerformanceController();

        // Employee fetches (creates) their review, auto-assigned to their manager.
        $this->actingAs($employeeUser);
        $fetchResponse = $controller->fetchReview($business, $cycle, $employee)->toResponse(Request::create('/x'));
        $review = \App\Models\PerformanceReview::where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->first();

        $this->assertNotNull($review);
        $this->assertSame($managerEmployee->id, $review->reviewer_id);
        $this->assertSame('pending_self', $review->status);

        // Employee submits self-assessment.
        $selfRequest = Request::create("/reviews/{$review->id}/self-assessment", 'POST', [
            'self_assessment' => 'I did well this cycle.',
        ]);
        $selfRequest->setUserResolver(fn () => $employeeUser);
        $selfResponse = $controller->submitSelfAssessment($selfRequest, $business, $review)->toResponse($selfRequest);
        $this->assertSame(200, $selfResponse->getStatusCode());
        $this->assertSame('pending_manager', $review->fresh()->status);

        // A stranger cannot submit the manager assessment.
        [$strangerUser,] = $this->makeEmployeeUser();
        session(['active_role' => 'business-employee']);
        $this->actingAs($strangerUser);
        $strangerRequest = Request::create("/reviews/{$review->id}/manager-assessment", 'POST', [
            'manager_assessment' => 'Nope',
            'competency_score' => 90,
        ]);
        $strangerRequest->setUserResolver(fn () => $strangerUser);
        $strangerResponse = $controller->submitManagerAssessment($strangerRequest, $business, $review)->toResponse($strangerRequest);
        $this->assertSame(400, $strangerResponse->getStatusCode());

        // The assigned manager can submit it.
        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);
        $managerRequest = Request::create("/reviews/{$review->id}/manager-assessment", 'POST', [
            'manager_assessment' => 'Solid quarter.',
            'competency_score' => 90,
        ]);
        $managerRequest->setUserResolver(fn () => $managerUser);
        $managerResponse = $controller->submitManagerAssessment($managerRequest, $business, $review)->toResponse($managerRequest);
        $this->assertSame(200, $managerResponse->getStatusCode());

        $review->refresh();
        $this->assertSame('completed', $review->status);
        $this->assertNotNull($review->overall_score);
        $this->assertNotNull($review->manager_submitted_at);
    }

    public function test_stranger_cannot_view_or_set_objectives_for_unrelated_employee(): void
    {
        [, $employee] = $this->makeEmployeeUser();
        [$strangerUser,] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_role' => 'business-employee']);
        $this->actingAs($strangerUser);

        $controller = new PerformanceController();
        $request = Request::create("/performance/employees/{$employee->id}/objectives", 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Sneaky objective',
        ]);
        $request->setUserResolver(fn () => $strangerUser);

        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, PerformanceObjective::where('title', 'Sneaky objective')->count());
    }

    public function test_direct_manager_can_set_objectives_for_their_report(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser($managerEmployee->id);
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);

        $controller = new PerformanceController();
        $request = Request::create("/performance/employees/{$employee->id}/objectives", 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Manager-set objective',
            'weight' => 100,
        ]);
        $request->setUserResolver(fn () => $managerUser);

        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, PerformanceObjective::where('title', 'Manager-set objective')->count());
    }
}
