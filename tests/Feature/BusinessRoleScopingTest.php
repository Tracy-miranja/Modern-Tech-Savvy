<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Two related fixes, both about a business never being able to see/act on
 * anything above its own scope:
 *
 * 1. Roles Management (business.roles.*) and roles/fetch previously listed
 *    EVERY Spatie role including super-admin/amsol-admin - a business-admin
 *    could see (and, via roles/assign, actually grant) platform-governance
 *    roles to one of their own employees. Role::businessAssignable() now
 *    excludes super-admin/amsol-admin/applicant everywhere a business picks
 *    from the role list.
 *
 * 2. restricted-hr's payroll block only covered 'business.payroll.index'
 *    and a non-existent 'business.payroll-settings' route name - every
 *    other payroll-family route (advances, loans, payroll-formulas,
 *    reliefs, deductions, allowances, pay-grades) was reachable. The
 *    restricted list in EnsureCorrectRole now covers the full set.
 */
class BusinessRoleScopingTest extends TestCase
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

    private function makeEmployeeUser(string $role): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => 1,
            'employee_code' => 'SCOPE-' . uniqid(),
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

    public function test_roles_list_excludes_platform_and_applicant_roles(): void
    {
        [$admin] = $this->makeEmployeeUser('business-admin');

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('roles.fetch'));

        $response->assertOk();
        $html = $response->json('data');

        $this->assertStringNotContainsString('super-admin', $html);
        $this->assertStringNotContainsString('amsol-admin', $html);
        $this->assertStringNotContainsString('>applicant<', $html);
        $this->assertStringContainsString('business-hr', $html);
    }

    public function test_assigning_a_platform_role_via_roles_assign_is_rejected(): void
    {
        [$admin] = $this->makeEmployeeUser('business-admin');
        [, $targetEmployee] = $this->makeEmployeeUser('business-employee');

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('roles.assign'), [
                'role_id' => $superAdminRole->id,
                'user_id' => $targetEmployee->user_id,
            ]);

        $response->assertForbidden();
        $this->assertFalse($targetEmployee->user->fresh()->hasRole('super-admin'));
    }

    public function test_restricted_hr_can_reach_employees_but_not_the_general_dashboard(): void
    {
        [$user] = $this->makeEmployeeUser('restricted-hr');
        $session = ['active_business_slug' => $this->business->slug, 'active_role' => 'restricted-hr', '2fa_verified' => true];

        // access.dashboard is deliberately excluded for restricted-hr in
        // PermissionSeeder ("All except dashboard and payroll-related") -
        // not a bug, so business.index staying blocked is expected. Full
        // page loads now redirect to somewhere restricted-hr DOES have
        // access to (RoleHomeRouteService) instead of a bare 403.
        $this->actingAs($user)
            ->withSession($session)
            ->get(route('business.index', $this->business->slug))
            ->assertRedirect(route('business.employees.index', $this->business->slug));

        // But they were previously blocked from EVERYTHING, including
        // pages they do hold access.* permission for, because restricted-hr
        // was missing from the base role: middleware entirely.
        $this->actingAs($user)
            ->withSession($session)
            ->get(route('business.employees.index', $this->business->slug))
            ->assertOk();
    }

    public function test_restricted_hr_is_blocked_from_the_full_payroll_route_family(): void
    {
        [$user] = $this->makeEmployeeUser('restricted-hr');

        $session = ['active_business_slug' => $this->business->slug, 'active_role' => 'restricted-hr', '2fa_verified' => true];

        foreach ([
            'business.payroll.index',
            'business.payroll.all',
            'business.advances.index',
            'business.loans.index',
            'business.employee-reliefs.index',
            'business.payroll-formulas.index',
            'business.reliefs.index',
            'business.deductions',
            'business.allowances.index',
            'business.pay-grades.index',
        ] as $routeName) {
            $this->actingAs($user)
                ->withSession($session)
                ->get(route($routeName, $this->business->slug))
                ->assertRedirect(route('business.employees.index', $this->business->slug));
        }
    }

    private function makeLoginableEmployeeUser(string $role, string $password = 'Password123!'): array
    {
        $user = User::factory()->create(['password' => bcrypt($password)]);
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => 1,
            'employee_code' => 'LOGIN-' . uniqid(),
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

    /**
     * business-hr requires 2FA (User::requiresTwoFactorAuthentication());
     * previously they'd land on business.index after verifying the code
     * and get 403'd immediately, since business-hr's permission set
     * deliberately excludes access.dashboard.
     */
    public function test_business_hr_login_lands_somewhere_they_actually_have_permission_for(): void
    {
        [$user] = $this->makeLoginableEmployeeUser('business-hr');

        $loginResponse = $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123!']);
        $loginResponse->assertOk();
        $loginResponse->assertJsonPath('data.redirect_url', route('2fa.verify'));

        $code = DB::table('two_factor_codes')->where('user_id', $user->id)->value('code');
        $verifyResponse = $this->post('/2fa/verify', ['code' => $code]);
        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('data.redirect_url', route('business.employees.index', $this->business->slug));

        $this->get($verifyResponse->json('data.redirect_url'))->assertOk();
    }

    public function test_restricted_hr_head_of_department_and_chief_of_staff_no_longer_dead_end_at_login(): void
    {
        foreach (['restricted-hr', 'head-of-department', 'chief-of-staff'] as $role) {
            [$user] = $this->makeLoginableEmployeeUser($role);

            $loginResponse = $this->post(route('login'), ['email' => $user->email, 'password' => 'Password123!']);
            $loginResponse->assertOk();

            $redirectUrl = $loginResponse->json('data.redirect_url');
            $this->assertNotSame(route('login'), $redirectUrl, "{$role} login redirected back to /login instead of landing somewhere usable.");

            $this->get($redirectUrl)->assertOk();

            // RedirectIfAuthenticated guards the login route - without
            // logging out, the next iteration's login POST would 302 away
            // before ever reaching the controller.
            $this->post(route('logout'));
        }
    }

    /**
     * Regression for: business.index (the dashboard landing page - every
     * business role's actual "home" after login) was for a while registered
     * ONLY under role:super-admin|amsol-admin, in its own group split out
     * from the business-admin|business-hr|... group elsewhere in the file.
     * Since a URI can only ever be served by ONE route registration, that
     * silently made it completely unreachable by business-admin (or any
     * other business role) - a fresh business-admin got "Unauthorized:
     * User does not have the required role" on their OWN dashboard, and
     * once 403s started redirecting to each role's own home (also
     * business.index for business-admin), that became an infinite redirect
     * loop instead of a plain error. Granting the account amsol-admin
     * "fixed" it only because amsol-admin happened to be on the one
     * surviving registration's role list - never because it was actually
     * needed.
     */
    public function test_plain_business_admin_can_reach_their_own_dashboard(): void
    {
        // business-admin's redirect resolves via businesses OWNED
        // (User::business/businesses()), not an Employee record - a
        // dedicated owned business, not $this->business (amsol, owned by
        // a different account), is what actually exercises that path.
        $password = 'Password123!';
        $user = User::factory()->create(['password' => bcrypt($password)]);
        $user->setStatus(\App\Enum\Status::ACTIVE);
        $user->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));
        $ownBusiness = Business::create([
            'user_id' => $user->id,
            'company_name' => 'Scoping Test Business ' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '07' . random_int(10000000, 99999999),
            'code' => strtoupper('SC') . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);

        $loginResponse = $this->post(route('login'), ['email' => $user->email, 'password' => $password]);
        $loginResponse->assertOk();
        $loginResponse->assertJsonPath('data.redirect_url', route('2fa.verify'));

        $code = DB::table('two_factor_codes')->where('user_id', $user->id)->value('code');
        $verifyResponse = $this->post('/2fa/verify', ['code' => $code]);
        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('data.redirect_url', route('business.index', $ownBusiness->slug));

        $this->get($verifyResponse->json('data.redirect_url'))->assertOk();

        // Confirm this isn't just amsol-admin/super-admin sneaking through -
        // a role with no business.index access at all still gets the
        // documented redirect-to-somewhere-else, not looped back here.
        $this->post(route('logout'));
        [$restrictedUser] = $this->makeLoginableEmployeeUser('restricted-hr');
        $this->actingAs($restrictedUser)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'restricted-hr', '2fa_verified' => true])
            ->get(route('business.index', $this->business->slug))
            ->assertRedirect(route('business.employees.index', $this->business->slug));
    }
}
