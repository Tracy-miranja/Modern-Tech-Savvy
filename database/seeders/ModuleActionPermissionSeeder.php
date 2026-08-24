<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds one Permission row per (module, action) pair, named
 * "module.{module_slug}.{action}" - the vocabulary businesses' own custom
 * roles (see App\Models\Role's is_custom/display_name) are built from,
 * checked via Spatie's role_or_permission middleware alongside the fixed
 * platform roles (see the Leave routes for the first wired example).
 *
 * Deliberately its own module list, not a 1:1 mirror of the `modules`
 * table (App\Models\Module - that's business SUBSCRIPTION gating, a
 * totally separate concern from who-can-do-what within a module a
 * business already has active). This list is the nav-facing areas a
 * business actually recognizes; extend it here (no new migration needed)
 * as more modules get real permission enforcement.
 *
 * Idempotent - firstOrCreate throughout, safe to re-run.
 */
class ModuleActionPermissionSeeder extends Seeder
{
    public const MODULES = [
        'leave-management',
        'attendance',
        'employee-management',
        'organization-structure',
        'performance-management',
        'payroll-management',
        'recruitment-onboarding',
        'asset-management',
        'learning-management',
        'project-management',
        'crm-integration',
    ];

    public const ACTIONS = ['view', 'create', 'edit', 'delete', 'approve'];

    /**
     * Which real, subscribable Module (App\Models\Module::slug, the
     * Business::hasModule() gate) governs each of the finer-grained slugs
     * above. Most match 1:1; a few of these nav-facing areas (leave,
     * attendance, employee records, org structure) are all bundled under
     * the single 'core-hr-management'/'time-attendance' subscriptions
     * rather than being separately subscribable - RoleController consults
     * this, never App\Models\Module directly, when deciding whether a
     * permission is grantable for a business's current subscription.
     */
    public const MODULE_SUBSCRIPTION_GATE = [
        'leave-management' => 'core-hr-management',
        'attendance' => 'time-attendance',
        'employee-management' => 'core-hr-management',
        'organization-structure' => 'core-hr-management',
        'performance-management' => 'performance-management',
        'payroll-management' => 'payroll-management',
        'recruitment-onboarding' => 'recruitment-onboarding',
        'asset-management' => 'asset-management',
        'learning-management' => 'learning-management',
        'project-management' => 'project-management',
        'crm-integration' => 'crm-integration',
    ];

    /**
     * Which modules each fixed/built-in role is granted, purely so Roles
     * Management has something real to show instead of "no permissions
     * assigned" (these roles were never permission-driven - actual access
     * is decided by role NAME checks in routes/web.php's
     * role_or_permission_or_impersonation:... lists, this just mirrors
     * that same list back as real Permission grants).
     *
     * head-of-department, restricted-hr, and chief-of-staff are listed
     * here at the same route-group breadth as business-admin/business-hr,
     * but EnsureCorrectRole::restrictedRoutes then blocks most of the
     * individual pages within these modules for those three specifically
     * (payroll/CRM/recruitment/org-setup/employees list, etc.) - that
     * page-level layer isn't modeled as permissions here, so what's shown
     * for these three is closer to "nominally has this module" than
     * "can freely use every page in it".
     */
    private const ROLE_MODULES = [
        'business-admin' => self::MODULES,
        'business-hr' => self::MODULES,
        'head-of-department' => self::MODULES,
        'restricted-hr' => self::MODULES,
        'chief-of-staff' => self::MODULES,
        'business-finance' => ['payroll-management', 'leave-management', 'employee-management', 'attendance'],
    ];

    public function run(): void
    {
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                Permission::firstOrCreate([
                    'name' => "module.{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        foreach (self::ROLE_MODULES as $roleName => $modules) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                continue;
            }

            $permissionNames = collect($modules)
                ->flatMap(fn ($module) => collect(self::ACTIONS)->map(fn ($action) => "module.{$module}.{$action}"))
                ->all();

            $role->givePermissionTo($permissionNames);
        }
    }
}
