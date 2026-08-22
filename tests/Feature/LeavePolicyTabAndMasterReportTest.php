<?php

namespace Tests\Feature;

use App\Http\Controllers\LeavePolicyController;
use App\Http\Controllers\LeaveReportController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Leave Policies tab (consolidated read-only view of every policy applied
 * across the business) and the Leave Master Report (per-employee,
 * per-leave-type full entitlement breakdown - entitled/accrued/carryover/
 * adjustment/used/pending/remaining in one place).
 */
class LeavePolicyTabAndMasterReportTest extends TestCase
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

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'LPMR-' . uniqid(),
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

        return $employee->fresh();
    }

    public function test_leave_policies_fetch_lists_every_policy_for_the_business(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Policy Tab Leave ' . uniqid()]);
        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 18,
            'accrual_frequency' => 'monthly', 'accrual_amount' => 1.5,
            'max_carryover_days' => 6, 'carryover_type' => 'fixed', 'carryover_value' => 3,
            'carryover_expiry_months' => 2, 'min_interval_days' => 5,
            'minimum_service_days_required' => 0, 'effective_date' => '2020-01-01', 'is_active' => true,
        ]);

        $controller = new LeavePolicyController();
        $response = $controller->fetch(Request::create('/x', 'POST'))->toResponse(Request::create('/x'));

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $html = $payload['data'];

        $this->assertStringContainsString($leaveType->name, $html);
        $this->assertStringContainsString('3.00 days', $html); // carryover_value for 'fixed' (decimal:2 cast)
        $this->assertStringContainsString('2 month(s)', $html); // carryover_expiry_months
        $this->assertStringContainsString('5 day(s)', $html); // min_interval_days
    }

    public function test_leave_policies_fetch_filters_by_leave_type_id(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $typeA = LeaveType::create(['business_id' => 1, 'name' => 'Policy Filter Leave A ' . uniqid()]);
        $typeB = LeaveType::create(['business_id' => 1, 'name' => 'Policy Filter Leave B ' . uniqid()]);

        foreach ([$typeA, $typeB] as $type) {
            LeavePolicy::create([
                'leave_type_id' => $type->id, 'gender_applicable' => 'all',
                'prorated_for_new_employees' => false, 'default_days' => 15,
                'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
                'max_carryover_days' => 0, 'minimum_service_days_required' => 0,
                'effective_date' => '2020-01-01', 'is_active' => true,
            ]);
        }

        $controller = new LeavePolicyController();
        $response = $controller->fetch(Request::create('/x', 'POST', ['leave_type_id' => $typeA->id]))
            ->toResponse(Request::create('/x'));
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($typeA->name, $html);
        $this->assertStringNotContainsString($typeB->name, $html);
    }

    public function test_leave_policies_fetch_scopes_to_the_current_open_periods_date_range(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $currentPeriod = LeavePeriod::currentlyOpenFor(1);
        $this->assertNotNull($currentPeriod, 'Business 1 is expected to have a current open leave period.');

        // Effective later this same open period but AFTER today - a naive
        // "effective as of today" query (the pre-fix behaviour) would have
        // missed this row entirely; scoping to the period's end date catches it.
        $futureEffectiveDate = now()->addDays(5)->lt($currentPeriod->end_date)
            ? now()->addDays(5)->toDateString()
            : $currentPeriod->end_date->toDateString();

        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Policy Scope Leave ' . uniqid()]);
        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 12,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 0, 'minimum_service_days_required' => 0,
            'effective_date' => $futureEffectiveDate, 'is_active' => true,
        ]);

        $controller = new LeavePolicyController();
        $response = $controller->fetch(Request::create('/x', 'POST'))->toResponse(Request::create('/x'));
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($leaveType->name, $html);
        $this->assertStringContainsString($currentPeriod->name, $html);
    }

    public function test_master_report_shows_the_full_entitlement_breakdown_per_employee_per_type(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Master Report Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Master Report Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'carryover_days' => 4, 'adjustment_days' => 1,
            'total_days' => 26, 'days_taken' => 3, 'days_pending' => 2, 'days_remaining' => 21,
        ]);

        $controller = new LeaveReportController();
        $html = $controller->masterPreview(Request::create('/x', 'GET', ['leave_period_id' => $period->id]), $business);

        $this->assertStringContainsString('Leave Master Report', $html);
        $this->assertStringContainsString($employee->user->name, $html);
        $this->assertStringContainsString($leaveType->name, $html);
    }
}
