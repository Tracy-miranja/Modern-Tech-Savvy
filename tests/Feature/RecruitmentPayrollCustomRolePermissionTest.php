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
 * Proves the business.recruitment, business.applications,
 * business.applicants, and business.payroll route groups now accept a
 * business-defined custom role holding the matching module.{slug}.{action}
 * permission, not just the fixed platform roles - same
 * role_or_permission_or_impersonation pattern proven on the earlier
 * modules, rolled out here as the next batch of follow-up work.
 */
class RecruitmentPayrollCustomRolePermissionTest extends TestCase
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

    private function makeCustomRoleUser(array $permissionNames): array
    {
        $displayName = 'Test Custom Role ' . uniqid();
        $role = Role::create([
            'name' => Role::generateUniqueName($this->business->id, $displayName),
            'display_name' => $displayName,
            'guard_name' => 'web',
            'business_id' => $this->business->id,
            'is_custom' => true,
        ]);
        $role->syncPermissions($permissionNames);

        $user = User::factory()->create();
        $user->assignRole($role);

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => 1,
            'employee_code' => 'RECRUITPAYROLE-' . uniqid(),
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

        return [$user->fresh(), $role];
    }

    private function assertRoleReaches(string $permission, string $routeName): void
    {
        [$user, $role] = $this->makeCustomRoleUser([$permission]);

        $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->get(route($routeName, $this->business->slug))
            ->assertOk();
    }

    private function assertRoleBlocked(string $routeName): void
    {
        [$user, $role] = $this->makeCustomRoleUser(['module.leave-management.view']);

        $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->getJson(route($routeName, $this->business->slug))
            ->assertStatus(403);
    }

    public function test_a_custom_role_holder_with_the_view_permission_can_reach_applicants(): void
    {
        $this->assertRoleReaches('module.recruitment-onboarding.view', 'business.applicants.index');
    }

    public function test_a_custom_role_holder_without_the_permission_cannot_reach_applicants(): void
    {
        $this->assertRoleBlocked('business.applicants.index');
    }

    public function test_a_custom_role_holder_with_the_view_permission_can_reach_payroll(): void
    {
        $this->assertRoleReaches('module.payroll-management.view', 'business.payroll.index');
    }

    public function test_a_custom_role_holder_without_the_permission_cannot_reach_payroll(): void
    {
        $this->assertRoleBlocked('business.payroll.index');
    }
}
