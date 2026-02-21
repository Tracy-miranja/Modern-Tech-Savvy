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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ShifMonthlySummaryExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $businessId;
    protected $year;

    public function __construct($businessId, $year)
    {
        $this->businessId = $businessId;
        $this->year = $year;
    }

    public function collection()
    {
        // Get all payrolls for the year
        $payrolls = Payroll::where('business_id', $this->businessId)
            ->where('payrun_year', $this->year)
            ->with(['employeePayrolls.employee.user'])
            ->get();

        // Group by employee
        $employeeData = [];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->employeePayrolls as $ep) {
                $employeeId = $ep->employee_id;
                $employeeName = $ep->employee->user->name ?? 'N/A';
                $month = (int) $payroll->payrun_month;

                // Initialize employee array if not exists
                if (!isset($employeeData[$employeeId])) {
                    $employeeData[$employeeId] = [
                        'name' => $employeeName,
                        'months' => array_fill(1, 12, 0), // Initialize all 12 months with 0
                        'total' => 0
                    ];
                }

                // Get SHIF amount
                $shifAmount = 0;
                if (isset($ep->shif)) {
                    $shifAmount = floatval($ep->shif);
                }

                // If shif is 0, check deductions JSON
                if ($shifAmount == 0) {
                    $deductions = json_decode($ep->deductions, true) ?? [];
                    $shifAmount = floatval($deductions['shif'] ?? $deductions['nhif'] ?? 0);
                }

                $employeeData[$employeeId]['months'][$month] = $shifAmount;
                $employeeData[$employeeId]['total'] += $shifAmount;
            }
        }

        // Convert to collection format for Excel
        $rows = collect();

        foreach ($employeeData as $data) {
            $row = [
                'name' => $data['name'],
            ];

            // Add all 12 months
            for ($i = 1; $i <= 12; $i++) {
                $amount = $data['months'][$i];
                $row["month_{$i}"] = $amount > 0 ? number_format($amount, 2, '.', ',') : null;
            }

            // Add total
            $row['total'] = number_format($data['total'], 2, '.', ',');

            $rows->push($row);
        }

        // Add grand total row
        $grandTotals = ['name' => 'Total'];
        $grandTotalSum = 0;

        for ($i = 1; $i <= 12; $i++) {
            $monthTotal = 0;
            foreach ($employeeData as $data) {
                $monthTotal += $data['months'][$i];
            }
            $grandTotals["month_{$i}"] = $monthTotal > 0 ? number_format($monthTotal, 2, '.', ',') : null;
            $grandTotalSum += $monthTotal;
        }

        $grandTotals['total'] = number_format($grandTotalSum, 2, '.', ',');
        $rows->push($grandTotals);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Name',
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
            'Total'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ]
            ],
            // Last row (totals) - make it bold
            $sheet->getHighestRow() => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9']
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Name column
            'B' => 12, // January
            'C' => 12, // February
            'D' => 12, // March
            'E' => 12, // April
            'F' => 12, // May
            'G' => 12, // June
            'H' => 12, // July
            'I' => 12, // August
            'J' => 12, // September
            'K' => 12, // October
            'L' => 12, // November
            'M' => 12, // December
            'N' => 15, // Total
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
