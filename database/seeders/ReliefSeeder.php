<?php

namespace Database\Seeders;

use App\Models\Relief;
use App\Models\Business;
use Illuminate\Database\Seeder;

class ReliefSeeder extends Seeder
{
    public function run()
    {

        $business = Business::where('slug', config('business.main_slug'))->first()
            ?? Business::first();

        $reliefs = [
            [
                'business_id' => $business->id,
                'type' => 'deductible_before_tax',
                'name' => 'SHIF Relief',
                'slug' => 'shif-relief',
                'greatest_or_least_of' => 'least',
                'amount' => 0,
                'actual_amount' => false,
                'percentage_of_amount' => 0,
                'percentage_of' => null,
                'fraction_to_consider' => 'employee_only',
                'limit' => 0,
                'round_off' => 'round_off_up',
                'decimal_places' => 2,
                'is_active' => true,
            ],
            [
                'business_id' => $business->id,
                'type' => 'deductible_before_tax',
                'name' => 'Housing Levy Relief',
                'slug' => 'housing-levy-relief',
                'greatest_or_least_of' => 'least',
                'amount' => 0,
                'actual_amount' => false,
                'percentage_of_amount' => 0,
                'percentage_of' => null,
                'fraction_to_consider' => 'employee_only',
                'limit' => 0,
                'round_off' => 'round_off_up',
                'decimal_places' => 2,
                'is_active' => true,
            ],
            [
                'business_id' => $business->id,
                'type' => 'deductible_before_tax',
                'name' => 'Pension Relief',
                'slug' => 'pension-relief',
                'greatest_or_least_of' => 'least',
                'amount' => 30000,
                'actual_amount' => true,
                'percentage_of_amount' => 0,
                'percentage_of' => null,
                'fraction_to_consider' => 'employee_only',
                'limit' => 30000,
                'round_off' => 'round_off_up',
                'decimal_places' => 2,
                'is_active' => true,
            ],
            [
                'business_id' => $business->id,
                'type' => 'deductible_after_tax',
                'name' => 'Insurance Relief',
                'slug' => 'insurance-relief',
                'greatest_or_least_of' => 'least',
                'amount' => 5000,
                'actual_amount' => true,
                'percentage_of_amount' => 15,
                'percentage_of' => 'total_salary',
                'fraction_to_consider' => 'employee_only',
                'limit' => 5000,
                'round_off' => 'round_off_up',
                'decimal_places' => 0,
                'is_active' => true,
            ],
            [
                'business_id' => $business->id,
                'type' => 'deductible_after_tax',
                'name' => 'Personal Relief',
                'slug' => 'personal-relief',
                'greatest_or_least_of' => 'least',
                'amount' => 2400,
                'actual_amount' => false,
                'percentage_of_amount' => 0,
                'percentage_of' => null,
                'fraction_to_consider' => 'employee_only',
                'limit' => 2400,
                'round_off' => 'round_off_up',
                'decimal_places' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($reliefs as $relief) {
            Relief::firstOrCreate(['slug' => $relief['slug']], $relief);
        }
    }
}