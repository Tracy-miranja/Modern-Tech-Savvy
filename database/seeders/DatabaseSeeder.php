<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        // $this->call(ModulesSeeder::class);
        // $this->call(ShiftSeeder::class);
        // $this->call(JobCategorySeeder::class);
        // $this->call(DepartmentSeeder::class);
        // $this->call(LeaveTypeListSeeder::class);
        // $this->call(IndustriesSeeder::class);
        // $this->call(PayrollFormulaSeeder::class);
        // $this->call(AllowanceSeeder::class);
        // $this->call(ReliefSeeder::class);
        $this->call(DeductionSeeder::class);
    }
}
