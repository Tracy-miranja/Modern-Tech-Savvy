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
 * Proves the business.assets.*, business.learning.*, and business.projects.*
 * route groups now accept a business-defined custom role holding the
 * matching module.{slug}.{action} permission, not just the fixed platform
 * roles - same role_or_permission_or_impersonation pattern proven on
 * Leave/Organization Structure/Employee Management/Attendance/Performance,
 * rolled out here for the three module-gated (ensure_module:X) route
 * groups as the next batch of follow-up work. Each still requires the
 * business to have that module actually subscribed (ensure_module is
 * unchanged, nested inside the new outer gate) - not exercised here since
 * ModuleGatingAndAssetsTest already covers that independently.
 */
class AssetLearningProjectCustomRolePermissionTest extends TestCase
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

        $this->business = Business::find(1); // amsol - has every module subscribed
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
            'employee_code' => 'MODROLE-' . uniqid(),
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

    public function test_a_custom_role_holder_with_the_view_permission_can_reach_assets(): void
    {
        $this->assertRoleReaches('module.asset-management.view', 'business.assets.index');
    }

    public function test_a_custom_role_holder_without_the_permission_cannot_reach_assets(): void
    {
        $this->assertRoleBlocked('business.assets.index');
    }

    public function test_a_custom_role_holder_with_the_view_permission_can_reach_learning(): void
    {
        $this->assertRoleReaches('module.learning-management.view', 'business.learning.index');
    }

    public function test_a_custom_role_holder_without_the_permission_cannot_reach_learning(): void
    {
        $this->assertRoleBlocked('business.learning.index');
    }

    public function test_a_custom_role_holder_with_the_view_permission_can_reach_projects(): void
    {
        $this->assertRoleReaches('module.project-management.view', 'business.projects.index');
    }

    public function test_a_custom_role_holder_without_the_permission_cannot_reach_projects(): void
    {
        $this->assertRoleBlocked('business.projects.index');
    }
}
