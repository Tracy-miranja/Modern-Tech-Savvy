<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Global (business_id null) statutory payroll formulas, one set per
 * country - most calculate via the `expression` column at payroll-run
 * time (see PayrollController), not the bracket table; only formulas
 * that are genuinely tiered (like PAYE) use payroll_formula_brackets.
 *
 * firstOrCreate() by slug, not truncate+insert: this table also holds
 * real per-business overrides (business_id set, e.g. a business's own
 * customized NSSF formula) that a truncate would silently destroy
 * alongside the global rows - this seeder must never be able to do that
 * again (see PayrollFormulaSeeder's git history for exactly that incident).
 */
class PayrollFormulaSeeder extends Seeder
{
    public function run(): void
    {
        $formulas = [
            ['country' => 'Kenya', 'name' => 'PAYE', 'slug' => 'paye', 'description' => 'Kenya PAYE tax on taxable income', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Kenya', 'name' => 'NHIF', 'slug' => 'nhif', 'description' => 'National Hospital Insurance Fund contribution', 'formula_type' => 'progressive', 'calculation_basis' => 'gross_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Kenya', 'name' => 'SHIF', 'slug' => 'shif', 'description' => 'Social Health Insurance Fund contribution', 'formula_type' => 'expression', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => 300.00, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'max(gross_pay * 0.0275, 300)'],
            ['country' => 'Kenya', 'name' => 'Housing Levy', 'slug' => 'housing-levy', 'description' => 'Affordable Housing Levy at 1.5% of gross pay', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => 1.50, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Kenya', 'name' => 'HELB', 'slug' => 'helb', 'description' => 'Higher Education Loans Board deduction (flat rate if applicable)', 'formula_type' => 'fixed', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => 0.00, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Nigeria', 'name' => 'Nigeria PAYE', 'slug' => 'nigeria-paye', 'description' => 'Pay As You Earn (PAYE) tax for Nigeria, based on 2024 FIRS tax bands (NGN, monthly)', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Nigeria', 'name' => 'Nigeria NHIS', 'slug' => 'nigeria-nhis', 'description' => 'National Health Insurance Scheme, 5% of basic salary (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'basic_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'basic_pay * 0.05'],
            ['country' => 'Nigeria', 'name' => 'Nigeria Pension', 'slug' => 'nigeria-pension', 'description' => 'Mandatory pension contribution, 8% of basic salary, housing, and transport allowances (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'gross_pay * 0.08'],
            ['country' => 'Uganda', 'name' => 'Uganda NSSF', 'slug' => 'uganda-nssf', 'description' => 'National Social Security Fund, 5% of gross pay (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => 5.00, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Tanzania', 'name' => 'Tanzania PAYE', 'slug' => 'tanzania-paye', 'description' => 'Pay As You Earn (PAYE) tax for Tanzania, based on 2024 TRA tax bands (TZS, monthly)', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Tanzania', 'name' => 'Tanzania NSSF', 'slug' => 'tanzania-nssf', 'description' => 'National Social Security Fund, 10% of gross pay (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'gross_pay * 0.10'],
            ['country' => 'Rwanda', 'name' => 'Rwanda PAYE', 'slug' => 'rwanda-paye', 'description' => 'Pay As You Earn (PAYE) tax for Rwanda, based on 2024 RRA tax bands (RWF, monthly)', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Rwanda', 'name' => 'Rwanda RSSB Pension', 'slug' => 'rwanda-rssb-pension', 'description' => 'Rwanda Social Security Board pension, 3% of gross pay (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'gross_pay * 0.03'],
            ['country' => 'Senegal', 'name' => 'Senegal PAYE', 'slug' => 'senegal-paye', 'description' => 'Income tax (IR) for Senegal, based on 2024 DGID tax bands (XOF, monthly)', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Senegal', 'name' => 'Senegal CSS', 'slug' => 'senegal-css', 'description' => 'Caisse de Sécurité Sociale, 7% of gross pay up to XOF 636,000 (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => 636000.00, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'gross_pay * 0.07'],
            ['country' => 'South Africa', 'name' => 'South Africa PAYE', 'slug' => 'south-africa-paye', 'description' => 'Pay As You Earn (PAYE) tax for South Africa, based on 2024 SARS tax bands (ZAR, monthly)', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'South Africa', 'name' => 'South Africa UIF', 'slug' => 'south-africa-uif', 'description' => 'Unemployment Insurance Fund, 1% of gross pay up to ZAR 17,712 (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => 17712.00, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'gross_pay * 0.01'],
            ['country' => 'Ethiopia', 'name' => 'Ethiopia PAYE', 'slug' => 'ethiopia-paye', 'description' => 'Income tax for Ethiopia, based on 2024 ERCA tax bands (ETB, monthly)', 'formula_type' => 'progressive', 'calculation_basis' => 'taxable_pay', 'is_progressive' => 1, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => null],
            ['country' => 'Ethiopia', 'name' => 'Ethiopia Pension', 'slug' => 'ethiopia-pension', 'description' => 'Mandatory pension contribution, 7% of gross pay (2024 rates)', 'formula_type' => 'rate', 'calculation_basis' => 'gross_pay', 'is_progressive' => 0, 'is_statutory' => 1, 'minimum_amount' => null, 'limit' => null, 'round_off' => 'nearest', 'applies_to' => 'all', 'expression' => 'gross_pay * 0.07'],
        ];

        foreach ($formulas as $formula) {
            // Only inserts when missing - never overwrites, so a formula
            // someone has since edited in the app (SHIF's rate has
            // already been revised once since it was first seeded) stays
            // exactly as they left it on every future re-run.
            $exists = DB::table('payroll_formulas')
                ->where('slug', $formula['slug'])
                ->whereNull('business_id')
                ->exists();

            if (!$exists) {
                DB::table('payroll_formulas')->insert(
                    array_merge($formula, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        }

        // PAYE is the only one of these tiered via the bracket table
        // rather than `expression` - Kenya's current KRA bands.
        $payeId = DB::table('payroll_formulas')->where('slug', 'paye')->whereNull('business_id')->value('id');

        if ($payeId && !DB::table('payroll_formula_brackets')->where('payroll_formula_id', $payeId)->exists()) {
            DB::table('payroll_formula_brackets')->insert([
                ['payroll_formula_id' => $payeId, 'min' => 0, 'max' => 24000, 'rate' => 10.00, 'amount' => null, 'created_at' => now(), 'updated_at' => now()],
                ['payroll_formula_id' => $payeId, 'min' => 24001, 'max' => 32333, 'rate' => 25.00, 'amount' => null, 'created_at' => now(), 'updated_at' => now()],
                ['payroll_formula_id' => $payeId, 'min' => 32334, 'max' => 500000, 'rate' => 30.00, 'amount' => null, 'created_at' => now(), 'updated_at' => now()],
                ['payroll_formula_id' => $payeId, 'min' => 500001, 'max' => null, 'rate' => 35.00, 'amount' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
