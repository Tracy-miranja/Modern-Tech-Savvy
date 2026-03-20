<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\EmployeePayroll;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class MasterRollExport implements WithMultipleSheets
{
    public function __construct(
        protected Payroll $payroll,
        protected object  $business,
        protected string  $type              = 'detailed',
        protected ?string $groupBy           = null,
        protected array   $filteredEmployeeIds = []   // ← NEW: empty = no filter (all employees)
    ) {}

    public function sheets(): array
    {
        $currency = $this->payroll->currency ?? 'KES';

        // ── Build the base query, scoped to filtered employees when provided ─
        $query = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with([
                'employee.user',
                'employee.location',
                'employee.employmentDetails.department',
                'employee.employmentDetails.jobCategory',
            ]);

        // Only restrict to the filtered subset when IDs were passed in
        if (!empty($this->filteredEmployeeIds)) {
            $query->whereIn('employee_id', $this->filteredEmployeeIds);
        }

        $allEps = $query->get();

        $statutoryLower    = [
            'shif', 'nssf', 'paye', 'housing levy', 'helb',
            'absenteeism', 'absenteeism charge',
        ];
        $skipAllowanceLower = ['overtime allowance'];

        $allowanceNames = [];
        $deductionNames = [];

        $collectItems = function (?string $allowanceJson, ?string $deductionJson)
            use (&$allowanceNames, &$deductionNames, $statutoryLower, $skipAllowanceLower): void
        {
            foreach ((json_decode($allowanceJson ?? '[]', true) ?? []) as $item) {
                if (!is_array($item)) continue;
                $iname = trim($item['item_name'] ?? '');
                if ($iname === '') continue;
                if (in_array(strtolower($iname), $skipAllowanceLower)) continue;
                $key = strtolower($iname);
                if (!isset($allowanceNames[$key])) $allowanceNames[$key] = $iname;
            }
            foreach ((json_decode($deductionJson ?? '[]', true) ?? []) as $item) {
                if (!is_array($item)) continue;
                $iname = trim($item['item_name'] ?? '');
                if ($iname === '') continue;
                if (in_array(strtolower($iname), $statutoryLower)) continue;
                $key = strtolower($iname);
                if (!isset($deductionNames[$key])) $deductionNames[$key] = $iname;
            }
        };

        foreach ($allEps as $ep) {
            $collectItems($ep->allowances, $ep->deductions);
        }

        // Also collect from payroll_settings for the same (filtered) employees
        $employeeIds = $allEps->pluck('employee_id')->filter()->unique()->values();
        if ($employeeIds->isNotEmpty()) {
            \Illuminate\Support\Facades\DB::table('payroll_settings')
                ->where('year', $this->payroll->payrun_year)
                ->where('month', $this->payroll->payrun_month)
                ->whereIn('employee_id', $employeeIds)
                ->get(['allowances', 'deductions'])
                ->each(fn($ps) => $collectItems($ps->allowances, $ps->deductions));
        }

        if ($this->groupBy) {
            $grouped = $allEps->groupBy(fn($ep) => match ($this->groupBy) {
                'location'     => $ep->employee?->location?->name ?? 'Head Office',
                'department'   => $ep->employee?->employmentDetails?->department?->name ?? 'Unassigned',
                'job_category' => $ep->employee?->employmentDetails?->jobCategory?->name ?? 'Unassigned',
                default        => 'All',
            });

            return $grouped->map(fn($eps, $groupName) => new MasterRollSheet(
                $eps, $this->payroll, $this->business, $currency,
                $this->type, $allowanceNames, $deductionNames,
                substr($groupName, 0, 31)
            ))->values()->all();
        }

        return [new MasterRollSheet(
            $allEps, $this->payroll, $this->business, $currency,
            $this->type, $allowanceNames, $deductionNames,
            'Master Roll'
        )];
    }
}


// ============================================================================
class MasterRollSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    private int $dataStartRow = 5;
    private int $totalsRow    = 5;
    private int $totalCols    = 0;

    // ── STATUTORY now includes Personal Relief + Insurance Relief ──────────
    // Order: SHIF | NSSF | Housing Levy | Taxable Income | Personal Relief | Ins. Relief | PAYE
    private array $statutory = [
        'SHIF',
        'NSSF',
        'Housing Levy',
        'Taxable Income',
        'Personal Relief',
        'Ins. Relief',
        'PAYE',
    ];

    private array $C = [
        'dark_navy' => '1A1A2E',
        'mid_blue'  => '16213E',
        'green'     => '155724',
        'red'       => '721C24',
        'orange'    => '7D4A00',
        'purple'    => '4A1A6B',
        'teal'      => '005B5B',
        'grey'      => '343A40',
        'white'     => 'FFFFFF',
        'row_odd'   => 'F0F4FF',
        'row_even'  => 'FFFFFF',
    ];

    public function __construct(
        private $eps,
        private Payroll $payroll,
        private object  $business,
        private string  $currency,
        private string  $type,
        private array   $allowanceNames,
        private array   $deductionNames,
        private string  $sheetTitle
    ) {
        $this->totalCols = count($this->buildColumnHeader());
    }

    public function title(): string { return $this->sheetTitle; }

    // ------------------------------------------------------------------
    public function array(): array
    {
        $colCount = $this->totalCols;
        $blankRow = array_fill(0, $colCount, '');

        $rows   = [];
        $rows[] = $blankRow;                    // row 1 — filled in styles()
        $rows[] = $blankRow;                    // row 2 — filled in styles()
        $rows[] = $this->buildGroupRow();       // row 3
        $rows[] = $this->buildColumnHeader();   // row 4

        $rowNum    = 0;
        $numTotals = [];

        foreach ($this->eps as $ep) {
            $rowNum++;
            $dataRow = $this->buildDataRow($ep, $rowNum);
            $rows[]  = $dataRow;
            foreach ($dataRow as $ci => $v) {
                if (is_numeric($v)) {
                    $numTotals[$ci] = ($numTotals[$ci] ?? 0) + $v;
                }
            }
        }

        // Totals row
        $totals = [];
        for ($i = 0; $i < $colCount; $i++) {
            $totals[] = $i === 0 ? 'TOTALS'
                      : (isset($numTotals[$i]) ? round($numTotals[$i], 2) : '');
        }
        $rows[] = $totals;

        $this->dataStartRow = 5;
        $this->totalsRow    = 5 + $rowNum;

        return $rows;
    }

    // ------------------------------------------------------------------
    private function buildGroupRow(): array
    {
        // 5 employee-info cols
        $h = ['', '', '', '', ''];

        // Allowances
        foreach ($this->allowanceNames as $n) { $h[] = 'ALLOWANCES'; }

        $h[] = 'OVERTIME';
        $h[] = 'GROSS PAY';

        // STATUTORY — 7 cols
        foreach ($this->statutory as $s) { $h[] = 'STATUTORY DEDUCTIONS'; }

        // Custom deductions
        foreach ($this->deductionNames as $n) { $h[] = 'CUSTOM DEDUCTIONS'; }

        // OTHER DEDUCTIONS — 3 cols (Absenteeism, Loan, Advance)
        for ($i = 0; $i < 3; $i++) { $h[] = 'OTHER DEDUCTIONS'; }

        $h[] = 'NET PAY';

        // Attendance & Bank — 5 cols
        for ($i = 0; $i < 5; $i++) { $h[] = 'ATTENDANCE & BANK'; }

        return $h;
    }

    // ------------------------------------------------------------------
    private function buildColumnHeader(): array
    {
        $c = $this->currency;

        $h = ['#', 'Employee Name', 'Employee Code', 'Tax PIN (KRA)', "Basic Salary\n({$c})"];

        foreach ($this->allowanceNames as $key => $name) {
            $h[] = "{$name}\n({$c})";
        }

        $h[] = "Overtime\n({$c})";
        $h[] = "Gross Pay\n({$c})";

        // Statutory columns — 7 total
        $h[] = "SHIF\n({$c})";
        $h[] = "NSSF\n({$c})";
        $h[] = "Housing Levy\n({$c})";
        $h[] = "Taxable Income\n({$c})";
        $h[] = "Personal Relief\n({$c})";
        $h[] = "Ins. Relief\n({$c})";
        $h[] = "PAYE\n({$c})";

        // Custom deductions
        foreach ($this->deductionNames as $key => $name) {
            $h[] = "{$name}\n({$c})";
        }

        // Other deductions — 3 cols only
        $h[] = "Absenteeism\n({$c})";
        $h[] = "Loan Repayment\n({$c})";
        $h[] = "Advance Recovery\n({$c})";

        $h[] = "NET PAY\n({$c})";
        $h[] = 'Days Present';
        $h[] = 'Days Absent';
        $h[] = 'Days in Month';
        $h[] = 'Bank Name';
        $h[] = 'Account Number';

        return $h;
    }

    // ------------------------------------------------------------------
    private function buildDataRow(EmployeePayroll $ep, int $rowNum): array
    {
        $statutoryLower = ['shif', 'nssf', 'paye', 'housing levy'];

        // ── Parse allowances ──────────────────────────────────────────────
        $allowanceByKey  = [];
        $overtimeFromAll = 0.0;

        $parseAllowances = function (array $items) use (&$allowanceByKey, &$overtimeFromAll): void {
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $iname = trim($item['item_name'] ?? '');
                if ($iname === '') continue;
                $amt = (float)($item['amount'] ?? 0);
                if (strtolower($iname) === 'overtime allowance') {
                    $overtimeFromAll += $amt;
                    continue;
                }
                $key = strtolower($iname);
                if (!isset($allowanceByKey[$key])) {
                    $allowanceByKey[$key] = $amt;
                }
            }
        };

        $parseAllowances(json_decode($ep->allowances, true) ?? []);

        $ps = \Illuminate\Support\Facades\DB::table('payroll_settings')
            ->where('employee_id', $ep->employee_id)
            ->where('year',        $this->payroll->payrun_year)
            ->where('month',       $this->payroll->payrun_month)
            ->first(['allowances', 'deductions']);

        if ($ps) {
            $parseAllowances(json_decode($ps->allowances ?? '[]', true) ?? []);
        }

        // ── Parse deductions ──────────────────────────────────────────────
        $deductionByKey = [];
        $absenteeismAmt = 0.0;

        $parseDeductions = function (array $items) use (&$deductionByKey, &$absenteeismAmt, $statutoryLower): void {
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $iname = trim($item['item_name'] ?? '');
                if ($iname === '') continue;
                $nl  = strtolower($iname);
                $amt = (float)($item['amount'] ?? 0);
                if (str_contains($nl, 'absenteeism')) {
                    $absenteeismAmt += $amt;
                } elseif (!in_array($nl, $statutoryLower)) {
                    $key = $nl;
                    if (!isset($deductionByKey[$key])) {
                        $deductionByKey[$key] = $amt;
                    }
                }
            }
        };

        $parseDeductions(json_decode($ep->deductions, true) ?? []);
        if ($ps) {
            $parseDeductions(json_decode($ps->deductions ?? '[]', true) ?? []);
        }

        $overtimeJson = json_decode($ep->overtime, true) ?? [];
        $overtimeAmt  = (float)($overtimeJson['amount'] ?? $overtimeFromAll);

        $basicSalary = (float)(
            $ep->basic_salary
            ?? $ep->basic_pay
            ?? $ep->employee?->employmentDetails?->basic_salary
            ?? $ep->employee?->employmentDetails?->salary
            ?? 0
        );

        // ── Resolve personal/insurance relief ────────────────────────────
        $reliefs = json_decode($ep->reliefs, true) ?? [];
        $personalRelief  = 0.0;
        $insuranceRelief = 0.0;
        foreach ($reliefs as $r) {
            if (!is_array($r)) continue;
            $rname = strtolower(trim($r['item_name'] ?? ''));
            $ramt  = (float)($r['amount'] ?? 0);
            if (str_contains($rname, 'personal'))  $personalRelief  += $ramt;
            if (str_contains($rname, 'insurance')) $insuranceRelief += $ramt;
        }
        $personalRelief  = (float)($ep->personal_relief  ?? $personalRelief);
        $insuranceRelief = (float)($ep->insurance_relief ?? $insuranceRelief);

        // ── Build row ─────────────────────────────────────────────────────
        $row = [
            $rowNum,
            $ep->employee?->user?->name ?? 'N/A',
            $ep->employee?->employee_code ?? 'N/A',
            $ep->employee?->tax_no ?? 'N/A',
            $basicSalary,
        ];

        // Allowances
        foreach ($this->allowanceNames as $key => $name) {
            $row[] = $allowanceByKey[$key] ?? 0.0;
        }

        $row[] = $overtimeAmt;
        $row[] = (float)($ep->gross_pay ?? 0);

        // ── STATUTORY — 7 cols ────────────────────────────────────────────
        $row[] = (float)($ep->shif           ?? 0);
        $row[] = (float)($ep->nssf           ?? 0);
        $row[] = (float)($ep->housing_levy   ?? 0);
        $row[] = (float)($ep->taxable_income ?? 0);
        $row[] = $personalRelief;
        $row[] = $insuranceRelief;
        $row[] = (float)($ep->paye           ?? 0);

        // Custom deductions
        foreach ($this->deductionNames as $key => $name) {
            $row[] = $deductionByKey[$key] ?? 0.0;
        }

        // ── OTHER DEDUCTIONS — 3 cols ─────────────────────────────────────
        $row[] = $absenteeismAmt;
        $row[] = (float)($ep->loan_repayment   ?? 0);
        $row[] = (float)($ep->advance_recovery ?? 0);

        $row[] = (float)($ep->net_pay ?? 0);
        $row[] = (int)  ($ep->attendance_present ?? 0);
        $row[] = (int)  ($ep->attendance_absent  ?? 0);
        $row[] = (int)  ($ep->days_in_month      ?? 0);
        $row[] = $ep->bank_name      ?? '';
        $row[] = $ep->account_number ?? '';

        return $row;
    }

    // ==================================================================
    // STYLES
    // ==================================================================
    public function styles(Worksheet $sheet): array
    {
        $company   = $this->business->company_name ?? $this->business->name ?? 'Company';
        $monthName = \Carbon\Carbon::createFromFormat('m', $this->payroll->payrun_month)->format('F');
        $period    = "{$monthName} {$this->payroll->payrun_year}";

        $lastCol = Coordinate::stringFromColumnIndex($this->totalCols);
        $NAVY    = $this->C['dark_navy'];
        $WHITE   = $this->C['white'];

        $sheet->freezePane('F5');

        // ── Header rows 1 & 2 ────────────────────────────────────────────
        $sheet->getStyle("A1:D2")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '343A40']],
        ]);

        $sheet->mergeCells("E1:{$lastCol}1");
        $sheet->getCell('E1')->setValue($company);
        $sheet->getStyle("E1")->applyFromArray([
            'font'      => ['name' => 'Arial', 'bold' => true, 'size' => 14, 'color' => ['rgb' => $WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells("E2:{$lastCol}2");
        $sheet->getCell('E2')->setValue("Master Payroll Roll  |  Period: {$period}  |  Currency: {$this->currency}");
        $sheet->getStyle("E2")->applyFromArray([
            'font'      => ['name' => 'Arial', 'size' => 9, 'color' => ['rgb' => 'CCCCCC']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '16213E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Group header row 3 ────────────────────────────────────────────
        $this->applyGroupHeader($sheet);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // ── Column header row 4 ───────────────────────────────────────────
        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font'      => ['name' => 'Arial', 'bold' => true, 'size' => 8, 'color' => ['rgb' => $WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '555555']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(52);
        $this->applyColumnHeaderColors($sheet);

        // ── Data rows alternating ─────────────────────────────────────────
        for ($r = $this->dataStartRow; $r < $this->totalsRow; $r++) {
            $bg = ($r % 2 === 0) ? $this->C['row_odd'] : $this->C['row_even'];
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 8],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $bg]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // ── Totals row ────────────────────────────────────────────────────
        $tr = $this->totalsRow;
        $sheet->getStyle("A{$tr}:{$lastCol}{$tr}")->applyFromArray([
            'font'    => ['name' => 'Arial', 'bold' => true, 'size' => 8, 'color' => ['rgb' => $WHITE]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $NAVY]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '888888']]],
        ]);

        // ── Number formats ────────────────────────────────────────────────
        $numFmt  = '#,##0.00;(#,##0.00);"-"';
        $intFmt  = '#,##0';
        $monStart = Coordinate::stringFromColumnIndex(6);
        $monEnd   = Coordinate::stringFromColumnIndex($this->totalCols - 5);
        $daysStart = Coordinate::stringFromColumnIndex($this->totalCols - 4);
        $daysEnd   = Coordinate::stringFromColumnIndex($this->totalCols - 2);

        $sheet->getStyle("{$monStart}{$this->dataStartRow}:{$monEnd}{$tr}")
              ->getNumberFormat()->setFormatCode($numFmt);
        $sheet->getStyle("{$monStart}{$this->dataStartRow}:{$monEnd}{$tr}")
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("{$daysStart}{$this->dataStartRow}:{$daysEnd}{$tr}")
              ->getNumberFormat()->setFormatCode($intFmt);

        $sheet->getStyle("A{$this->dataStartRow}:A{$tr}")
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Net Pay column bold
        $netPayL = Coordinate::stringFromColumnIndex($this->totalCols - 5);
        $sheet->getStyle("{$netPayL}{$this->dataStartRow}:{$netPayL}" . ($tr - 1))
              ->getFont()->setBold(true);

        return [];
    }

    // ------------------------------------------------------------------
    private function applyGroupHeader(Worksheet $sheet): void
    {
        $col = 1;

        // Employee Info — 5 cols
        $this->mergeGroup($sheet, $col, $col + 4, 'EMPLOYEE INFO', $this->C['grey']);
        $col += 5;

        // Allowances
        $cnt = count($this->allowanceNames);
        if ($cnt > 0) {
            $this->mergeGroup($sheet, $col, $col + $cnt - 1, 'ALLOWANCES', $this->C['green']);
            $col += $cnt;
        }

        // Overtime
        $this->mergeGroup($sheet, $col, $col, 'OVERTIME', $this->C['teal']);
        $col++;

        // Gross Pay
        $this->mergeGroup($sheet, $col, $col, 'GROSS PAY', $this->C['mid_blue']);
        $col++;

        // STATUTORY DEDUCTIONS — 7 cols
        $statCount = count($this->statutory);
        $this->mergeGroup($sheet, $col, $col + $statCount - 1, 'STATUTORY DEDUCTIONS', $this->C['red']);
        $col += $statCount;

        // Custom deductions
        $cnt = count($this->deductionNames);
        if ($cnt > 0) {
            $this->mergeGroup($sheet, $col, $col + $cnt - 1, 'CUSTOM DEDUCTIONS', $this->C['orange']);
            $col += $cnt;
        }

        // OTHER DEDUCTIONS — 3 cols (Absenteeism, Loan, Advance)
        $this->mergeGroup($sheet, $col, $col + 2, 'OTHER DEDUCTIONS', $this->C['purple']);
        $col += 3;

        // Net Pay
        $this->mergeGroup($sheet, $col, $col, 'NET PAY', $this->C['dark_navy']);
        $col++;

        // Attendance & Bank — remaining cols
        $this->mergeGroup($sheet, $col, $this->totalCols, 'ATTENDANCE & BANK', $this->C['teal']);
    }

    private function mergeGroup(Worksheet $sheet, int $s, int $e, string $label, string $bg): void
    {
        $sL = Coordinate::stringFromColumnIndex($s);
        $eL = Coordinate::stringFromColumnIndex($e);
        if ($s !== $e) $sheet->mergeCells("{$sL}3:{$eL}3");
        $sheet->getCell("{$sL}3")->setValue($label);
        $sheet->getStyle("{$sL}3")->applyFromArray([
            'font'      => ['name' => 'Arial', 'bold' => true, 'size' => 8, 'color' => ['rgb' => $this->C['white']]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $bg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
    }

    // ------------------------------------------------------------------
    private function applyColumnHeaderColors(Worksheet $sheet): void
    {
        $col = 1;

        // Employee Info — 5 cols
        for ($i = 0; $i < 5; $i++) { $this->colorCol($sheet, $col++, $this->C['grey']); }

        // Allowances
        foreach ($this->allowanceNames as $k => $n) { $this->colorCol($sheet, $col++, $this->C['green']); }

        // Overtime
        $this->colorCol($sheet, $col++, $this->C['teal']);

        // Gross Pay
        $this->colorCol($sheet, $col++, $this->C['mid_blue']);

        // Statutory — 7 cols all red
        foreach ($this->statutory as $s) { $this->colorCol($sheet, $col++, $this->C['red']); }

        // Custom deductions
        foreach ($this->deductionNames as $k => $n) { $this->colorCol($sheet, $col++, $this->C['orange']); }

        // Other deductions — 3 cols only
        for ($i = 0; $i < 3; $i++) { $this->colorCol($sheet, $col++, $this->C['purple']); }

        // Net Pay
        $this->colorCol($sheet, $col++, $this->C['dark_navy']);

        // Attendance & Bank — remaining cols
        while ($col <= $this->totalCols) { $this->colorCol($sheet, $col++, $this->C['teal']); }
    }

    private function colorCol(Worksheet $sheet, int $ci, string $rgb): void
    {
        $sheet->getStyle(Coordinate::stringFromColumnIndex($ci) . "4")
              ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }
}
