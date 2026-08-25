<?php

namespace App\Services;

use App\Models\Relief;

class PayrollService
{
    public function computeReliefValue(Relief $relief, $employeeData)
    {
        $baseValue = match ($relief->percentage_of) {
            'total_salary' => $employeeData['gross_salary'],
            'basic_salary' => $employeeData['basic_salary'],
            'net_salary' => $employeeData['net_salary'],
            default => 0,
        };

        $value = match ($relief->computation_method) {
            'fixed' => $relief->amount,
            'percentage' => ($relief->percentage_of_amount / 100) * $baseValue,
            'formula' => $this->computeFormula($relief->formula, $employeeData),
            default => 0,
        };

        if ($relief->limit && $value > $relief->limit) {
            $value = $relief->limit;
        }

        $value = match ($relief->round_off) {
            'round_off_up' => ceil($value * pow(10, $relief->decimal_places)) / pow(10, $relief->decimal_places),
            'round_off_down' => floor($value * pow(10, $relief->decimal_places)) / pow(10, $relief->decimal_places),
            default => $value,
        };

        return $value;
    }

    private function computeFormula($formula, $employeeData)
    {
        return match (true) {
            str_contains($formula, 'TaxBands') => $this->computeTaxBands($employeeData['taxable_income']),
            str_contains($formula, 'FringeBenefit') => $this->computeFringeBenefit($formula, $employeeData),
            default => 0,
        };
    }

    private function computeTaxBands($taxableIncome)
    {
        $bands = [24000 => 0.10, 32333 => 0.25, PHP_INT_MAX => 0.30];
        $tax = 0;
        $remaining = $taxableIncome;
        $prevThreshold = 0;

        foreach ($bands as $threshold => $rate) {
            if ($remaining <= 0) break;
            $taxableInBand = min($remaining, $threshold - $prevThreshold);
            $tax += $taxableInBand * $rate;
            $remaining -= $taxableInBand;
            $prevThreshold = $threshold;
        }

        return $tax;
    }

    private function computeFringeBenefit($formula, $employeeData)
    {
        preg_match('/FringeBenefit\((\d+\.?\d*)\)/', $formula, $matches);
        $employerRate = $matches[1] ?? 0;
        $marketRate = 0.13;
        $loanAmount = $employeeData['loan_amount'] ?? 0;
        return (($marketRate - ($employerRate / 100)) * $loanAmount) / 12;
    }
}