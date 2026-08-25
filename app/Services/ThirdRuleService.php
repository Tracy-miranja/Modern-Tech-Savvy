<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\EmployeePayroll;

class ThirdRuleService
{

    public const MIN_NET_FRACTION = 1 / 3;

    private const EXCLUDED_FROM_VOLUNTARY = [
        'shif', 'nssf', 'paye', 'housing levy', 'helb',
        'absenteeism', 'absenteeism charge',
    ];

    public const RULE_BASIS = 'basic_salary';

    public static function evaluateEmployee(EmployeePayroll $ep, ?string $basis = null): array
    {
        $basis = $basis ?? self::RULE_BASIS;

        $basicSalary = (float) (
            $ep->basic_salary
            ?? $ep->employee?->employmentDetails?->basic_salary
            ?? $ep->employee?->employmentDetails?->salary
            ?? 0
        );
        $grossPay = (float) ($ep->gross_pay ?? 0);
        $basisAmount = $basis === 'gross_pay' ? $grossPay : $basicSalary;

        $statutory = (float) ($ep->shif ?? 0)
            + (float) ($ep->nssf ?? 0)
            + (float) ($ep->housing_levy ?? 0)
            + (float) ($ep->paye ?? 0);

        $deductionMap = self::decodeJsonField(
    $ep->getRawOriginal('deductions')
);
        $voluntary = 0.0;
        foreach ($deductionMap as $lc => $item) {
            if (in_array($lc, self::EXCLUDED_FROM_VOLUNTARY, true)) {
                continue;
            }
            $voluntary += (float) ($item['amount'] ?? 0);
        }
        $voluntary += (float) ($ep->loan_repayment ?? 0)
            + (float) ($ep->advance_recovery ?? 0);

        $maxTotalDeductions = $basisAmount * (1 - self::MIN_NET_FRACTION);
        $maxVoluntary = max($maxTotalDeductions - $statutory, 0.0);
        $totalDeductions = $statutory + $voluntary;

        $passes = $totalDeductions <= $maxTotalDeductions + 0.01;

        if ($passes) {
            $status = 'compliant';
        } elseif ($statutory > $maxTotalDeductions) {

            $status = 'statutory_breach';
        } else {
            $status = 'breach';
        }

        return [
            'basis_amount'         => $basisAmount,
            'statutory_deductions' => $statutory,
            'voluntary_deductions' => $voluntary,
            'total_deductions'     => $totalDeductions,
            'max_total_deductions' => $maxTotalDeductions,
            'max_voluntary'        => $maxVoluntary,
            'passes'               => $passes,
            'status'               => $status,
        ];
    }

    private static function decodeJsonField($raw): array
{
    $decoded = $raw;

    $maxPasses = 4;

    while (is_string($decoded) && $maxPasses-- > 0) {
        $attempt = json_decode($decoded, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            break;
        }

        $decoded = $attempt;
    }

    if (!is_array($decoded)) {
        return [];
    }

    $out = [];

    foreach ($decoded as $keyOrIndex => $value) {
        if (!is_array($value)) {
            continue;
        }

        if (!empty($value['item_name'])) {
            $name = trim($value['item_name']);
        } elseif (!empty($value['name'])) {
            $name = trim($value['name']);
        } elseif (is_string($keyOrIndex)) {
            $name = trim($keyOrIndex);
        } else {
            continue;
        }

        $lc = strtolower($name);

        $out[$lc] = [
            'display_name' => $name,
            'amount' => (float)($value['amount'] ?? 0),
        ];
    }

    return $out;
}

    public static function evaluatePayroll(Payroll $payroll, ?string $basis = null): bool
    {
        foreach ($payroll->employeePayrolls as $ep) {
            if (! self::evaluateEmployee($ep, $basis)['passes']) {
                return false;
            }
        }
        return true;
    }

    public static function recalculateAndSave(Payroll $payroll, ?string $basis = null): bool
    {
        $passes = self::evaluatePayroll($payroll, $basis);
        $payroll->third_rule = $passes;
        $payroll->save();

        return $passes;
    }

    public static function capVoluntaryDeductions(EmployeePayroll $ep, float $requestedVoluntary, ?string $basis = null): array
    {
        $eval = self::evaluateEmployee($ep, $basis);
        $allowed = min($requestedVoluntary, $eval['max_voluntary']);

        return [
            'allowed'  => round($allowed, 2),
            'deferred' => round($requestedVoluntary - $allowed, 2),
        ];
    }
}
