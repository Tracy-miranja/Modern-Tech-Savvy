<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\EmployeePayroll;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * NSSF Grouped Export — groups employees by department, location, or job_category
 * Inserts a bold group header row before each group and a subtotal row after.
 */
class NssfGroupedExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected Payroll $payroll;
    protected string  $groupBy; // 'department' | 'location' | 'job_category'
    protected array   $groupRows = []; // tracks row indices for group headers & subtotals

    public function __construct(Payroll $payroll, string $groupBy = 'department')
    {
        $this->payroll = $payroll;
        $this->groupBy = in_array($groupBy, ['department', 'location', 'job_category'])
            ? $groupBy : 'department';
    }

    public function collection(): Collection
    {
        $employeePayrolls = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with([
                'employee.user',
                'employee.location',
                'employee.employmentDetails.department',
                'employee.employmentDetails.jobCategory',
            ])
            ->get();

        // Group employees
        $grouped = $employeePayrolls->groupBy(function ($ep) {
            $employee = $ep->employee;
            return match ($this->groupBy) {
                'department'   => $employee->employmentDetails?->department?->name ?? 'No Department',
                'location'     => $employee->location?->name ?? 'Head Office',
                'job_category' => $employee->employmentDetails?->jobCategory?->name ?? 'No Category',
                default        => 'All',
            };
        });

        $rows        = collect();
        $grandTotal  = 0;
        $rowIndex    = 2; // Row 1 = headings

        foreach ($grouped as $groupName => $eps) {
            // Group header row
            $this->groupRows['headers'][] = $rowIndex;
            $rows->push([
                'no'          => '',
                'payroll_no'  => strtoupper($groupName),
                'surname'     => '',
                'other_names' => '',
                'id_no'       => '',
                'kra_pin'     => '',
                'nssf_no'     => '',
                'gross_pay'   => '',
                'employee'    => '',
                'employer'    => '',
                'total'       => '',
            ]);
            $rowIndex++;

            $subEmployee = 0;
            $subEmployer = 0;
            $subTotal    = 0;
            $subCount    = 0;

            foreach ($eps as $ep) {
                $employee  = $ep->employee;
                $user      = $employee->user ?? null;
                $fullName  = $user->name ?? 'N/A';
                $nameParts = explode(' ', $fullName, 2);

                $nssfTotal    = floatval($ep->nssf ?? 0);
                $empContrib   = round($nssfTotal / 2, 2);
                $erContrib    = round($nssfTotal / 2, 2);
                $total        = $empContrib + $erContrib;

                $subEmployee += $empContrib;
                $subEmployer += $erContrib;
                $subTotal    += $total;
                $grandTotal  += $total;
                $subCount++;

                $rows->push([
                    'no'          => $subCount,
                    'payroll_no'  => $employee->employee_code ?? 'N/A',
                    'surname'     => $nameParts[1] ?? '',
                    'other_names' => $nameParts[0] ?? $fullName,
                    'id_no'       => $employee->national_id ?? 'N/A',
                    'kra_pin'     => $employee->tax_no ?? 'N/A',
                    'nssf_no'     => $employee->nssf_no ?? 'N/A',
                    'gross_pay'   => floatval($ep->gross_pay ?? 0),
                    'employee'    => $empContrib,
                    'employer'    => $erContrib,
                    'total'       => $total,
                ]);
                $rowIndex++;
            }

            // Subtotal row
            $this->groupRows['subtotals'][] = $rowIndex;
            $rows->push([
                'no'          => '',
                'payroll_no'  => "Sub-total ({$groupName})",
                'surname'     => '',
                'other_names' => '',
                'id_no'       => '',
                'kra_pin'     => '',
                'nssf_no'     => '',
                'gross_pay'   => '',
                'employee'    => $subEmployee,
                'employer'    => $subEmployer,
                'total'       => $subTotal,
            ]);
            $rowIndex++;
        }

        // Grand total
        $this->groupRows['grand_total'] = $rowIndex;
        $rows->push([
            'no'          => '',
            'payroll_no'  => 'GRAND TOTAL',
            'surname'     => '',
            'other_names' => '',
            'id_no'       => '',
            'kra_pin'     => '',
            'nssf_no'     => '',
            'gross_pay'   => '',
            'employee'    => '',
            'employer'    => '',
            'total'       => $grandTotal,
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
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style group headers
                foreach ($this->groupRows['headers'] ?? [] as $row) {
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
                    ]);
                    $sheet->mergeCells("B{$row}:K{$row}");
                }

                // Style subtotals
                foreach ($this->groupRows['subtotals'] ?? [] as $row) {
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'italic' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DEEAF1']],
                    ]);
                }

                // Style grand total
                if (isset($this->groupRows['grand_total'])) {
                    $row = $this->groupRows['grand_total'];
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
                    ]);
                }
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  'B' => 20, 'C' => 18, 'D' => 18, 'E' => 15,
            'F' => 15, 'G' => 15, 'H' => 16, 'I' => 25, 'J' => 25, 'K' => 18,
        ];
    }
}
