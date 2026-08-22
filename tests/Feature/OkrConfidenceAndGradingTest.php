<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\PerformanceCycle;
use App\Models\PerformanceObjective;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for OKR Phase 02: auto-flagged confidence (on_track /
 * at_risk / critical) and the 0.0-1.0 stretch-goal grade computed at cycle
 * close.
 */
class OkrConfidenceAndGradingTest extends TestCase
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
            'employee_code' => 'OKRC-' . uniqid(),
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

    private function makeObjectiveWithProgress(Employee $employee, PerformanceCycle $cycle, float $progressPercent): PerformanceObjective
    {
        $objective = PerformanceObjective::create([
            'business_id' => 1,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'scope' => 'individual',
            'title' => 'Objective ' . uniqid(),
            'weight' => 100,
            'status' => 'on_track',
        ]);

        $objective->keyResults()->create([
            'description' => 'KR',
            'target_value' => 100,
            'current_value' => $progressPercent,
            'weight' => 100,
        ]);

        return $objective->fresh(['keyResults']);
    }

    public function test_objective_on_track_when_progress_matches_time_elapsed(): void
    {
        [, $employee] = $this->makeEmployee();
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Confidence Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(30)->toDateString(),
            'end_date' => Carbon::now()->addDays(30)->toDateString(), // ~50% elapsed
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        $objective = $this->makeObjectiveWithProgress($employee, $cycle, 55); // ahead of the 50% expectation

        $this->assertSame('on_track', $objective->computeConfidence());
    }

    public function test_objective_at_risk_when_moderately_behind_schedule(): void
    {
        [, $employee] = $this->makeEmployee();
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Confidence Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(50)->toDateString(),
            'end_date' => Carbon::now()->addDays(50)->toDateString(), // 50% elapsed
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        // Expected ~50%, actual 35% -> gap of 15 (at_risk band).
        $objective = $this->makeObjectiveWithProgress($employee, $cycle, 35);

        $this->assertSame('at_risk', $objective->computeConfidence());
    }

    public function test_objective_critical_near_cycle_end_despite_decent_progress(): void
    {
        [, $employee] = $this->makeEmployee();
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Confidence Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(88)->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(), // ~98% elapsed, days almost up
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        // 50% progress with almost no time left -> critical, even though the
        // raw gap-based bands alone wouldn't necessarily say so.
        $objective = $this->makeObjectiveWithProgress($employee, $cycle, 50);

        $this->assertSame('critical', $objective->computeConfidence());
    }

    public function test_updating_key_result_progress_persists_refreshed_confidence(): void
    {
        [$user, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Confidence Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(80)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        $objective = $this->makeObjectiveWithProgress($employee, $cycle, 10); // way behind
        $keyResult = $objective->keyResults->first();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();
        $request = Request::create("/key-results/{$keyResult->id}/progress", 'POST', ['current_value' => 10]);
        $request->setUserResolver(fn () => $user);

        $response = $controller->updateKeyResultProgress($request, $business, $keyResult)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('critical', $objective->fresh()->confidence);
    }

    public function test_closing_a_cycle_grades_every_objective_once(): void
    {
        [$hrUser,] = $this->makeEmployee();
        [, $employeeA] = $this->makeEmployee();
        [, $employeeB] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Grading Cycle ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        $sweetSpot = $this->makeObjectiveWithProgress($employeeA, $cycle, 75); // green band
        $tooEasy = $this->makeObjectiveWithProgress($employeeB, $cycle, 100); // blue band

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrUser);

        $controller = new PerformanceController();
        $request = Request::create("/cycles/{$cycle->id}/status", 'POST', ['status' => 'closed']);
        $request->setUserResolver(fn () => $hrUser);
        $response = $controller->updateCycleStatus($request, $business, $cycle)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $sweetSpot->refresh();
        $tooEasy->refresh();

        $this->assertSame(0.75, $sweetSpot->final_score);
        $this->assertSame('green', $sweetSpot->gradeBand());

        $this->assertSame(1.0, $tooEasy->final_score);
        $this->assertSame('blue', $tooEasy->gradeBand());
    }

    public function test_critical_objectives_feed_is_scoped_to_the_managers_team(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployee();
        [, $reportEmployee] = $this->makeEmployee();
        $reportEmployee->manager_id = $managerEmployee->id;
        $reportEmployee->save();
        [, $strangerEmployee] = $this->makeEmployee();

        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Feed Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(88)->toDateString(),
            'end_date' => Carbon::now()->addDays(2)->toDateString(),
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        $reportObjective = $this->makeObjectiveWithProgress($reportEmployee, $cycle, 20);
        $reportObjective->refreshConfidence();
        $strangerObjective = $this->makeObjectiveWithProgress($strangerEmployee, $cycle, 20);
        $strangerObjective->refreshConfidence();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($managerUser);

        $controller = new PerformanceController();
        $request = Request::create('/objectives/critical', 'GET', ['performance_cycle_id' => $cycle->id]);
        $request->setUserResolver(fn () => $managerUser);
        $response = $controller->fetchCriticalObjectives($request, $business)->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $ids = collect($payload['data'])->pluck('id');
        $this->assertTrue($ids->contains($reportObjective->id));
        $this->assertFalse($ids->contains($strangerObjective->id));
    }
}
