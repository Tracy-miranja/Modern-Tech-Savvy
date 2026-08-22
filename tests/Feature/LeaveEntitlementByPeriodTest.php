<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveEntitlementController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the "Set Entitlements" form's period-change
 * handler: `getByPeriod()` was routed but never implemented, so selecting a
 * leave period always failed the page's Promise.all and showed
 * "Error fetching employees."
 */
class LeaveEntitlementByPeriodTest extends TestCase
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

    public function test_get_by_period_returns_entitlements_for_the_selected_period(): void
    {
        $business = Business::find(1);
        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'By-Period Test ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'By-Period Leave ' . uniqid(),
        ]);

        $employeeUser = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'BYPD-' . uniqid(),
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

        LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        $hrUser = User::factory()->create();
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/by-period', 'POST', [
            'leave_period_id' => $period->id,
        ]);
        $request->setUserResolver(fn () => $hrUser);

        $response = $controller->getByPeriod($request)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);
        $this->assertCount(1, $payload['data']);
        $this->assertSame($employee->id, $payload['data'][0]['employee_id']);
        $this->assertEquals(21.0, $payload['data'][0]['entitled_days']);
    }
}
