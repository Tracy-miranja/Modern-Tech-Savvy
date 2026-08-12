<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\EmployeePayroll;
// use App\Exports\MasterRollSheet;

/**
 * Implements Section 19(3) of the Employment Act, 2007 (Kenya) - the "1/3 rule":
 * an employee's net pay must not fall below one-third of their basic salary
 * (equivalently: total deductions, statutory + voluntary, must not exceed
 * two-thirds of basic salary).
 *
 * Usage:
 *   ThirdRuleService::recalculateAndSave($payroll);   // updates $payroll->third_rule
 *   ThirdRuleService::evaluateEmployee($employeePayroll); // per-employee breakdown
 */
class ThirdRuleService
{
    // Employee must take home at least this fraction of the basis amount.
    public const MIN_NET_FRACTION = 1 / 3;

    // Keys already counted as "statutory" elsewhere, or not a true deduction
    // (absenteeism reduces earned pay, it is not a deduction from wages owed).
    private const EXCLUDED_FROM_VOLUNTARY = [
        'shif', 'nssf', 'paye', 'housing levy', 'helb',
        'absenteeism', 'absenteeism charge',
    ];

    /**
     * Which figure the 2/3 ceiling is measured against.
     * 'basic_salary' matches the common public/private-sector interpretation
     * (Human Resource Policies and Procedures Manual for the Public Service, 2016).
     * Change to 'gross_pay' here if your business/legal advisor prefers that basis.
     */
    public const RULE_BASIS = 'basic_salary';

    /**
     * Evaluate a single EmployeePayroll row against the 1/3 rule.
     *
     * @return array{
     *   basis_amount: float, statutory_deductions: float, voluntary_deductions: float,
     *   total_deductions: float, max_total_deductions: float, max_voluntary: float,
     *   passes: bool, status: string
     * }
     */
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

        $maxTotalDeductions = $basisAmount * (1 - self::MIN_NET_FRACTION); // 2/3 of basis
        $maxVoluntary = max($maxTotalDeductions - $statutory, 0.0);
        $totalDeductions = $statutory + $voluntary;

        // small tolerance for floating point rounding
        $passes = $totalDeductions <= $maxTotalDeductions + 0.01;

        if ($passes) {
            $status = 'compliant';
        } elseif ($statutory > $maxTotalDeductions) {
            // Statutory deductions alone already breach the 2/3 ceiling -
            // this cannot be fixed by capping voluntary deductions.
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

    /**
     * True only if every EmployeePayroll in the payroll passes the rule.
     */
    public static function evaluatePayroll(Payroll $payroll, ?string $basis = null): bool
    {
        foreach ($payroll->employeePayrolls as $ep) {
            if (! self::evaluateEmployee($ep, $basis)['passes']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Recalculate and persist $payroll->third_rule. Call this immediately
     * after (re)generating or editing any EmployeePayroll rows for a payrun -
     * e.g. at the end of your payroll processing job, and again after any
     * single employee's payslip is manually edited.
     */
    public static function recalculateAndSave(Payroll $payroll, ?string $basis = null): bool
    {
        $passes = self::evaluatePayroll($payroll, $basis);
        $payroll->third_rule = $passes;
        $payroll->save();

        return $passes;
    }

    /**
     * Optional: caps voluntary deductions for an employee to what the 1/3 rule
     * allows, and reports the excess so it can be deferred to next payroll.
     * Call this during payslip calculation, before persisting deduction totals.
     *
     * @param float $requestedVoluntary Sum of all requested voluntary deductions
     * @return array{allowed: float, deferred: float}
     */
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
