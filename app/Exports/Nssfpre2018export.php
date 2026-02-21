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

/**
 * NSSF Pre-2018 Format (flat rate: employee 200, employer 200 = 400 total)
 */
class NssfPre2018Export implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function collection(): Collection
    {
        $rows       = collect();
        $totalEmployee  = 0;
        $totalEmployer  = 0;
        $totalContrib   = 0;

        $employeePayrolls = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with(['employee.user'])
            ->get();

        foreach ($employeePayrolls as $index => $ep) {
            $employee  = $ep->employee;
            $user      = $employee->user ?? null;
            $fullName  = $user->name ?? 'N/A';
            $nameParts = explode(' ', $fullName, 2);
            $surname   = $nameParts[1] ?? '';
            $otherNames= $nameParts[0] ?? $fullName;

            // Pre-2018: Fixed 200 employee + 200 employer
            $employeeContrib = 200.00;
            $employerContrib = 200.00;
            $total           = $employeeContrib + $employerContrib;

            $totalEmployee += $employeeContrib;
            $totalEmployer += $employerContrib;
            $totalContrib  += $total;

            $rows->push([
                'no'               => $index + 1,
                'payroll_no'       => $employee->employee_code ?? 'N/A',
                'surname'          => $surname,
                'other_names'      => $otherNames,
                'id_no'            => $employee->national_id ?? 'N/A',
                'kra_pin'          => $employee->tax_no ?? 'N/A',
                'nssf_no'          => $employee->nssf_no ?? 'N/A',
                'gross_pay'        => floatval($ep->gross_pay ?? 0),
                'employee_contrib' => $employeeContrib,
                'employer_contrib' => $employerContrib,
                'total'            => $total,
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
            'gross_pay'        => '',
            'employee_contrib' => $totalEmployee,
            'employer_contrib' => $totalEmployer,
            'total'            => $totalContrib,
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
            'Employee Contribution (KES)',
            'Employer Contribution (KES)',
            'Total (KES)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ],
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E4F0']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  'B' => 15, 'C' => 18, 'D' => 18, 'E' => 15,
            'F' => 15, 'G' => 15, 'H' => 16, 'I' => 25, 'J' => 25, 'K' => 18,
        ];
    }
}
