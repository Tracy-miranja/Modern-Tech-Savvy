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

class NssfOldFormatExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function collection(): Collection
    {
        $rows        = collect();
        $grandTotal  = 0;

        $employeePayrolls = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with(['employee.user'])
            ->get();

        foreach ($employeePayrolls as $index => $ep) {
            $employee   = $ep->employee;
            $user       = $employee->user ?? null;
            $nssfAmount = floatval($ep->nssf ?? 0);

            $grandTotal += $nssfAmount;

            $rows->push([
                'no'          => $index + 1,
                'payroll_no'  => $employee->employee_code ?? 'N/A',
                'name'        => $user->name ?? 'N/A',
                'id_no'       => $employee->national_id ?? 'N/A',
                'nssf_no'     => $employee->nssf_no ?? 'N/A',
                'nssf_amount' => $nssfAmount,
            ]);
        }

        $rows->push([
            'no'          => '',
            'payroll_no'  => 'TOTAL',
            'name'        => '',
            'id_no'       => '',
            'nssf_no'     => '',
            'nssf_amount' => $grandTotal,
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Payroll No',
            'Employee Name',
            'ID No',
            'NSSF No',
            'NSSF Amount (KES)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
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
            'A' => 5, 'B' => 15, 'C' => 28, 'D' => 15, 'E' => 18, 'F' => 20,
        ];
    }
}
