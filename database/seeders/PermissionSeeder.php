<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * access.* permissions - a SEPARATE, narrower permission vocabulary from
 * ModuleActionPermissionSeeder's module.{module}.{action} set, checked
 * only by EnsureCorrectRole::getRequiredPermissionForRoute() as a third
 * gate for business-hr/restricted-hr/head-of-department/chief-of-staff
 * specifically (after that same middleware's restrictedRoutes block-list
 * gate). This file previously referenced business-it/business-marketing/
 * general-hr, three roles that turned out to have zero real route access
 * anywhere in the app and have since been removed - and was never
 * actually called from DatabaseSeeder, so business-hr and restricted-hr
 * have been running with ZERO access.* permissions despite passing every
 * earlier gate: EnsureCorrectRole's third check then 403s them off nearly
 * every page in the map below, including business-hr's own login-redirect
 * target. Excludes access.clients throughout - that's platform-governance
 * (business.clients.index), separately gated by a literal `role:
 * super-admin` check none of these roles hold, so granting it here would
 * just be misleading about what these roles can actually reach.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'access.dashboard', 'access.clients', 'access.locations', 'access.organization',
            'access.roles', 'access.employees', 'access.payroll', 'access.payroll-settings',
            'access.leave', 'access.attendance', 'access.performance', 'access.crm',
            'access.recruitment', 'access.profile', 'access.support',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $all = array_diff($permissions, ['access.clients']);

        // business-hr's restrictedRoutes entry in EnsureCorrectRole is
        // empty - no page-level block beyond this - so it gets the full
        // set, same as business-admin.
        $businessAdminPermissions = $all;
        $businessHrPermissions = $all;

        // Matches business-finance's actual route-group membership
        // (payroll-management, leave-management, employee-management,
        // attendance in ModuleActionPermissionSeeder) plus the
        // universally-needed dashboard/profile/support.
        $financePermissions = [
            'access.dashboard', 'access.payroll', 'access.payroll-settings',
            'access.leave', 'access.employees', 'access.attendance',
            'access.profile', 'access.support',
        ];

        // restricted-hr's own restrictedRoutes entry blocks every
        // payroll-family route specifically - everything else stays open.
        $restrictedHrPermissions = array_diff($all, ['access.payroll', 'access.payroll-settings']);

        $roles = [
            'business-admin' => $businessAdminPermissions,
            'business-hr' => $businessHrPermissions,
            'business-finance' => $financePermissions,
            'restricted-hr' => $restrictedHrPermissions,
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }
}
