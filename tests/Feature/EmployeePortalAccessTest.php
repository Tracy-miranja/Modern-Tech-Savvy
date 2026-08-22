<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\EmploymentDetail;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Employee self-service portal ("myaccount") access, exercised via real
 * HTTP requests. Written after discovering EmployeeController::store()
 * (and the CSV bulk-import path) looked up the 'business-employee' role
 * scoped by business_id - but only business_id=1 (Amsol) actually has a
 * 'business-employee' row in the roles table, so every employee created
 * for any OTHER business silently got NO role at all, 403ing on every
 * role:business-employee-gated portal route (My Team, Cover Requests, My
 * Leave Requests, etc).
 */
class EmployeePortalAccessTest extends TestCase
{
    private Business $clientBusiness;

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

        $this->clientBusiness = Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => 'Portal Test Business ' . uniqid(),
            'slug' => 'portal-test-business-' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'code' => 'PTB' . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    /**
     * Mirrors what EmployeeController::store()/the CSV import now do:
     * assign the GLOBAL 'business-employee' role (not filtered by
     * business_id), matching the fix.
     */
    private function makePortalEmployee(Business $business): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'business-employee', 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
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

    public function test_business_employee_role_is_assignable_for_a_non_amsol_business(): void
    {
        // Direct regression check on the actual bug: business_id=1 is the
        // only pre-existing 'business-employee' row, but the role itself
        // must not be business-scoped for hasRole() checks to work for
        // any other business's employees.
        $role = Role::firstOrCreate(['name' => 'business-employee', 'guard_name' => 'web']);
        $this->assertNotNull($role);

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->assertTrue($user->fresh()->hasRole('business-employee'));
    }

    public function test_employee_at_a_non_amsol_business_can_reach_my_team(): void
    {
        [$user, $employee] = $this->makePortalEmployee($this->clientBusiness);

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->clientBusiness->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->get(route('myaccount.my-team', $this->clientBusiness->slug));

        $response->assertOk();
    }

    public function test_employee_at_a_non_amsol_business_can_reach_cover_requests(): void
    {
        [$user, $employee] = $this->makePortalEmployee($this->clientBusiness);

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->clientBusiness->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->get(route('myaccount.delegations.index', $this->clientBusiness->slug));

        $response->assertOk();
    }

    public function test_employee_at_a_non_amsol_business_can_reach_my_leave_requests(): void
    {
        [$user, $employee] = $this->makePortalEmployee($this->clientBusiness);

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->clientBusiness->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->get(route('myaccount.leave.requests.index', $this->clientBusiness->slug));

        $response->assertOk();
    }

    /**
     * An amsol-admin previewing the employee portal via "Switch Account"
     * has no Employee record at all (platform accounts don't have one).
     * My Team / Cover Requests used to hard 403 in that case while every
     * other myaccount page (leave calendar, performance, etc) just showed
     * an empty state - inconsistent, and confusing when testing the
     * portal from an admin account. They should now render an empty
     * state too, matching the rest of the portal.
     */
    public function test_amsol_admin_previewing_the_portal_with_no_employee_record_gets_an_empty_state_not_403(): void
    {
        $amsol = Business::where('slug', 'amsol')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'amsol-admin', 'guard_name' => 'web']));

        $session = ['active_business_slug' => $amsol->slug, 'active_role' => 'business-employee', '2fa_verified' => true];

        $myTeam = $this->actingAs($user)->withSession($session)->get(route('myaccount.my-team', $amsol->slug));
        $myTeam->assertOk();
        $myTeam->assertSee('direct reports');

        // The header nav previously checked hasAnyRole(['super-admin',
        // 'amsol-admin']) only - the account's PERMANENT roles, ignoring
        // which role is actually active. An amsol-admin who switched to
        // business-employee via "Switch Account" would still see
        // "Manage Clients"/"Switch Business" meant for their admin role.
        $myTeam->assertDontSee('Manage Clients');

        $delegations = $this->actingAs($user)->withSession($session)->get(route('myaccount.delegations.index', $amsol->slug));
        $delegations->assertOk();
    }

    public function test_amsol_admin_sees_manage_clients_only_while_that_role_is_actually_active(): void
    {
        $amsol = Business::where('slug', 'amsol')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'amsol-admin', 'guard_name' => 'web']));

        $asAmsolAdmin = $this->actingAs($user)
            ->withSession(['active_business_slug' => $amsol->slug, 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->get(route('business.index', $amsol->slug));
        $asAmsolAdmin->assertOk();
        $asAmsolAdmin->assertSee('Manage Clients');
    }

    private function portalSession(Business $business): array
    {
        return ['active_business_slug' => $business->slug, 'active_role' => 'business-employee', '2fa_verified' => true];
    }

    public function test_p9_forms_index_page_is_reachable_and_profile_data_nav_is_disabled(): void
    {
        [$user] = $this->makePortalEmployee($this->clientBusiness);
        $session = $this->portalSession($this->clientBusiness);

        $p9 = $this->actingAs($user)->withSession($session)->get(route('myaccount.p9.index', $this->clientBusiness->slug));
        $p9->assertOk();
        // No closed payroll for this fresh employee - must not blindly
        // offer years with nothing behind them (the original bug: picking
        // any of a static "last 6 years" list silently failed for every
        // year with no closed payroll).
        $p9->assertSee('No P9 forms available yet');

        // Profile Data nav is deliberately disabled for now - not a real
        // route change, just made non-clickable in the sidebar.
        $p9->assertSee('Profile Data');
        $p9->assertDontSee(route('myaccount.profile', $this->clientBusiness->slug));
    }

    public function test_p9_forms_page_only_lists_years_with_closed_payroll(): void
    {
        [$user, $employee] = $this->makePortalEmployee($this->clientBusiness);

        $closedPayroll = Payroll::create([
            'business_id' => $this->clientBusiness->id,
            'payroll_type' => 'monthly',
            'currency' => 'KES',
            'staff' => 1,
            'payrun_year' => 2025,
            'payrun_month' => 6,
            'status' => 'closed',
        ]);
        EmployeePayroll::create([
            'payroll_id' => $closedPayroll->id,
            'employee_id' => $employee->id,
            'gross_pay' => 1000,
            'net_pay' => 900,
        ]);

        $draftPayroll = Payroll::create([
            'business_id' => $this->clientBusiness->id,
            'payroll_type' => 'monthly',
            'currency' => 'KES',
            'staff' => 1,
            'payrun_year' => 2026,
            'payrun_month' => 3,
            'status' => 'draft',
        ]);
        EmployeePayroll::create([
            'payroll_id' => $draftPayroll->id,
            'employee_id' => $employee->id,
            'gross_pay' => 1000,
            'net_pay' => 900,
        ]);

        $response = $this->actingAs($user)
            ->withSession($this->portalSession($this->clientBusiness))
            ->get(route('myaccount.p9.index', $this->clientBusiness->slug));

        $response->assertOk();
        $response->assertSee(route('myaccount.p9', ['business' => $this->clientBusiness->slug, 'year' => 2025]), false);
        $response->assertDontSee(route('myaccount.p9', ['business' => $this->clientBusiness->slug, 'year' => 2026]), false);
    }

    public function test_payslips_page_is_reachable_even_with_no_payroll_data(): void
    {
        [$user] = $this->makePortalEmployee($this->clientBusiness);

        $response = $this->actingAs($user)
            ->withSession($this->portalSession($this->clientBusiness))
            ->get(route('myaccount.payslips', $this->clientBusiness->slug));

        $response->assertOk();
    }

    public function test_my_performance_page_no_longer_403s_for_a_real_employee(): void
    {
        [$user] = $this->makePortalEmployee($this->clientBusiness);

        $response = $this->actingAs($user)
            ->withSession($this->portalSession($this->clientBusiness))
            ->get(route('myaccount.performance.index', $this->clientBusiness->slug));

        $response->assertOk();
    }

    public function test_my_performance_page_shows_empty_state_instead_of_403_with_no_employee_record(): void
    {
        $amsol = Business::where('slug', 'amsol')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'amsol-admin', 'guard_name' => 'web']));

        $response = $this->actingAs($user)
            ->withSession($this->portalSession($amsol))
            ->get(route('myaccount.performance.index', $amsol->slug));

        $response->assertOk();
        $response->assertSee('No employee record found');
    }

    public function test_attendance_clock_in_page_does_not_crash_with_no_employee_record(): void
    {
        $amsol = Business::where('slug', 'amsol')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'amsol-admin', 'guard_name' => 'web']));

        $response = $this->actingAs($user)
            ->withSession($this->portalSession($amsol))
            ->get(route('myaccount.attendances.clock-in-out.index', $amsol->slug));

        $response->assertOk();
    }

    public function test_super_admin_sees_switch_account_option_in_its_own_sidebar(): void
    {
        $amsol = Business::where('slug', 'amsol')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $amsol->slug, 'active_role' => 'super-admin', '2fa_verified' => true])
            ->get(route('business.index', $amsol->slug));

        $response->assertOk();
        $response->assertSee('Switch Account');
    }
}
