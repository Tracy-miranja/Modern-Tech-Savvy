<?php

namespace App\Services;

use App\Models\WithholdingTaxRate;
use App\Models\EmployeePaymentDetail;

class WhtCalculationService
{
    public function calculate(
        float $grossAmount,
        EmployeePaymentDetail $paymentDetail
    ): array {
        // ── Step 1: WHT rate from DB ───────────────────────────────
        $whtRate = WithholdingTaxRate::getRate(
            $paymentDetail->wht_payment_type ?? 'professional_fees',
            $paymentDetail->wht_residency    ?? 'resident'
        );

        // ── Step 2: WHT deduction ──────────────────────────────────
        // Deducted from consultant gross before paying them
        $whtAmount = round($grossAmount * ($whtRate / 100), 2);

        // ── Step 3: SHIF deduction (from consultant pay) ──────────
        // SHIF is deducted FROM the consultant — not a company cost.
        // Company just remits it to NHIF on their behalf.
        $shifDeduction = 0;
        if ($paymentDetail->consultant_shif_covered) {
            if ($paymentDetail->consultant_shif_basis === 'fixed') {
                $shifDeduction = floatval($paymentDetail->consultant_shif_fixed_amount ?? 0);
            } else {
                // Standard SHIF rate: 2.75% of gross, minimum KES 300
                $shifDeduction = max(300, round($grossAmount * 0.0275, 2));
            }
        }

        // ── Step 4: NSSF deduction (from consultant pay) ──────────
        // NSSF is deducted FROM the consultant — not a company cost.
        // Company just remits it on their behalf.
       $nssfDeduction = 0;
if ($paymentDetail->consultant_nssf_covered) {
    if ($paymentDetail->consultant_nssf_basis === 'fixed') {
        $nssfDeduction = floatval($paymentDetail->consultant_nssf_fixed_amount ?? 0);
    } else {
        $lel = 9000;    // Lower Earnings Limit (Year 4)
        $uel = 108000;  // Upper Earnings Limit (Year 4)

        if ($grossAmount <= $lel) {
            // Below LEL — 6% of actual gross
            $nssfDeduction = round($grossAmount * 0.06, 2);
        } else {
            // Tier 1: 6% of LEL = 540
            $tier1 = round($lel * 0.06, 2); // = 540

            // Tier 2: 6% of (gross - LEL), capped at UEL - LEL = 99,000
            $tier2Base     = min($grossAmount - $lel, $uel - $lel); // max 99,000
            $tier2         = round($tier2Base * 0.06, 2);           // max 5,940

            $nssfDeduction = $tier1 + $tier2; // max 6,480
        }
    }
}

        // ── Step 5: Net pay to consultant ─────────────────────────
        // Gross − WHT − SHIF − NSSF
        // All three are deducted FROM the consultant
        $netToConsultant = $grossAmount - $whtAmount - $shifDeduction - $nssfDeduction;

        // ── Step 6: Total company cost ─────────────────────────────
        // Company pays the gross amount only.
        // WHT, SHIF, NSSF all come OUT of that gross — no extra cost.
        // Company's only obligation is to REMIT them to the respective bodies.
        $totalCompanyCost = $grossAmount; // no extras on top

        return [
            'gross_amount'      => $grossAmount,
            'wht_rate'          => $whtRate,
            'wht_amount'        => $whtAmount,
            'shif_deduction'    => $shifDeduction,    // deducted from consultant
            'nssf_deduction'    => $nssfDeduction,    // deducted from consultant
            'net_to_consultant' => max(0, $netToConsultant),
            'total_company_cost'=> $totalCompanyCost,
            'is_final_tax'      => $paymentDetail->wht_residency === 'non_resident',

            // Keep these keys for backward compatibility with PayrollController
            'shif_company_cost' => $shifDeduction,
            'nssf_company_cost' => $nssfDeduction,
        ];
    }
}
