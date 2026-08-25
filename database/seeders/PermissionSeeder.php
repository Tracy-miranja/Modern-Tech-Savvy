<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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

        $businessAdminPermissions = $all;
        $businessHrPermissions = $all;

        $financePermissions = [
            'access.dashboard', 'access.payroll', 'access.payroll-settings',
            'access.leave', 'access.employees', 'access.attendance',
            'access.profile', 'access.support',
        ];

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
