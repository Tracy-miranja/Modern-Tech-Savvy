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
 * Regression coverage for OKR Phase 03's Goal Visibility Lock: goal-setting
 * stays open through the normal active period (objectives are only ever
 * created against active cycles in this system) and only locks once the
 * self-review window opens or the cycle closes - progress updates are
 * never blocked by it.
 */
class OkrGoalLockTest extends TestCase
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
            'employee_code' => 'OKRL-' . uniqid(),
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

    public function test_objectives_can_still_be_added_during_the_normal_active_period(): void
    {
        [$user, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Lock Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(80)->toDateString(),
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
            // No self_review_due_date set - goal-setting should stay open.
        ]);

        $this->assertFalse($cycle->goalsAreLocked());

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();
        $request = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Normal goal',
            'weight' => 100,
        ]);
        $request->setUserResolver(fn () => $user);
        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_new_objectives_are_blocked_once_the_self_review_window_has_opened(): void
    {
        [$user, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Lock Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(80)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'self_review_due_date' => Carbon::now()->subDay()->toDateString(),
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        $this->assertTrue($cycle->goalsAreLocked());

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();
        $request = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Sneaky late goal',
            'weight' => 100,
        ]);
        $request->setUserResolver(fn () => $user);
        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, PerformanceObjective::where('title', 'Sneaky late goal')->count());
    }

    public function test_lock_goals_on_start_false_keeps_goal_setting_open_past_the_review_date(): void
    {
        [$user, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Unlocked Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(80)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'self_review_due_date' => Carbon::now()->subDay()->toDateString(),
            'lock_goals_on_start' => false,
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);

        $this->assertFalse($cycle->goalsAreLocked());

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();
        $request = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Still allowed',
            'weight' => 100,
        ]);
        $request->setUserResolver(fn () => $user);
        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_new_key_results_are_blocked_on_a_locked_cycle_but_progress_updates_still_work(): void
    {
        [$user, $employee] = $this->makeEmployee();
        $business = Business::find(1);

        // Objective + key result created while the cycle was still open.
        $openCycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Lock Cycle ' . uniqid(),
            'start_date' => Carbon::now()->subDays(80)->toDateString(),
            'end_date' => Carbon::now()->addDays(10)->toDateString(),
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'active',
        ]);
        $objective = PerformanceObjective::create([
            'business_id' => 1,
            'performance_cycle_id' => $openCycle->id,
            'employee_id' => $employee->id,
            'scope' => 'individual',
            'title' => 'Pre-lock objective',
            'weight' => 100,
            'status' => 'on_track',
        ]);
        $keyResult = $objective->keyResults()->create([
            'description' => 'Existing KR',
            'target_value' => 100,
            'current_value' => 10,
            'weight' => 100,
        ]);

        // Now the cycle's self-review window opens - goals lock.
        $openCycle->update(['self_review_due_date' => Carbon::now()->subDay()->toDateString()]);
        $this->assertTrue($openCycle->fresh()->goalsAreLocked());

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();

        // Adding a new key result is blocked.
        $krRequest = Request::create("/objectives/{$objective->id}/key-results", 'POST', [
            'description' => 'Sneaky new KR',
            'target_value' => 50,
        ]);
        $krRequest->setUserResolver(fn () => $user);
        $krResponse = $controller->storeKeyResult($krRequest, $business, $objective)->toResponse($krRequest);
        $this->assertSame(400, $krResponse->getStatusCode());

        // But updating progress on the existing key result still works.
        $progressRequest = Request::create("/key-results/{$keyResult->id}/progress", 'POST', ['current_value' => 42]);
        $progressRequest->setUserResolver(fn () => $user);
        $progressResponse = $controller->updateKeyResultProgress($progressRequest, $business, $keyResult)->toResponse($progressRequest);
        $this->assertSame(200, $progressResponse->getStatusCode());
        $this->assertSame(42.0, $keyResult->fresh()->current_value);
    }

    public function test_closed_cycles_are_always_locked_regardless_of_review_date(): void
    {
        [$user, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'Closed Cycle ' . uniqid(),
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'kpi_weight' => 40, 'okr_weight' => 40, 'competency_weight' => 20,
            'status' => 'closed',
        ]);

        $this->assertTrue($cycle->goalsAreLocked());

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();
        $request = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Too late',
            'weight' => 100,
        ]);
        $request->setUserResolver(fn () => $user);
        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
