<?php

namespace App\Exports;

use App\Models\Business;
use App\Models\Payroll;
use App\Models\EmployeePayroll;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class PayrollVarianceExport implements WithMultipleSheets
{
    protected Business $business;
    protected array    $params;

    public function __construct(Business $business, array $params)
    {
        $this->business = $business;
        $this->params   = $params;
    }

    public function sheets(): array
    {
        return [
            new VarianceSummarySheet($this->business, $this->params),
            new VarianceDetailSheet($this->business, $this->params),
        ];
    }
}

trait VarianceHelper
{
    protected function fetchYearData(Business $business, int $year): array
    {
        $payrolls = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->with('employeePayrolls')
            ->get();

        $data = [];
        foreach ($payrolls as $p) {
            $m = intval($p->payrun_month);
            $data[$m] = [
                'gross' => floatval($p->employeePayrolls->sum('gross_pay')),
                'net'   => floatval($p->employeePayrolls->sum('net_pay')),
                'paye'  => floatval($p->employeePayrolls->sum('paye')),
                'nssf'  => floatval($p->employeePayrolls->sum('nssf')),
                'shif'  => floatval($p->employeePayrolls->sum('shif')),
                'hl'    => floatval($p->employeePayrolls->sum('housing_levy')),
                'count' => $p->employeePayrolls->count(),
            ];
        }
        return $data;
    }

    protected function fetchMonthData(Business $business, int $year, int $month): array
    {
        $payroll = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->where('payrun_month', $month)
            ->with('employeePayrolls')
            ->first();

        if (!$payroll) {
            return ['gross'=>0,'net'=>0,'paye'=>0,'nssf'=>0,'shif'=>0,'hl'=>0,'count'=>0];
        }
        return [
            'gross' => floatval($payroll->employeePayrolls->sum('gross_pay')),
            'net'   => floatval($payroll->employeePayrolls->sum('net_pay')),
            'paye'  => floatval($payroll->employeePayrolls->sum('paye')),
            'nssf'  => floatval($payroll->employeePayrolls->sum('nssf')),
            'shif'  => floatval($payroll->employeePayrolls->sum('shif')),
            'hl'    => floatval($payroll->employeePayrolls->sum('housing_levy')),
            'count' => $payroll->employeePayrolls->count(),
        ];
    }

    protected function variancePct(float $base, float $compare): float
    {
        return $base != 0 ? round((($compare - $base) / abs($base)) * 100, 2) : 0;
    }

    protected function status(float $variance): string
    {
        if ($variance > 0) return 'Increased';
        if ($variance < 0) return 'Decreased';
        return 'No Change';
    }

    protected function periodLabel(array $params, int $period): string
    {
        $months = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
                   7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
        if ($params['mode'] === 'year') {
            return (string) $params["year{$period}"];
        }
        $m = $months[$params["month{$period}"]];
        return "{$m} {$params["year{$period}"]}";
    }

    protected function sumYearField(array $yearData, string $field): float
    {
        return array_sum(array_column($yearData, $field));
    }
}

class VarianceSummarySheet implements
    FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    use VarianceHelper;

    protected Business $business;
    protected array    $params;

    public function __construct(Business $business, array $params)
    {
        $this->business = $business;
        $this->params   = $params;
    }

    public function title(): string { return 'Summary'; }

    public function collection(): Collection
    {
        $p      = $this->params;
        $rows   = collect();
        $fields = [
            'gross' => 'Gross Pay',
            'net'   => 'Net Pay',
            'paye'  => 'PAYE',
            'nssf'  => 'NSSF',
            'shif'  => 'SHIF',
            'hl'    => 'Housing Levy',
        ];

        if ($p['mode'] === 'year') {
            $d1 = $this->fetchYearData($this->business, $p['year1']);
            $d2 = $this->fetchYearData($this->business, $p['year2']);

            foreach ($fields as $key => $label) {
                $v1  = $this->sumYearField($d1, $key);
                $v2  = $this->sumYearField($d2, $key);
                $var = $v2 - $v1;
                $rows->push([$label, $v1, $v2, $var, $this->variancePct($v1, $v2), $this->status($var)]);
            }

            $cnt1 = $d1 ? round($this->sumYearField($d1, 'count') / count($d1), 1) : 0;
            $cnt2 = $d2 ? round($this->sumYearField($d2, 'count') / count($d2), 1) : 0;
            $rows->push(['Avg Monthly Headcount', $cnt1, $cnt2, round($cnt2 - $cnt1, 1), $this->variancePct($cnt1, $cnt2), $this->status($cnt2 - $cnt1)]);

        } else {
            $d1 = $this->fetchMonthData($this->business, $p['year1'], $p['month1']);
            $d2 = $this->fetchMonthData($this->business, $p['year2'], $p['month2']);

            foreach ($fields as $key => $label) {
                $var = $d2[$key] - $d1[$key];
                $rows->push([$label, $d1[$key], $d2[$key], $var, $this->variancePct($d1[$key], $d2[$key]), $this->status($var)]);
            }

            $var = $d2['count'] - $d1['count'];
            $rows->push(['Employee Count', $d1['count'], $d2['count'], $var, $this->variancePct($d1['count'], $d2['count']), $this->status($var)]);
        }

        return $rows;
    }

    public function headings(): array
    {
        $cur = $this->business->currency ?? 'KES';
        $p1  = $this->periodLabel($this->params, 1);
        $p2  = $this->periodLabel($this->params, 2);
        return ['Metric', "{$p1} ({$cur})", "{$p2} ({$cur})", "Variance ({$cur})", 'Variance %', 'Status'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
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

                $sheet->getStyle("B2:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00;(#,##0.00);"-"');
                $sheet->getStyle("E2:E{$lastRow}")->getNumberFormat()->setFormatCode('0.00"%"');
                $sheet->getStyle("B2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                for ($r = 2; $r <= $lastRow; $r++) {
                    $var = floatval($sheet->getCell("D{$r}")->getValue());
                    if ($var > 0) {
                        $sheet->getStyle("D{$r}:F{$r}")->getFont()->getColor()->setRGB('C0392B');
                        $sheet->getStyle("F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FADBD8');
                    } elseif ($var < 0) {
                        $sheet->getStyle("D{$r}:F{$r}")->getFont()->getColor()->setRGB('1E8449');
                        $sheet->getStyle("F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D5F5E3');
                    }
                    if ($r % 2 == 0) {
                        $sheet->getStyle("A{$r}:C{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF5FB');
                    }
                }
                $sheet->getStyle("A1:F{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 14, 'F' => 14];
    }
}

class VarianceDetailSheet implements
    FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    use VarianceHelper;

    protected Business $business;
    protected array    $params;

    public function __construct(Business $business, array $params)
    {
        $this->business = $business;
        $this->params   = $params;
    }

    public function title(): string
    {
        return $this->params['mode'] === 'year' ? 'Month-by-Month' : 'Employee Detail';
    }

    public function collection(): Collection
    {
        $p    = $this->params;
        $rows = collect();
        $monthNames = [
            1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
            7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',
        ];

        if ($p['mode'] === 'year') {
            $d1   = $this->fetchYearData($this->business, $p['year1']);
            $d2   = $this->fetchYearData($this->business, $p['year2']);
            $tot1 = $tot2 = 0;

            foreach ($monthNames as $m => $name) {
                $g1 = $d1[$m]['gross'] ?? 0;
                $g2 = $d2[$m]['gross'] ?? 0;
                $v  = $g2 - $g1;
                $tot1 += $g1; $tot2 += $g2;

                $rows->push([
                    $name,
                    $g1 > 0 ? $g1 : null,
                    $g2 > 0 ? $g2 : null,
                    ($g1 > 0 || $g2 > 0) ? $v : null,
                    ($g1 > 0 || $g2 > 0) ? $this->variancePct($g1, $g2) : null,
                    $d1[$m]['count'] ?? 0,
                    $d2[$m]['count'] ?? 0,
                ]);
            }

            $tv = $tot2 - $tot1;
            $rows->push(['TOTAL', $tot1, $tot2, $tv, $this->variancePct($tot1, $tot2), '', '']);

        } else {

            $payroll1 = Payroll::where('business_id', $this->business->id)
                ->where('payrun_year', $p['year1'])->where('payrun_month', $p['month1'])->first();
            $payroll2 = Payroll::where('business_id', $this->business->id)
                ->where('payrun_year', $p['year2'])->where('payrun_month', $p['month2'])->first();

            $eps1 = $payroll1
                ? EmployeePayroll::where('payroll_id', $payroll1->id)->with('employee.user')->get()->keyBy('employee_id')
                : collect();
            $eps2 = $payroll2
                ? EmployeePayroll::where('payroll_id', $payroll2->id)->with('employee.user')->get()->keyBy('employee_id')
                : collect();

            $allIds = $eps1->keys()->merge($eps2->keys())->unique();

            $tot1 = $tot2 = 0;
            foreach ($allIds as $empId) {
                $ep1  = $eps1->get($empId);
                $ep2  = $eps2->get($empId);
                $name = ($ep1 ?? $ep2)?->employee?->user?->name ?? 'N/A';
                $g1   = $ep1 ? floatval($ep1->gross_pay) : 0;
                $g2   = $ep2 ? floatval($ep2->gross_pay) : 0;
                $v    = $g2 - $g1;
                $tot1 += $g1; $tot2 += $g2;

                $rows->push([
                    $name,
                    $g1 > 0 ? $g1 : null,
                    $g2 > 0 ? $g2 : null,
                    $v,
                    $this->variancePct($g1, $g2),
                    $ep1 ? floatval($ep1->net_pay) : null,
                    $ep2 ? floatval($ep2->net_pay) : null,
                ]);
            }

            $tv = $tot2 - $tot1;
            $rows->push(['TOTAL', $tot1, $tot2, $tv, $this->variancePct($tot1, $tot2), '', '']);
        }

        return $rows;
    }

    public function headings(): array
    {
        $cur = $this->business->currency ?? 'KES';
        $p1  = $this->periodLabel($this->params, 1);
        $p2  = $this->periodLabel($this->params, 2);

        if ($this->params['mode'] === 'year') {
            return ['Month', "Gross {$p1} ({$cur})", "Gross {$p2} ({$cur})", "Variance ({$cur})", 'Variance %', "Headcount {$p1}", "Headcount {$p2}"];
        }
        return ['Employee', "Gross {$p1} ({$cur})", "Gross {$p2} ({$cur})", "Variance ({$cur})", 'Variance %', "Net Pay {$p1} ({$cur})", "Net Pay {$p2} ({$cur})"];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
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

                $sheet->getStyle("B2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00;(#,##0.00);"-"');
                $sheet->getStyle("E2:E{$lastRow}")->getNumberFormat()->setFormatCode('0.00"%"');
                $sheet->getStyle("B2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                for ($r = 2; $r < $lastRow; $r++) {
                    $var = floatval($sheet->getCell("D{$r}")->getValue());
                    if ($var > 0) {
                        $sheet->getStyle("D{$r}:E{$r}")->getFont()->getColor()->setRGB('C0392B');
                    } elseif ($var < 0) {
                        $sheet->getStyle("D{$r}:E{$r}")->getFont()->getColor()->setRGB('1E8449');
                    }
                    if ($r % 2 == 0) {
                        $sheet->getStyle("A{$r}:G{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF5FB');
                    }
                }

                $sheet->getStyle("A{$lastRow}:G{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                ]);

                $sheet->getStyle("A1:G{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 20, 'C' => 20, 'D' => 18, 'E' => 13, 'F' => 18, 'G' => 18];
    }
}
