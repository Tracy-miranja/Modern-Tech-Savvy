<?php

namespace Database\Seeders;

use App\Models\PayrollTaxRule;
use Illuminate\Database\Seeder;

class PayrollTaxRuleSeeder extends Seeder
{
    private const EFFECTIVE_FROM = '2026-07-01';

    // lower_limit is "excess over X" per band, not the band's first shilling
    public function run(): void
    {
        $bands = [
            ['lower_limit' => 0, 'upper_limit' => 270000, 'rate' => 0, 'fixed_amount' => 0],
            ['lower_limit' => 270000, 'upper_limit' => 520000, 'rate' => 8, 'fixed_amount' => 0],
            ['lower_limit' => 520000, 'upper_limit' => 760000, 'rate' => 20, 'fixed_amount' => 20000],
            ['lower_limit' => 760000, 'upper_limit' => 1000000, 'rate' => 25, 'fixed_amount' => 68000],
            ['lower_limit' => 1000000, 'upper_limit' => null, 'rate' => 30, 'fixed_amount' => 128000],
        ];

        foreach ($bands as $band) {
            PayrollTaxRule::firstOrCreate([
                'country' => 'Tanzania',
                'jurisdiction' => null,
                'rule_type' => 'paye_resident_band',
                'lower_limit' => $band['lower_limit'],
                'effective_from' => self::EFFECTIVE_FROM,
            ], [
                'upper_limit' => $band['upper_limit'],
                'rate' => $band['rate'],
                'fixed_amount' => $band['fixed_amount'],
                'is_active' => true,
            ]);
        }

        $flatRules = [
            'paye_nonresident_flat' => 15,
            'nssf_employee' => 10,
            'nssf_employer' => 10,
            'sdl' => 3.5,
            'wcf' => 0.5,
        ];

        foreach ($flatRules as $ruleType => $rate) {
            PayrollTaxRule::firstOrCreate([
                'country' => 'Tanzania',
                'jurisdiction' => null,
                'rule_type' => $ruleType,
                'effective_from' => self::EFFECTIVE_FROM,
            ], [
                'rate' => $rate,
                'is_active' => true,
            ]);
        }
    }
}
