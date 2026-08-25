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

        $whtRate = WithholdingTaxRate::getRate(
            $paymentDetail->wht_payment_type ?? 'professional_fees',
            $paymentDetail->wht_residency    ?? 'resident'
        );

        $whtAmount = round($grossAmount * ($whtRate / 100), 2);

        $shifDeduction = 0;
        if ($paymentDetail->consultant_shif_covered) {
            if ($paymentDetail->consultant_shif_basis === 'fixed') {
                $shifDeduction = floatval($paymentDetail->consultant_shif_fixed_amount ?? 0);
            } else {

                $shifDeduction = max(300, round($grossAmount * 0.0275, 2));
            }
        }

       $nssfDeduction = 0;
if ($paymentDetail->consultant_nssf_covered) {
    if ($paymentDetail->consultant_nssf_basis === 'fixed') {
        $nssfDeduction = floatval($paymentDetail->consultant_nssf_fixed_amount ?? 0);
    } else {
        $lel = 9000;
        $uel = 108000;

        if ($grossAmount <= $lel) {

            $nssfDeduction = round($grossAmount * 0.06, 2);
        } else {

            $tier1 = round($lel * 0.06, 2);

            $tier2Base     = min($grossAmount - $lel, $uel - $lel);
            $tier2         = round($tier2Base * 0.06, 2);

            $nssfDeduction = $tier1 + $tier2;
        }
    }
}

        $netToConsultant = $grossAmount - $whtAmount - $shifDeduction - $nssfDeduction;

        $totalCompanyCost = $grossAmount;

        return [
            'gross_amount'      => $grossAmount,
            'wht_rate'          => $whtRate,
            'wht_amount'        => $whtAmount,
            'shif_deduction'    => $shifDeduction,
            'nssf_deduction'    => $nssfDeduction,
            'net_to_consultant' => max(0, $netToConsultant),
            'total_company_cost'=> $totalCompanyCost,
            'is_final_tax'      => $paymentDetail->wht_residency === 'non_resident',

            'shif_company_cost' => $shifDeduction,
            'nssf_company_cost' => $nssfDeduction,
        ];
    }
}
