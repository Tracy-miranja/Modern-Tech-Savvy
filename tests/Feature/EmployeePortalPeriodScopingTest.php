<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The employee portal (dashboard "My Leave Balance" widget and the "Request
 * Leave" form) was showing entitlement/leave-type data with no regard for
 * which leave period is actually current:
 * - EmployeeDashboardController::index()'s leave_balances pulled EVERY
 *   LeaveEntitlement the employee ever had, across every period - the same
 *   leave type would show up once per period (2025, 2026, 2027, ...) with
 *   different, confusing numbers, instead of just the current one.
 * - requestLeave()'s leave type list included deactivated leave types.
 */
class EmployeePortalPeriodScopingTest extends TestCase
{
    private Business $business;

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

        $this->business = Business::find(1); // amsol
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('business-employee');

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => 1,
            'employee_code' => 'PORTAL-' . uniqid(),
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

    public function test_dashboard_leave_balances_only_include_the_current_periods_entitlements(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $leaveType = LeaveType::create(['business_id' => $this->business->id, 'name' => 'Portal Scoping Leave ' . uniqid()]);

        $oldPeriod = LeavePeriod::create([
            'business_id' => $this->business->id, 'name' => 'Portal Old ' . uniqid(),
            'start_date' => '2020-01-01', 'end_date' => '2020-12-31', 'is_active' => false,
        ]);
        // Far in the future so it unambiguously outranks any real is_active
        // period already on the shared amsol business (e.g. "Leave 2026")
        // when ordered by start_date desc - a same-year clash there would
        // make the controller resolve someone else's "current" period
        // instead of this test's.
        $currentPeriod = LeavePeriod::create([
            'business_id' => $this->business->id, 'name' => 'Portal Current ' . uniqid(),
            'start_date' => '2099-01-01', 'end_date' => '2099-12-31',
            'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => $this->business->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $oldPeriod->id, 'entitled_days' => 21, 'accrued_days' => 21,
            'total_days' => 21, 'days_taken' => 5, 'days_remaining' => 16,
        ]);
        LeaveEntitlement::create([
            'business_id' => $this->business->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $currentPeriod->id, 'entitled_days' => 21, 'accrued_days' => 21,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-employee', '2fa_verified' => true])
            ->get(route('myaccount.index', $this->business->slug));

        $response->assertOk();
        $balances = collect($response->viewData('leave_balances'));

        $this->assertCount(1, $balances, 'Only the current period entitlement should show, not one row per period.');
        $this->assertSame(21.0, (float) $balances->first()['days_remaining']);
    }

    public function test_request_leave_form_excludes_deactivated_leave_types(): void
    {
        [$user] = $this->makeEmployeeUser();

        $activeType = LeaveType::create(['business_id' => $this->business->id, 'name' => 'Portal Active Type ' . uniqid(), 'is_active' => true]);
        $inactiveType = LeaveType::create(['business_id' => $this->business->id, 'name' => 'Portal Retired Type ' . uniqid(), 'is_active' => false]);

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-employee', '2fa_verified' => true])
            ->get(route('myaccount.leave.requests.create', $this->business->slug));

        $response->assertOk();
        $types = collect($response->viewData('leaveTypes'))->pluck('id');

        $this->assertTrue($types->contains($activeType->id));
        $this->assertFalse($types->contains($inactiveType->id));
    }
}
