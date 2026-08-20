<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\EmployeePayroll;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * New NSSF Return/Remittance Format (post-2014 NSSF Act)
 * Columns: Payroll No, Surname, Other Names, ID No, KRA PIN, NSSF No, Gross Pay, Tier I Employee, Tier I Employer, Tier II Employee, Tier II Employer, Total
 *
 * Rates effective 1 February 2026 (Year 4 of the NSSF Act 2013 phased rollout):
 *   Tier I  — Lower Earnings Limit (LEL): KES 9,000  — 6% each side, capped at KES 540
 *   Tier II — Upper Earnings Limit (UEL): KES 108,000 — 6% each side on the band
 *             (9,000 to 108,000), capped at KES 5,940
 *   Combined max per side: KES 6,480 (total employee+employer: KES 12,960)
 */
class NssfNewRemittanceExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    // NSSF Act 2013 — Year 4 rates, effective 1 Feb 2026
    private const TIER1_LEL = 9000;
    private const TIER2_UEL = 108000;
    private const NSSF_RATE = 0.06;

    protected $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $totals = [
            'gross_pay'         => 0,
            'tier1_employee'    => 0,
            'tier1_employer'    => 0,
            'tier2_employee'    => 0,
            'tier2_employer'    => 0,
            'total_contribution'=> 0,
        ];

        $employeePayrolls = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with(['employee.user'])
            ->get();

        foreach ($employeePayrolls as $index => $ep) {
            $employee = $ep->employee;
            $user     = $employee->user ?? null;
            $fullName = $user->name ?? 'N/A';
            $nameParts = explode(' ', $fullName, 2);
            $surname    = $nameParts[1] ?? '';
            $otherNames = $nameParts[0] ?? $fullName;

            $grossPay = floatval($ep->gross_pay ?? 0);

            // Tier I: on first KES 9,000 (Lower Earnings Limit) — 6% each side, capped at 540
            $tier1Base     = min($grossPay, self::TIER1_LEL);
            $tier1Employee = round($tier1Base * self::NSSF_RATE, 2);
            $tier1Employer = round($tier1Base * self::NSSF_RATE, 2);

            // Tier II: on amount above 9,000 up to 108,000 (Upper Earnings Limit) — 6% each side, capped at 5,940
            $tier2Base     = max(0, min($grossPay, self::TIER2_UEL) - self::TIER1_LEL);
            $tier2Employee = round($tier2Base * self::NSSF_RATE, 2);
            $tier2Employer = round($tier2Base * self::NSSF_RATE, 2);

            $totalContribution = $tier1Employee + $tier1Employer + $tier2Employee + $tier2Employer;

            // Accumulate totals
            $totals['gross_pay']          += $grossPay;
            $totals['tier1_employee']     += $tier1Employee;
            $totals['tier1_employer']     += $tier1Employer;
            $totals['tier2_employee']     += $tier2Employee;
            $totals['tier2_employer']     += $tier2Employer;
            $totals['total_contribution'] += $totalContribution;

            $rows->push([
                'no'               => $index + 1,
                'payroll_no'       => $employee->employee_code ?? 'N/A',
                'surname'          => $surname,
                'other_names'      => $otherNames,
                'id_no'            => $employee->national_id ?? 'N/A',
                'kra_pin'          => $employee->tax_no ?? 'N/A',
                'nssf_no'          => $employee->nssf_no ?? 'N/A',
                'gross_pay'        => $grossPay,
                'tier1_employee'   => $tier1Employee,
                'tier1_employer'   => $tier1Employer,
                'tier2_employee'   => $tier2Employee,
                'tier2_employer'   => $tier2Employer,
                'total'            => $totalContribution,
            ]);
        }

        // Totals row
        $rows->push([
            'no'               => '',
            'payroll_no'       => 'TOTAL',
            'surname'          => '',
            'other_names'      => '',
            'id_no'            => '',
            'kra_pin'          => '',
            'nssf_no'          => '',
            'gross_pay'        => $totals['gross_pay'],
            'tier1_employee'   => $totals['tier1_employee'],
            'tier1_employer'   => $totals['tier1_employer'],
            'tier2_employee'   => $totals['tier2_employee'],
            'tier2_employer'   => $totals['tier2_employer'],
            'total'            => $totals['total_contribution'],
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Payroll No',
            'Surname',
            'Other Names',
            'ID No',
            'KRA PIN',
            'NSSF No',
            'Gross Pay (KES)',
            'Tier I Employee (KES)',
            'Tier I Employer (KES)',
            'Tier II Employee (KES)',
            'Tier II Employer (KES)',
            'Total Contribution (KES)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ],
            $lastRow => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E4F0']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 15,
            'C' => 18,
            'D' => 18,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 16,
            'I' => 20,
            'J' => 20,
            'K' => 22,
            'L' => 22,
            'M' => 22,
        ];
    }
}
