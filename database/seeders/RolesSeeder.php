<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        // business-it, business-marketing, and general-hr were removed -
        // none of the three ever appeared in a single route's access
        // control (routes/web.php's role_or_permission_or_impersonation:
        // lists), the login redirect (AuthenticatedSessionController::
        // getRedirectUrlForRole()) had no branch for any of them, and
        // business-it's only permissions (delete-lead/delete-contact-
        // submission below) were never actually checked anywhere. An
        // employee assigned any of the three could log in and then reach
        // nothing - not a role, a dead end.
        $roles = [
            'super-admin',
            'admin',
            'business-admin',
            'business-hr',
            'business-finance',
            'business-employee',
            'restricted-hr',
            'head-of-department',
            'chief-of-staff'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create permissions
        $deleteLeadPermission = Permission::firstOrCreate(['name' => 'delete-lead', 'guard_name' => 'web']);
        $deleteContactSubmissionPermission = Permission::firstOrCreate(['name' => 'delete-contact-submission', 'guard_name' => 'web']);

        // Create access permissions
        $accessLeavePermission = Permission::firstOrCreate(['name' => 'access.leave', 'guard_name' => 'web']);
        $accessDashboardPermission = Permission::firstOrCreate(['name' => 'access.dashboard', 'guard_name' => 'web']);
        $accessEmployeesPermission = Permission::firstOrCreate(['name' => 'access.employees', 'guard_name' => 'web']);

        // Assign permissions to 'admin' role
        $adminRole = Role::findByName('admin', 'web');
        $adminRole->givePermissionTo([$deleteLeadPermission, $deleteContactSubmissionPermission]);

        // Assign permissions to chief-of-staff role
        $chiefOfStaffRole = Role::findByName('chief-of-staff', 'web');
        $chiefOfStaffRole->givePermissionTo([$accessLeavePermission, $accessDashboardPermission, $accessEmployeesPermission]);

        // Assign permissions to head-of-department role
        $headOfDepartmentRole = Role::findByName('head-of-department', 'web');
        $headOfDepartmentRole->givePermissionTo([$accessLeavePermission, $accessDashboardPermission, $accessEmployeesPermission]);
    }
}
