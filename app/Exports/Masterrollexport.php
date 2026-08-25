<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\EmployeePayroll;
use App\Services\ThirdRuleService;
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
        protected string  $type                = 'detailed',
        protected ?string $groupBy             = null,
        protected array   $filteredEmployeeIds = []
    ) {}

    public function sheets(): array
    {
        $currency = $this->payroll->currency ?? 'KES';

        $query = EmployeePayroll::where('payroll_id', $this->payroll->id)
            ->with([
                'employee.user',
                'employee.location',
                'employee.employmentDetails.department',
                'employee.employmentDetails.jobCategory',
            ]);

        if (!empty($this->filteredEmployeeIds)) {
            $query->whereIn('employee_id', $this->filteredEmployeeIds);
        }

        $allEps = $query->get();

        $skipAllowanceLower = ['overtime allowance'];
        $skipDeductionLower = [
            'shif', 'nssf', 'paye', 'housing levy', 'helb',
            'absenteeism', 'absenteeism charge',
        ];

        $allowanceNames = [];
        $deductionNames = [];

        $collectItems = function ($allowanceRaw, $deductionRaw)
            use (&$allowanceNames, &$deductionNames, $skipAllowanceLower, $skipDeductionLower): void
        {
            foreach (MasterRollSheet::decodeJsonField($allowanceRaw) as $lc => $item) {
                if (in_array($lc, $skipAllowanceLower)) continue;
                if (!isset($allowanceNames[$lc])) $allowanceNames[$lc] = $item['display_name'];
            }
            foreach (MasterRollSheet::decodeJsonField($deductionRaw) as $lc => $item) {
                if (in_array($lc, $skipDeductionLower)) continue;
                if (!isset($deductionNames[$lc])) $deductionNames[$lc] = $item['display_name'];
            }
        };

        foreach ($allEps as $ep) {
            $collectItems($ep->getRawOriginal('allowances'), $ep->getRawOriginal('deductions'));
        }

        $employeeIds = $allEps->pluck('employee_id')->filter()->unique()->values();
        if ($employeeIds->isNotEmpty()) {
            \Illuminate\Support\Facades\DB::table('payroll_settings')
                ->where('year',  $this->payroll->payrun_year)
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

class MasterRollSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    private int $dataStartRow = 5;
    private int $totalsRow    = 5;
    private int $totalCols    = 0;

    private array $thirdRuleStatusByRow = [];

    private array $statutory = [
        'SHIF', 'NSSF', 'Housing Levy', 'Taxable Income',
        'Personal Relief', 'Ins. Relief', 'PAYE',
    ];

    private array $thirdRuleCols = [
        '1/3 Rule Basis',
        'Max Allowed Deductions (2/3)',
        'Max Voluntary Available',
        'Total Deductions',
        '1/3 Rule Status',
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
        'gold'      => '8B6F00',
        'white'     => 'FFFFFF',
        'row_odd'   => 'F0F4FF',
        'row_even'  => 'FFFFFF',
        'pass_bg'   => 'C6EFCE',
        'pass_fg'   => '155724',
        'fail_bg'   => 'FFC7CE',
        'fail_fg'   => '721C24',
    ];

    private array $skipDeductionLower = [
        'shif', 'nssf', 'paye', 'housing levy', 'helb',
        'absenteeism', 'absenteeism charge',
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

    public static function decodeJsonField($raw): array
    {

        $decoded = $raw;

        $maxPasses = 4;
        while (is_string($decoded) && $maxPasses-- > 0) {
            $attempt = json_decode($decoded, true);
            if (json_last_error() !== JSON_ERROR_NONE) break;
            $decoded = $attempt;
        }

        if (!is_array($decoded)) return [];

        $out = [];

        foreach ($decoded as $keyOrIndex => $value) {

            if (!is_array($value)) continue;

            if (isset($value['item_name']) && trim((string)$value['item_name']) !== '') {

                $name = trim((string)$value['item_name']);
            } elseif (isset($value['name']) && trim((string)$value['name']) !== '') {

                $name = trim((string)$value['name']);
            } elseif (is_string($keyOrIndex) && trim($keyOrIndex) !== '') {

                $name = trim($keyOrIndex);
            } else {
                continue;
            }

            $lc  = strtolower($name);
            $amt = (float)($value['amount'] ?? 0);

            $out[$lc] = [
                'display_name' => $name,
                'amount'       => $amt,
            ];
        }

        return $out;
    }

    // =========================================================================
    public function array(): array
    {
        $colCount = $this->totalCols;
        $blankRow = array_fill(0, $colCount, '');

        $rows   = [];
        $rows[] = $blankRow;
        $rows[] = $blankRow;
        $rows[] = $this->buildGroupRow();
        $rows[] = $this->buildColumnHeader();

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

    // ── Group header row (row 3) ──────────────────────────────────────────
    private function buildGroupRow(): array
    {
        $h = ['', '', '', '', ''];
        foreach ($this->allowanceNames as $n) { $h[] = 'ALLOWANCES'; }
        $h[] = 'OVERTIME';
        $h[] = 'GROSS PAY';
        foreach ($this->statutory as $s) { $h[] = 'STATUTORY DEDUCTIONS'; }
        foreach ($this->deductionNames as $n) { $h[] = 'CUSTOM DEDUCTIONS'; }
        for ($i = 0; $i < 3; $i++) { $h[] = 'OTHER DEDUCTIONS'; }
        $h[] = 'NET PAY';
        foreach ($this->thirdRuleCols as $c) { $h[] = '1/3 RULE CHECK'; }
        for ($i = 0; $i < 5; $i++) { $h[] = 'ATTENDANCE & BANK'; }
        return $h;
    }

    // ── Column label row (row 4) ──────────────────────────────────────────
    private function buildColumnHeader(): array
    {
        $c = $this->currency;
        $h = ['#', 'Employee Name', 'Employee Code', 'Tax PIN (KRA)', "Basic Salary\n({$c})"];
        foreach ($this->allowanceNames as $lc => $name) { $h[] = "{$name}\n({$c})"; }
        $h[] = "Overtime\n({$c})";
        $h[] = "Gross Pay\n({$c})";
        $h[] = "SHIF\n({$c})";
        $h[] = "NSSF\n({$c})";
        $h[] = "Housing Levy\n({$c})";
        $h[] = "Taxable Income\n({$c})";
        $h[] = "Personal Relief\n({$c})";
        $h[] = "Ins. Relief\n({$c})";
        $h[] = "PAYE\n({$c})";
        foreach ($this->deductionNames as $lc => $name) { $h[] = "{$name}\n({$c})"; }
        $h[] = "Absenteeism\n({$c})";
        $h[] = "Loan Repayment\n({$c})";
        $h[] = "Advance Recovery\n({$c})";
        $h[] = "NET PAY\n({$c})";
        $h[] = "1/3 Rule Basis\n({$c})";
        $h[] = "Max Allowed Deductions\n(2/3, {$c})";
        $h[] = "Max Voluntary Available\n({$c})";
        $h[] = "Total Deductions\n({$c})";
        $h[] = "1/3 Rule Status";
        $h[] = 'Days Present';
        $h[] = 'Days Absent';
        $h[] = 'Days in Month';
        $h[] = 'Bank Name';
        $h[] = 'Account Number';
        return $h;
    }

    // ── Data row for one employee ─────────────────────────────────────────
    private function buildDataRow(EmployeePayroll $ep, int $rowNum): array
    {

        $allowanceMap    = self::decodeJsonField($ep->getRawOriginal('allowances'));
        $overtimeFromAll = $allowanceMap['overtime allowance']['amount'] ?? 0.0;
        unset($allowanceMap['overtime allowance']);

        $ps = \Illuminate\Support\Facades\DB::table('payroll_settings')
            ->where('employee_id', $ep->employee_id)
            ->where('year',        $this->payroll->payrun_year)
            ->where('month',       $this->payroll->payrun_month)
            ->first(['allowances', 'deductions']);

        if ($ps) {
            $psAllMap = self::decodeJsonField($ps->allowances);
            if (isset($psAllMap['overtime allowance'])) {
                $overtimeFromAll = $psAllMap['overtime allowance']['amount'];
                unset($psAllMap['overtime allowance']);
            }

            foreach ($psAllMap as $lc => $item) {
                $allowanceMap[$lc] = $item;
            }
        }

        $rawDedMap = self::decodeJsonField($ep->getRawOriginal('deductions'));

        if ($ps) {
            $psDedMap = self::decodeJsonField($ps->deductions);
            foreach ($psDedMap as $lc => $item) {
                $rawDedMap[$lc] = $item;
            }
        }

        $absenteeismAmt = 0.0;
        $deductionMap   = [];
        foreach ($rawDedMap as $lc => $item) {
            if (str_contains($lc, 'absenteeism')) {
                $absenteeismAmt += $item['amount'];
            } elseif (!in_array($lc, $this->skipDeductionLower)) {
                $deductionMap[$lc] = $item['amount'];
            }
        }

        $overtimeDecoded = self::decodeJsonField($ep->getRawOriginal('overtime'));

        $rawOvertimeStr = $ep->getRawOriginal('overtime');
        $overtimeArr    = $rawOvertimeStr;
        $passes         = 4;
        while (is_string($overtimeArr) && $passes-- > 0) {
            $tmp = json_decode($overtimeArr, true);
            if (json_last_error() !== JSON_ERROR_NONE) break;
            $overtimeArr = $tmp;
        }
        $overtimeAmt = (float)(is_array($overtimeArr) ? ($overtimeArr['amount'] ?? $overtimeFromAll) : $overtimeFromAll);

        $basicSalary = (float)(
            $ep->basic_salary
            ?? $ep->employee?->employmentDetails?->basic_salary
            ?? $ep->employee?->employmentDetails?->salary
            ?? 0
        );

        $personalRelief  = (float)($ep->personal_relief  ?? 0);
        $insuranceRelief = (float)($ep->insurance_relief ?? 0);

        if ($personalRelief == 0 || $insuranceRelief == 0) {
            $reliefsDecoded = self::decodeJsonField($ep->getRawOriginal('reliefs'));
            foreach ($reliefsDecoded as $lc => $item) {
                if ($personalRelief == 0  && str_contains($lc, 'personal'))  {
                    $personalRelief  = (float)($item['display_amount'] ?? $item['amount'] ?? 0);
                }
                if ($insuranceRelief == 0 && str_contains($lc, 'insurance')) {
                    $insuranceRelief = (float)($item['display_amount'] ?? $item['amount'] ?? 0);
                }
            }
        }

        $row = [
            $rowNum,
            $ep->employee?->user?->name ?? 'N/A',
            $ep->employee?->employee_code ?? 'N/A',
            $ep->employee?->tax_no ?? 'N/A',
            $basicSalary,
        ];

        foreach ($this->allowanceNames as $lc => $displayName) {
            $row[] = $allowanceMap[$lc]['amount'] ?? 0.0;
        }

        $row[] = $overtimeAmt;
        $row[] = (float)($ep->gross_pay ?? 0);

        $row[] = (float)($ep->shif           ?? 0);
        $row[] = (float)($ep->nssf           ?? 0);
        $row[] = (float)($ep->housing_levy   ?? 0);
        $row[] = (float)($ep->taxable_income ?? 0);
        $row[] = $personalRelief;
        $row[] = $insuranceRelief;
        $row[] = (float)($ep->paye           ?? 0);

        foreach ($this->deductionNames as $lc => $displayName) {
            $row[] = $deductionMap[$lc] ?? 0.0;
        }

        $row[] = $absenteeismAmt;
        $row[] = (float)($ep->loan_repayment   ?? 0);
        $row[] = (float)($ep->advance_recovery ?? 0);

        $row[] = (float)($ep->net_pay ?? 0);

        $ruleResult = ThirdRuleService::evaluateEmployee($ep);
        $row[] = round($ruleResult['basis_amount'], 2);
        $row[] = round($ruleResult['max_total_deductions'], 2);
        $row[] = round($ruleResult['max_voluntary'], 2);
        $row[] = round($ruleResult['total_deductions'], 2);
        $statusLabel = match ($ruleResult['status']) {
            'compliant'        => 'COMPLIANT',
            'statutory_breach' => 'STATUTORY BREACH',
            default            => 'BREACH',
        };
        $row[] = $statusLabel;

        $this->thirdRuleStatusByRow[$rowNum] = $statusLabel;

        $row[] = (int)  ($ep->attendance_present ?? 0);
        $row[] = (int)  ($ep->attendance_absent  ?? 0);
        $row[] = (int)  ($ep->days_in_month      ?? 0);
        $row[] = $ep->bank_name      ?? '';
        $row[] = $ep->account_number ?? '';

        return $row;
    }

    // =========================================================================
    public function styles(Worksheet $sheet): array
    {
        $company   = $this->business->company_name ?? $this->business->name ?? 'Company';
        $monthName = \Carbon\Carbon::createFromFormat('m', $this->payroll->payrun_month)->format('F');
        $period    = "{$monthName} {$this->payroll->payrun_year}";

        $lastCol = Coordinate::stringFromColumnIndex($this->totalCols);
        $NAVY    = $this->C['dark_navy'];
        $WHITE   = $this->C['white'];

        $sheet->freezePane('F5');

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

        $this->applyGroupHeader($sheet);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font'      => ['name' => 'Arial', 'bold' => true, 'size' => 8, 'color' => ['rgb' => $WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'               => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '555555']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(52);
        $this->applyColumnHeaderColors($sheet);

        for ($r = $this->dataStartRow; $r < $this->totalsRow; $r++) {
            $bg = ($r % 2 === 0) ? $this->C['row_odd'] : $this->C['row_even'];
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'font'      => ['name' => 'Arial', 'size' => 8],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $bg]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        $tr = $this->totalsRow;
        $sheet->getStyle("A{$tr}:{$lastCol}{$tr}")->applyFromArray([
            'font'    => ['name' => 'Arial', 'bold' => true, 'size' => 8, 'color' => ['rgb' => $WHITE]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $NAVY]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '888888']]],
        ]);

        $numFmt    = '#,##0.00;(#,##0.00);"-"';
        $intFmt    = '#,##0';
        $monStart  = Coordinate::stringFromColumnIndex(6);
        $monEnd    = Coordinate::stringFromColumnIndex($this->totalCols - 5 - count($this->thirdRuleCols));
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

        $netPayL = Coordinate::stringFromColumnIndex($this->totalCols - 5 - count($this->thirdRuleCols));
        $sheet->getStyle("{$netPayL}{$this->dataStartRow}:{$netPayL}" . ($tr - 1))
            ->getFont()->setBold(true);

        $thirdRuleFirstCol = $this->totalCols - 5 - count($this->thirdRuleCols) + 1;
        $thirdRuleNumEnd   = $thirdRuleFirstCol + 3;
        $tStart = Coordinate::stringFromColumnIndex($thirdRuleFirstCol);
        $tEnd   = Coordinate::stringFromColumnIndex($thirdRuleNumEnd);
        $sheet->getStyle("{$tStart}{$this->dataStartRow}:{$tEnd}{$tr}")
            ->getNumberFormat()->setFormatCode($numFmt);
        $sheet->getStyle("{$tStart}{$this->dataStartRow}:{$tEnd}{$tr}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $statusCol = Coordinate::stringFromColumnIndex($thirdRuleNumEnd + 1);
        foreach ($this->thirdRuleStatusByRow as $rowNum => $status) {
            $sheetRow = $this->dataStartRow + $rowNum - 1;
            $bg = $status === 'COMPLIANT' ? $this->C['pass_bg'] : $this->C['fail_bg'];
            $fg = $status === 'COMPLIANT' ? $this->C['pass_fg'] : $this->C['fail_fg'];
            $sheet->getStyle("{$statusCol}{$sheetRow}")->applyFromArray([
                'font'      => ['name' => 'Arial', 'bold' => true, 'size' => 8, 'color' => ['rgb' => $fg]],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $bg]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        return [];
    }

    // ── Section group header (row 3) ──────────────────────────────────────
    private function applyGroupHeader(Worksheet $sheet): void
    {
        $col = 1;
        $this->mergeGroup($sheet, $col, $col + 4, 'EMPLOYEE INFO', $this->C['grey']); $col += 5;

        $cnt = count($this->allowanceNames);
        if ($cnt > 0) {
            $this->mergeGroup($sheet, $col, $col + $cnt - 1, 'ALLOWANCES', $this->C['green']);
            $col += $cnt;
        }

        $this->mergeGroup($sheet, $col, $col, 'OVERTIME', $this->C['teal']);       $col++;
        $this->mergeGroup($sheet, $col, $col, 'GROSS PAY', $this->C['mid_blue']);  $col++;

        $statCount = count($this->statutory);
        $this->mergeGroup($sheet, $col, $col + $statCount - 1, 'STATUTORY DEDUCTIONS', $this->C['red']);
        $col += $statCount;

        $cnt = count($this->deductionNames);
        if ($cnt > 0) {
            $this->mergeGroup($sheet, $col, $col + $cnt - 1, 'CUSTOM DEDUCTIONS', $this->C['orange']);
            $col += $cnt;
        }

        $this->mergeGroup($sheet, $col, $col + 2, 'OTHER DEDUCTIONS', $this->C['purple']); $col += 3;
        $this->mergeGroup($sheet, $col, $col, 'NET PAY', $this->C['dark_navy']);            $col++;

        $thirdCount = count($this->thirdRuleCols);
        $this->mergeGroup($sheet, $col, $col + $thirdCount - 1, '1/3 RULE CHECK', $this->C['gold']);
        $col += $thirdCount;

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

    private function applyColumnHeaderColors(Worksheet $sheet): void
    {
        $col = 1;
        for ($i = 0; $i < 5; $i++) { $this->colorCol($sheet, $col++, $this->C['grey']); }
        foreach ($this->allowanceNames as $k => $n) { $this->colorCol($sheet, $col++, $this->C['green']); }
        $this->colorCol($sheet, $col++, $this->C['teal']);
        $this->colorCol($sheet, $col++, $this->C['mid_blue']);
        foreach ($this->statutory as $s) { $this->colorCol($sheet, $col++, $this->C['red']); }
        foreach ($this->deductionNames as $k => $n) { $this->colorCol($sheet, $col++, $this->C['orange']); }
        for ($i = 0; $i < 3; $i++) { $this->colorCol($sheet, $col++, $this->C['purple']); }
        $this->colorCol($sheet, $col++, $this->C['dark_navy']);
        foreach ($this->thirdRuleCols as $c) { $this->colorCol($sheet, $col++, $this->C['gold']); }
        while ($col <= $this->totalCols) { $this->colorCol($sheet, $col++, $this->C['teal']); }
    }

    private function colorCol(Worksheet $sheet, int $ci, string $rgb): void
    {
        $sheet->getStyle(Coordinate::stringFromColumnIndex($ci) . '4')
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }
}
