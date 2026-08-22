<?php

namespace Tests\Feature;

use App\Http\Controllers\LeavePeriodController;
use App\Http\Controllers\LeaveRequestController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeavePolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Leave Period Open/Close - Phase 1 of "Year Open/Close" (see
 * LeavePeriodController::close()'s docblock). Closing a period blocks new
 * leave requests dated within it and triggers carryover into whichever
 * period follows next, reusing LeavePolicyService::createOrUpdateEntitlement()
 * exactly as the accrual pipeline already does.
 */
class LeavePeriodOpenCloseTest extends TestCase
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

    private function actingAsAdmin(): User
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);

        return $admin;
    }

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id, 'business_id' => 1, 'department_id' => 1,
            'employee_code' => 'POC-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();

        EmploymentDetail::create([
            'employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1,
            'employment_date' => '2020-01-01', 'employment_term' => 'permanent',
        ]);

        return $employee;
    }

    public function test_closing_a_period_stamps_status_timestamp_and_closer(): void
    {
        $admin = $this->actingAsAdmin();
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Close Stamp Period ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/x', 'POST', ['leave_period_slug' => $period->slug]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->close($request, app(LeavePolicyService::class))->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $period->refresh();
        $this->assertTrue($period->isClosed());
        $this->assertNotNull($period->closed_at);
        $this->assertSame($admin->id, $period->closed_by);
    }

    public function test_closing_an_already_closed_period_is_rejected(): void
    {
        $admin = $this->actingAsAdmin();
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Double Close Period ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
            'period_status' => 'closed',
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/x', 'POST', ['leave_period_slug' => $period->slug]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->close($request, app(LeavePolicyService::class))->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_closing_computes_carryover_into_the_next_period(): void
    {
        $admin = $this->actingAsAdmin();
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Close Carryover Leave ' . uniqid()]);

        $currentPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Close Carryover Current ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
        ]);
        $nextPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Close Carryover Next ' . uniqid(),
            'start_date' => '2031-01-01', 'end_date' => '2031-12-31', 'is_active' => false,
        ]);

        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0, 'max_carryover_days' => 10,
            'carryover_type' => 'full', 'minimum_service_days_required' => 0,
            'effective_date' => '2020-01-01', 'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $currentPeriod->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 8,
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/x', 'POST', ['leave_period_slug' => $currentPeriod->slug]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->close($request, app(LeavePolicyService::class))->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());

        $nextEntitlement = LeaveEntitlement::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('leave_period_id', $nextPeriod->id)
            ->first();

        $this->assertNotNull($nextEntitlement, 'Expected the close action to create the next period entitlement.');
        $this->assertSame(8.0, $nextEntitlement->carryover_days);
    }

    public function test_a_new_leave_request_dated_inside_a_closed_period_is_rejected(): void
    {
        $admin = $this->actingAsAdmin();
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Closed Period Request Leave ' . uniqid(), 'allows_backdating' => true]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Closed For Requests Period ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
            'period_status' => 'closed',
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        session(['active_business_slug' => Business::find(1)->slug]);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => '2030-06-01', 'end_date' => '2030-06-02',
        ]);
        $request->setUserResolver(fn () => $employee->user);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertStringContainsString('closed', $payload['message']);
    }

    public function test_a_leave_request_outside_any_closed_period_still_works(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Open Period Request Leave ' . uniqid(), 'allows_backdating' => true]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Still Open Period ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        session(['active_business_slug' => Business::find(1)->slug]);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => '2030-06-01', 'end_date' => '2030-06-02',
        ]);
        $request->setUserResolver(fn () => $employee->user);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    public function test_reopen_flips_status_back_without_super_admin_role_check_at_model_level(): void
    {
        $this->actingAsAdmin();
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Reopen Period ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
            'period_status' => 'closed', 'closed_at' => now(),
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/x', 'POST', ['leave_period_slug' => $period->slug]);
        $response = $controller->reopen($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $period->refresh();
        $this->assertFalse($period->isClosed());
        $this->assertNull($period->closed_at);
    }
}
