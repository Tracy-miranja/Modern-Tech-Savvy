<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

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
    }
}
