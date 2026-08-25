<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

    public const MODULE_HOME_ROUTES = [
        'leave-management' => 'business.leave.index',
        'attendance' => 'business.attendances.index',
        'employee-management' => 'business.employees.index',
        'organization-structure' => 'business.organization-structure.index',
        'performance-management' => 'business.performance.tasks.index',
        'payroll-management' => 'business.payroll.index',
        'recruitment-onboarding' => 'business.recruitment.jobs.index',
        'asset-management' => 'business.assets.index',
        'learning-management' => 'business.learning.index',
        'project-management' => 'business.projects.index',
        'crm-integration' => 'business.crm.contacts.index',
    ];

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
