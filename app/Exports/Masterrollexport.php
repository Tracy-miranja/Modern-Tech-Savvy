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

/**
 * MasterRollExport
 *
 * FIX 1: Header layout — company name & period are placed in a right-side block
 *         (columns E onward) matching the target Excel image. Columns A–D on rows 1–2
 *         are left blank so the EMPLOYEE INFO group colour fills them cleanly.
 *
 * FIX 2: Allowance / Deduction columns — ALL item_names present in the payroll JSON
 *         are included as columns regardless of is_active or amount. A column shows 0
 *         if a particular employee has no value for it, but the column always exists.
 *
 * FIX 3: Row numbering — removed the empty spacer row (old row 3) so:
 *         Row 1 = company title block
 *         Row 2 = period/currency info
 *         Row 3 = group header band  (previously row 4)
 *         Row 4 = column sub-headers (previously row 5)
 *         Row 5+ = data rows         (previously row 6+)
 *
 * FIX 4: $totalCols initialised early so styles() always has the correct value
 *         even if called before array() by the framework.
 */
class MasterRollExport implements WithMultipleSheets
{
    public function __construct(
        protected Payroll $payroll,
        protected object  $business,
        protected string  $type    = 'detailed',
        protected ?string $groupBy = null
    ) {}

    public function sheets(): array
    {
        $currency = $this->payroll->currency ?? 'KES';

        $allEps = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with([
                'employee.user',
                'employee.location',
                'employee.employmentDetails.department',
                'employee.employmentDetails.jobCategory',
            ])
            ->get();

        // Statutory item_names to exclude from custom deductions column
        $statutoryLower = [
            'shif', 'nssf', 'paye', 'housing levy', 'helb',
            'absenteeism', 'absenteeism charge',
        ];

        // Allowance item_names that are handled separately (not dynamic columns)
        $skipAllowanceLower = [
            'overtime allowance',
        ];

        // Collect every unique allowance/deduction item_name.
        // We read from BOTH EmployeePayroll AND PayrollSettings for this payroll's
        // month/year so that all configured items appear as columns even if
        // EmployeePayroll stores allowances in a different structure.
        $allowanceNames = [];
        $deductionNames = [];

        // Helper closure — processes one JSON string's items into the name maps
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

        // Pass 1 — from EmployeePayroll records
        foreach ($allEps as $ep) {
            $collectItems($ep->allowances, $ep->deductions);
        }

        // Pass 2 — from payroll_settings table for this payroll's month/year.
        // Uses DB facade directly to avoid any model name ambiguity.
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
    // ── FIX 3: 4-row header (no blank spacer row) ──────────────────────────
    private int $dataStartRow = 5;   // data begins on row 5
    private int $totalsRow    = 5;
    private int $totalCols    = 0;

    // Fixed statutory columns always in this order
    private array $statutory = ['SHIF', 'NSSF', 'Housing Levy', 'Taxable Income', 'PAYE'];

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
        // ── FIX 4: pre-calculate totalCols so styles() always has the value ──
        $this->totalCols = count($this->buildColumnHeader());
    }

    public function title(): string { return $this->sheetTitle; }

    // ------------------------------------------------------------------
    public function array(): array
    {
        // Title rows 1 & 2 are written directly to cells inside styles() using
        // $sheet->setCellValue(). This avoids putting non-uniform rows into the
        // FromArray data stream, which was causing Laravel Excel to miscalculate
        // the column count and silently drop the dynamic allowance columns.
        //
        // Row layout:
        //   Row 1  → blank placeholder (title injected in styles)
        //   Row 2  → blank placeholder (period injected in styles)
        //   Row 3  → group header band
        //   Row 4  → column sub-headers
        //   Row 5+ → data rows
        //   Last   → totals row

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
        $h = ['', '', '', '', ''];                                        // 5 employee-info cols (# | Name | Code | PIN | Basic Salary)
        foreach ($this->allowanceNames as $n) { $h[] = 'ALLOWANCES'; }
        $h[] = 'OVERTIME';
        $h[] = 'GROSS PAY';
        foreach ($this->statutory as $s)      { $h[] = 'STATUTORY DEDUCTIONS'; }
        foreach ($this->deductionNames as $n) { $h[] = 'CUSTOM DEDUCTIONS'; }
        for ($i = 0; $i < 5; $i++)            { $h[] = 'OTHER DEDUCTIONS & RELIEFS'; }
        $h[] = 'NET PAY';
        for ($i = 0; $i < 5; $i++)            { $h[] = 'ATTENDANCE & BANK'; }
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

        foreach ($this->statutory as $s) { $h[] = "{$s}\n({$c})"; }

        foreach ($this->deductionNames as $key => $name) {
            $h[] = "{$name}\n({$c})";
        }

        $h[] = "Absenteeism\n({$c})";
        $h[] = "Loan Repayment\n({$c})";
        $h[] = "Advance Recovery\n({$c})";
        $h[] = "Personal Relief\n({$c})";
        $h[] = "Insurance Relief\n({$c})";
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
                // Only overwrite if not already set from ep->allowances (ep takes priority)
                $key = strtolower($iname);
                if (!isset($allowanceByKey[$key])) {
                    $allowanceByKey[$key] = $amt;
                }
            }
        };

        // Primary source: EmployeePayroll->allowances
        $parseAllowances(json_decode($ep->allowances, true) ?? []);

        // Fallback: payroll_settings for this employee/month/year
        // (fills in any items missing from ep->allowances)
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

        // ── Basic salary ──────────────────────────────────────────────────
        // Prefer ep->basic_salary; fall back to ep->basic_pay or employee->basic_salary
        $basicSalary = (float)(
            $ep->basic_salary
            ?? $ep->basic_pay
            ?? $ep->employee?->employmentDetails?->basic_salary
            ?? $ep->employee?->employmentDetails?->salary
            ?? 0
        );

        $row = [
            $rowNum,
            $ep->employee?->user?->name ?? 'N/A',
            $ep->employee?->employee_code ?? 'N/A',
            $ep->employee?->tax_no ?? 'N/A',
            $basicSalary,
        ];

        foreach ($this->allowanceNames as $key => $name) {
            $row[] = $allowanceByKey[$key] ?? 0.0;
        }

        $row[] = $overtimeAmt;
        $row[] = (float)($ep->gross_pay ?? 0);

        $row[] = (float)($ep->shif         ?? 0);
        $row[] = (float)($ep->nssf         ?? 0);
        $row[] = (float)($ep->housing_levy ?? 0);
        $row[] = (float)($ep->taxable_income ?? 0);   // Taxable Income — statutory col before PAYE
        $row[] = (float)($ep->paye         ?? 0);

        foreach ($this->deductionNames as $key => $name) {
            $row[] = $deductionByKey[$key] ?? 0.0;
        }

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

        $row[] = $absenteeismAmt;
        $row[] = (float)($ep->loan_repayment   ?? 0);
        $row[] = (float)($ep->advance_recovery ?? 0);
        $row[] = (float)($ep->personal_relief  ?? $personalRelief);
        $row[] = (float)($ep->insurance_relief ?? $insuranceRelief);
        $row[] = (float)($ep->net_pay          ?? 0);
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

        // Freeze pane: keep first 4 cols + header rows visible while scrolling
        $sheet->freezePane('F5');

        // ── Header rows 1 & 2: write values directly to cells ─────────────
        // Columns A-D rows 1-2: grey background, empty
        $sheet->getStyle("A1:D2")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '343A40']],
        ]);

        // Company name: merge E1 to last column, right-aligned
        $sheet->mergeCells("E1:{$lastCol}1");
        $sheet->getCell('E1')->setValue($company);
        $sheet->getStyle("E1")->applyFromArray([
            'font'      => ['name' => 'Arial', 'bold' => true, 'size' => 14, 'color' => ['rgb' => $WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Period / currency: merge E2 to last column
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

        $this->mergeGroup($sheet, $col, $col + 4,  'EMPLOYEE INFO',              $this->C['grey']);     $col += 5;
        $cnt = count($this->allowanceNames);
        if ($cnt > 0) {
            $this->mergeGroup($sheet, $col, $col + $cnt - 1, 'ALLOWANCES',       $this->C['green']);    $col += $cnt;
        }
        $this->mergeGroup($sheet, $col, $col,      'OVERTIME',                   $this->C['teal']);     $col++;
        $this->mergeGroup($sheet, $col, $col,      'GROSS PAY',                  $this->C['mid_blue']); $col++;
        $this->mergeGroup($sheet, $col, $col + 4,  'STATUTORY DEDUCTIONS',       $this->C['red']);      $col += 5;
        $cnt = count($this->deductionNames);
        if ($cnt > 0) {
            $this->mergeGroup($sheet, $col, $col + $cnt - 1, 'CUSTOM DEDUCTIONS', $this->C['orange']); $col += $cnt;
        }
        $this->mergeGroup($sheet, $col, $col + 4,  'OTHER DEDUCTIONS & RELIEFS', $this->C['purple']); $col += 5;
        $this->mergeGroup($sheet, $col, $col,      'NET PAY',                    $this->C['dark_navy']); $col++;
        $this->mergeGroup($sheet, $col, $this->totalCols, 'ATTENDANCE & BANK',   $this->C['teal']);
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
        for ($i = 0; $i < 5; $i++)                          { $this->colorCol($sheet, $col++, $this->C['grey']); }
        foreach ($this->allowanceNames as $k => $n)          { $this->colorCol($sheet, $col++, $this->C['green']); }
        $this->colorCol($sheet, $col++, $this->C['teal']);     // Overtime
        $this->colorCol($sheet, $col++, $this->C['mid_blue']); // Gross Pay
        foreach ($this->statutory as $s)                     { $this->colorCol($sheet, $col++, $this->C['red']); }
        foreach ($this->deductionNames as $k => $n)          { $this->colorCol($sheet, $col++, $this->C['orange']); }
        for ($i = 0; $i < 5; $i++)                           { $this->colorCol($sheet, $col++, $this->C['purple']); }
        $this->colorCol($sheet, $col++, $this->C['dark_navy']); // Net Pay
        while ($col <= $this->totalCols)                     { $this->colorCol($sheet, $col++, $this->C['teal']); }
    }

    private function colorCol(Worksheet $sheet, int $ci, string $rgb): void
    {
        $sheet->getStyle(Coordinate::stringFromColumnIndex($ci) . "4")
              ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }
}
