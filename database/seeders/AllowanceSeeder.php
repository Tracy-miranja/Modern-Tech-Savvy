<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AllowanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks to allow truncation of allowances table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate only the allowances table to start fresh
        DB::table('allowances')->truncate();

        // Use existing business_id = 1 (assuming it’s 'krest' from your dump)
        $businessId = 1;

        // Define popular allowances
        $allowances = [
            [
                'business_id' => $businessId,
                'name' => 'Housing Allowance',
                'type' => 'fixed',
                'calculation_basis' => 'basic_pay',
                'amount' => 15000.00,
                'rate' => null,
                'is_taxable' => true,
                'applies_to' => 'all',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'name' => 'Transport Allowance',
                'type' => 'fixed',
                'calculation_basis' => 'basic_pay',
                'amount' => 5000.00,
                'rate' => null,
                'is_taxable' => true,
                'applies_to' => 'all',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'name' => 'Meal Allowance',
                'type' => 'fixed',
                'calculation_basis' => 'basic_pay',
                'amount' => 3000.00,
                'rate' => null,
                'is_taxable' => false,
                'applies_to' => 'specific',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'name' => 'Performance Bonus',
                'type' => 'rate',
                'calculation_basis' => 'basic_pay',
                'amount' => null,
                'rate' => 10.00, // 10% of basic pay
                'is_taxable' => true,
                'applies_to' => 'specific',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'name' => 'Overtime Allowance',
                'type' => 'rate',
                'calculation_basis' => 'gross_pay',
                'amount' => null,
                'rate' => 15.00, // 15% of gross pay
                'is_taxable' => true,
                'applies_to' => 'specific',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $businessId,
                'name' => 'Medical Allowance',
                'type' => 'fixed',
                'calculation_basis' => 'basic_pay',
                'amount' => 7000.00,
                'rate' => null,
                'is_taxable' => false,
                'applies_to' => 'all',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert allowances with slugs
        foreach ($allowances as $allowance) {
            DB::table('allowances')->insert([
                'business_id' => $allowance['business_id'],
                'name' => $allowance['name'],
                'slug' => Str::slug($allowance['name']),
                'type' => $allowance['type'],
                'calculation_basis' => $allowance['calculation_basis'],
                'amount' => $allowance['amount'],
                'rate' => $allowance['rate'],
                'is_taxable' => $allowance['is_taxable'],
                'applies_to' => $allowance['applies_to'],
                'created_at' => $allowance['created_at'],
                'updated_at' => $allowance['updated_at'],
            ]);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
