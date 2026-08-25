<?php

namespace App\Exports;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\Payroll;
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
use PhpOffice\PhpSpreadsheet\Style\Border;

class NssfMonthlySummaryExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected Business $business;
    protected int      $year;
    protected int      $totalRows = 0;   // set after collection() runs

    public function __construct(Business $business, int $year)
    {
        $this->business = $business;
        $this->year     = $year;
    }

    public function collection(): Collection
    {
        $months   = range(1, 12);
        $payrolls = Payroll::where('business_id', $this->business->id)
            ->where('payrun_year', $this->year)
            ->whereIn('payrun_month', $months)
            ->get()
            ->keyBy('payrun_month');

        $allEmployeeIds = EmployeePayroll::whereIn('payroll_id', $payrolls->pluck('id'))
            ->distinct()
            ->pluck('employee_id');

        $employees = Employee::whereIn('id', $allEmployeeIds)
            ->with('user')
            ->get()
            ->keyBy('id');

        $nssfByEmployeeMonth = [];
        foreach ($payrolls as $month => $payroll) {
            $eps = EmployeePayroll::where('payroll_id', $payroll->id)
                ->get(['employee_id', 'nssf', 'deductions']);

            foreach ($eps as $ep) {
                $nssf = floatval($ep->nssf ?? 0);
                if ($nssf == 0) {
                    $deductions = json_decode($ep->deductions, true) ?? [];
                    $nssf       = floatval($deductions['nssf'] ?? 0);
                }
                $nssfByEmployeeMonth[$ep->employee_id][$month] = $nssf;
            }
        }

        $rows        = collect();
        $monthTotals = array_fill(1, 12, 0.0);

        foreach ($employees as $empId => $employee) {
            $name      = $employee->user->name ?? 'N/A';
            $rowTotal  = 0;
            $rowValues = [];

            foreach ($months as $m) {
                $v           = $nssfByEmployeeMonth[$empId][$m] ?? 0;
                $rowValues[] = $v > 0 ? $v : null;
                $rowTotal   += $v;
                if ($v > 0) {
                    $monthTotals[$m] += $v;
                }
            }

            if ($rowTotal == 0) {
                continue;
            }

            $rows->push(array_merge([$name], $rowValues, [$rowTotal]));
        }

        $grandTotal  = array_sum($monthTotals);
        $totalsValues = array_map(fn($v) => $v > 0 ? $v : null, array_values($monthTotals));
        $rows->push(array_merge(['Total'], $totalsValues, [$grandTotal]));

        $this->totalRows = $rows->count() + 1;
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Name',
            'January', 'February', 'March', 'April',
            'May', 'June', 'July', 'August',
            'September', 'October', 'November', 'December',
            'Total',
        ];
    }

    public function styles(Worksheet $sheet): array
    {

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A{$lastRow}:N{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
                    'borders' => [
                        'top'    => ['borderStyle' => Border::BORDER_MEDIUM],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                    ],
                ]);

                $sheet->getStyle("B2:N{$lastRow}")->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("B2:N{$lastRow}")
                      ->getNumberFormat()
                      ->setFormatCode('#,##0.00');

                $sheet->getColumnDimension('A')->setAutoSize(true);
            },
        ];
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 28];
        foreach (range('B', 'N') as $col) {
            $widths[$col] = 12;
        }
        return $widths;
    }
}
