<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Module;
use App\Models\OrganogramRole;
use App\Models\PayGrade;
use App\Models\PayrollFormula;
use App\Models\Shift;
use App\Models\User;
use App\Services\BusinessSetupProgressService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the "Getting Started" dashboard checklist - see
 * GUIDE.md. Every step's done-state must be detected live from real data,
 * never a manually-ticked flag, so a fresh business with nothing configured
 * yet must show every relevant step as pending.
 */
class BusinessSetupProgressTest extends TestCase
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

    private function makeBusiness(string $label): Business
    {
        return Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => "Setup Progress Test {$label} " . uniqid(),
            'slug' => 'setup-progress-test-' . strtolower($label) . '-' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'code' => strtoupper($label) . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);
    }

    private function stepsByKey(array $steps): array
    {
        return collect($steps)->keyBy('key')->all();
    }

    /**
     * A business that has never gone through module selection at all (no
     * business_modules rows) is grandfathered to full access everywhere
     * else in the app (Business::hasModule()'s docblock) - the guide
     * matches that: every optional-module step shows too, just pending,
     * rather than being hidden. Hiding them here would contradict what
     * the business can actually already reach.
     */
    public function test_brand_new_grandfathered_business_shows_every_step_as_pending(): void
    {
        $business = $this->makeBusiness('Fresh');
        $service = app(BusinessSetupProgressService::class);

        $steps = $this->stepsByKey($service->progressFor($business));

        $this->assertFalse($steps['organization_structure']['done']);
        $this->assertFalse($steps['departments']['done']);
        $this->assertFalse($steps['employees']['done']);
        $this->assertFalse($steps['leave']['done']);
        $this->assertFalse($steps['attendance']['done']);
        $this->assertArrayHasKey('payroll', $steps, 'Grandfathered (no module selection yet) businesses see every step.');
        $this->assertFalse($steps['payroll']['done']);
        $this->assertArrayHasKey('assets', $steps);
        $this->assertArrayHasKey('learning', $steps);
        $this->assertArrayHasKey('projects', $steps);
        $this->assertArrayHasKey('recruitment', $steps);
        $this->assertArrayHasKey('performance', $steps);
        $this->assertArrayHasKey('crm', $steps);
    }

    /**
     * Once a business has made ANY real module selection, it's no longer
     * grandfathered - from then on the guide only shows steps for modules
     * it actually picked, and it updates live as that selection changes.
     * This is the "dynamic with the modules the business has selected"
     * behavior directly.
     */
    public function test_guide_only_shows_steps_for_modules_the_business_actually_selected(): void
    {
        $business = $this->makeBusiness('Selective');
        $assetModule = Module::firstOrCreate(['name' => 'Asset Management'], ['description' => 'Test', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false]);
        $learningModule = Module::firstOrCreate(['name' => 'Learning Management'], ['description' => 'Test', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false]);
        $business->modules()->attach($assetModule->id, ['is_active' => true]);

        $service = app(BusinessSetupProgressService::class);
        $steps = $this->stepsByKey($service->progressFor($business->fresh()));

        $this->assertArrayHasKey('assets', $steps, 'Selected module should show its step.');
        $this->assertArrayNotHasKey('learning', $steps, 'Unselected module should not show its step.');
        $this->assertArrayNotHasKey('payroll', $steps, 'Unselected module should not show its step.');

        $business->modules()->attach($learningModule->id, ['is_active' => true]);
        $steps = $this->stepsByKey($service->progressFor($business->fresh()));
        $this->assertArrayHasKey('learning', $steps, 'Guide updates live once a module is added.');

        $business->modules()->updateExistingPivot($assetModule->id, ['is_active' => false]);
        $steps = $this->stepsByKey($service->progressFor($business->fresh()));
        $this->assertArrayNotHasKey('assets', $steps, 'Guide updates live once a module is deactivated too.');
    }

    public function test_organization_structure_step_flips_once_a_role_exists(): void
    {
        $business = $this->makeBusiness('OrgRole');
        OrganogramRole::create(['business_id' => $business->id, 'name' => 'Staff ' . uniqid(), 'level' => 1]);

        $service = app(BusinessSetupProgressService::class);
        $steps = $this->stepsByKey($service->progressFor($business));

        $this->assertTrue($steps['organization_structure']['done']);
    }

    public function test_departments_and_employees_steps_flip_independently(): void
    {
        $business = $this->makeBusiness('DeptEmp');
        $department = Department::create(['business_id' => $business->id, 'name' => 'Ops ' . uniqid()]);

        $service = app(BusinessSetupProgressService::class);
        $steps = $this->stepsByKey($service->progressFor($business));
        $this->assertTrue($steps['departments']['done']);
        $this->assertFalse($steps['employees']['done']);

        $user = User::factory()->create();
        Employee::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'department_id' => $department->id,
            'employee_code' => 'SP-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        $steps = $this->stepsByKey($service->progressFor($business));
        $this->assertTrue($steps['employees']['done']);
    }

    public function test_leave_setup_requires_all_four_sub_conditions(): void
    {
        $business = $this->makeBusiness('Leave');
        $service = app(BusinessSetupProgressService::class);

        $this->assertFalse($service->hasLeaveSetup($business));

        $leaveType = LeaveType::create(['business_id' => $business->id, 'name' => 'Annual ' . uniqid()]);
        $this->assertFalse($service->hasLeaveSetup($business));

        $period = LeavePeriod::create([
            'business_id' => $business->id, 'name' => 'FY ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $this->assertFalse($service->hasLeaveSetup($business), 'Still missing holidays/non-working-days and an entitlement.');

        $business->update(['non_working_days' => ['saturday', 'sunday']]);
        $this->assertFalse($service->hasLeaveSetup($business->fresh()), 'Still missing an entitlement.');

        $department = Department::create(['business_id' => $business->id, 'name' => 'Ops ' . uniqid()]);
        $user = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $user->id, 'business_id' => $business->id, 'department_id' => $department->id,
            'employee_code' => 'SP-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        LeaveEntitlement::create([
            'business_id' => $business->id, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'carryover_days' => 0, 'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $this->assertTrue($service->hasLeaveSetup($business->fresh()));
    }

    public function test_holidays_table_also_satisfies_the_holiday_sub_condition(): void
    {
        $business = $this->makeBusiness('Holiday');
        Holiday::create([
            'business_id' => $business->id, 'name' => 'New Year',
            'date' => '2026-01-01', 'is_recurring' => true,
        ]);

        $service = app(BusinessSetupProgressService::class);
        $this->assertTrue((new \ReflectionMethod($service, 'hasHolidaysConfigured'))->invoke($service, $business->fresh()));
    }

    public function test_attendance_setup_requires_both_a_shift_and_a_location(): void
    {
        $business = $this->makeBusiness('Attendance');
        $service = app(BusinessSetupProgressService::class);

        $this->assertFalse($service->hasAttendanceSetup($business));

        Shift::create(['business_id' => $business->id, 'name' => 'Day Shift ' . uniqid(), 'start_time' => '08:00', 'end_time' => '17:00']);
        $this->assertFalse($service->hasAttendanceSetup($business));

        Location::create(['business_id' => $business->id, 'name' => 'HQ ' . uniqid()]);
        $this->assertTrue($service->hasAttendanceSetup($business));
    }

    public function test_payroll_step_only_appears_when_the_payroll_module_is_active(): void
    {
        $business = $this->makeBusiness('Payroll');
        $module = Module::firstOrCreate(
            ['name' => 'Payroll Management'],
            ['description' => 'Test', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false]
        );
        // Give the business some other, unrelated module first so it's no
        // longer grandfathered (see test_brand_new_grandfathered_business...)
        // and this test genuinely exercises "selected X, not Y" rather than
        // "selected nothing yet".
        $otherModule = Module::firstOrCreate(['name' => 'Asset Management'], ['description' => 'Test', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false]);
        $business->modules()->attach($otherModule->id, ['is_active' => true]);

        $service = app(BusinessSetupProgressService::class);
        $this->assertArrayNotHasKey('payroll', $this->stepsByKey($service->progressFor($business->fresh())));

        $business->modules()->attach($module->id, ['is_active' => true]);

        $steps = $this->stepsByKey($service->progressFor($business->fresh()));
        $this->assertArrayHasKey('payroll', $steps);
        $this->assertFalse($steps['payroll']['done']);

        $department = Department::create(['business_id' => $business->id, 'name' => 'Finance ' . uniqid()]);
        PayGrade::create(['business_id' => $business->id, 'name' => 'Grade A ' . uniqid(), 'department_id' => $department->id, 'amount' => 50000]);
        PayrollFormula::create(['business_id' => $business->id, 'name' => 'Standard ' . uniqid()]);

        $steps = $this->stepsByKey($service->progressFor($business->fresh()));
        $this->assertTrue($steps['payroll']['done']);
    }

    public function test_dismiss_and_reopen_endpoints_persist_on_the_business(): void
    {
        $business = $this->makeBusiness('Dismiss');
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));

        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('business.setup-progress.dismiss', $business->slug))
            ->assertOk();

        $this->assertNotNull($business->fresh()->setup_guide_dismissed_at);

        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('business.setup-progress.reopen', $business->slug))
            ->assertOk();
        $this->assertNull($business->fresh()->setup_guide_dismissed_at);
    }
}
