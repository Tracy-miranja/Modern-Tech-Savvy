<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeductionSeeder extends Seeder
{
    public function run()
    {
       // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('deductions')->truncate();

        $businessId = 1;

        $deductions = [
            [
                'name' => 'Uzima Sacco Contribution',
                'description' => 'Monthly contribution to Uzima Sacco.',
                'calculation_basis' => 'basic_pay',
                // 'type' => 'fixed',
                'amount' => 2000.00,
                'rate' => null,
            ],
            [
                'name' => 'Harambee Sacco Contribution',
                'description' => 'Monthly contribution to Harambee Sacco.',
                'calculation_basis' => 'basic_pay',
                // 'type' => 'fixed',
                'amount' => 1500.00,
                'rate' => null,
            ],
            [
                'name' => 'Union Dues',
                'description' => 'Trade union membership dues.',
                'calculation_basis' => 'basic_pay',
                // 'type' => 'rate',
              //  'amount' => null,
                'rate' => 2.00, // 2% of basic pay
            ],
            [
                'name' => 'Stima Sacco Contribution',
                'description' => 'Monthly contribution to Stima Sacco.',
                'calculation_basis' => 'gross_pay',
                // 'type' => 'fixed',
                'amount' => 3000.00,
                'rate' => null,
            ],
        ];

       foreach ($deductions as $deduction) {
    DB::table('deductions')->insert([
        'business_id' => $businessId,
        'name' => $deduction['name'],
        'slug' => Str::slug($deduction['name']),
        'description' => $deduction['description'],
        'calculation_basis' => $deduction['calculation_basis'],
        'rate' => $deduction['rate'],
        'decimal_places' => 2, // <-- add this
        'is_optional' => true,
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
