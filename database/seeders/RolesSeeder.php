<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {

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

        $deleteLeadPermission = Permission::firstOrCreate(['name' => 'delete-lead', 'guard_name' => 'web']);
        $deleteContactSubmissionPermission = Permission::firstOrCreate(['name' => 'delete-contact-submission', 'guard_name' => 'web']);

        $accessLeavePermission = Permission::firstOrCreate(['name' => 'access.leave', 'guard_name' => 'web']);
        $accessDashboardPermission = Permission::firstOrCreate(['name' => 'access.dashboard', 'guard_name' => 'web']);
        $accessEmployeesPermission = Permission::firstOrCreate(['name' => 'access.employees', 'guard_name' => 'web']);

        $adminRole = Role::findByName('admin', 'web');
        $adminRole->givePermissionTo([$deleteLeadPermission, $deleteContactSubmissionPermission]);

        $chiefOfStaffRole = Role::findByName('chief-of-staff', 'web');
        $chiefOfStaffRole->givePermissionTo([$accessLeavePermission, $accessDashboardPermission, $accessEmployeesPermission]);

        $headOfDepartmentRole = Role::findByName('head-of-department', 'web');
        $headOfDepartmentRole->givePermissionTo([$accessLeavePermission, $accessDashboardPermission, $accessEmployeesPermission]);
    }
}
