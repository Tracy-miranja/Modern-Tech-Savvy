<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WithholdingTaxRatesSeeder extends Seeder
{

    public function run()
{
    $rates = [

        ['payment_type' => 'professional_fees',
         'label'        => 'Professional / Consultancy Fees',
         'residency'    => 'resident',
         'rate'         => 5.00,
         'is_final_tax' => false],
        ['payment_type' => 'professional_fees',
         'label'        => 'Professional / Consultancy Fees',
         'residency'    => 'non_resident',
         'rate'         => 20.00,
         'is_final_tax' => true],

        ['payment_type' => 'training_fees',
         'label'        => 'Training Fees',
         'residency'    => 'resident',
         'rate'         => 5.00,
         'is_final_tax' => false],
        ['payment_type' => 'training_fees',
         'label'        => 'Training Fees',
         'residency'    => 'non_resident',
         'rate'         => 20.00,
         'is_final_tax' => true],

        ['payment_type' => 'contractual',
         'label'        => 'Contractual Payments',
         'residency'    => 'resident',
         'rate'         => 3.00,
         'is_final_tax' => false],
        ['payment_type' => 'contractual',
         'label'        => 'Contractual Payments',
         'residency'    => 'non_resident',
         'rate'         => 20.00,
         'is_final_tax' => true],

        ['payment_type' => 'commissions',
         'label'        => 'Commissions / Agency Fees',
         'residency'    => 'resident',
         'rate'         => 5.00,
         'is_final_tax' => false],
        ['payment_type' => 'commissions',
         'label'        => 'Commissions / Agency Fees',
         'residency'    => 'non_resident',
         'rate'         => 20.00,
         'is_final_tax' => true],
    ];

    foreach ($rates as $rate) {
        \App\Models\WithholdingTaxRate::updateOrCreate(
            [
                'payment_type' => $rate['payment_type'],
                'residency'    => $rate['residency'],
            ],
            $rate
        );
    }
}
}
