<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(ModulesSeeder::class);
        // Without this, every module.{module}.{action} permission is
        // simply missing - Role::syncPermissions() (Roles Management ->
        // create/edit a custom role) throws Spatie's PermissionDoesNotExist
        // the moment any of them is checked, and every module-gated route
        // (role_or_permission_or_impersonation:...|module.leave-management.view|...)
        // fails the same way. Global reference data, firstOrCreate
        // throughout - always safe to (re-)run.
        $this->call(ModuleActionPermissionSeeder::class);
        $this->call(ShiftSeeder::class);
        // JobCategorySeeder/DepartmentSeeder stay commented out deliberately:
        // Krest already has its own real, curated departments and job
        // categories (added through the app itself), and a few of these
        // seeders' slugs collide with them (e.g. 'operations',
        // 'sales-executive'). firstOrCreate() makes those specific
        // collisions a safe no-op, but the rest would still add generic
        // placeholder entries alongside Krest's real ones. Both are now
        // idempotent and business-aware, so they're safe to run on demand
        // (php artisan db:seed --class=DepartmentSeeder) for a fresh
        // business that has nothing yet - just not as part of the default
        // chain here.
        // $this->call(JobCategorySeeder::class);
        // $this->call(DepartmentSeeder::class);
        $this->call(LeaveTypeListSeeder::class);
        $this->call(IndustriesSeeder::class);
        $this->call(PayrollFormulaSeeder::class);
        $this->call(AllowanceSeeder::class);
        $this->call(ReliefSeeder::class);
        $this->call(DeductionSeeder::class);
    }
}
