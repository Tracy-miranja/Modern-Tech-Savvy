<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Master Payroll Roll</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 8mm 8mm 10mm 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #222;
            background: #fff;
        }

        /* ── Page Header ── */
        .page-header {
            width: 100%;
            margin-bottom: 6px;
        }
        .company-name {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #1A1A2E;
            padding: 5px 8px 3px;
            background: #E8EAF6;
        }
        .period-info {
            text-align: right;
            font-size: 9px;
            color: #ddd;
            background: #16213E;
            padding: 3px 8px;
        }

        /* ── Table ── */
        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8px;
            page-break-inside: auto;
        }

        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        /* Group header row */
        .group-row th {
            font-size: 8.5px;
            font-weight: bold;
            color: #fff;
            text-align: center;
            padding: 5px 3px;
            border: 1px solid #fff;
        }
        .group-employee  { background: #343A40; }
        .group-allowance { background: #155724; }
        .group-overtime  { background: #005B5B; }
        .group-gross     { background: #16213E; }
        .group-statutory { background: #721C24; }
        .group-custom    { background: #7D4A00; }
        .group-other     { background: #4A1A6B; }
        .group-netpay    { background: #1A1A2E; }
        .group-attend    { background: #005B5B; }

        /* Column header row */
        .col-header th {
            font-size: 7.5px;
            font-weight: bold;
            color: #fff;
            text-align: center;
            padding: 5px 3px;
            border: 1px solid #555;
            word-wrap: break-word;
            vertical-align: middle;
            line-height: 1.3;
            white-space: normal;
        }
        .ch-employee  { background: #343A40; }
        .ch-allowance { background: #155724; }
        .ch-overtime  { background: #005B5B; }
        .ch-gross     { background: #16213E; }
        .ch-statutory { background: #721C24; }
        .ch-custom    { background: #7D4A00; }
        .ch-other     { background: #4A1A6B; }
        .ch-netpay    { background: #1A1A2E; }
        .ch-attend    { background: #005B5B; }

        /* Data rows */
        .data-row td {
            font-size: 8px;
            padding: 4px 3px;
            border: 1px solid #ddd;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
            overflow: visible;
        }
        .data-row.odd  td { background: #F8FAFF; }
        .data-row.even td { background: #FFFFFF; }
        .data-row td.num {
            text-align: right;
            white-space: nowrap;
            overflow: visible;
        }
        .data-row td.ctr { text-align: center; }

        /* Totals row */
        .totals-row td {
            font-size: 8.5px;
            font-weight: bold;
            color: #fff;
            background: #1A1A2E;
            padding: 5px 3px;
            border: 1px solid #888;
            text-align: right;
            white-space: normal;
            word-break: break-word;
            overflow: visible;
        }
        .totals-row td:first-child { text-align: left; }

        /* NET PAY standout */
        .netpay-cell { font-weight: bold; font-size: 8.5px; }

        /* ── Column widths ── */
        .w-num    { width: 1.5%; }
        .w-name   { width: 8.0%; }
        .w-code   { width: 4.0%; }
        .w-pin    { width: 5.5%; }
        .w-basic  { width: 5.0%; }
        .w-allow  { width: 4.2%; }
        .w-ot     { width: 4.0%; }
        .w-gross  { width: 5.0%; }
        .w-stat   { width: 4.0%; }
        .w-ded    { width: 4.2%; }
        .w-other  { width: 4.0%; }
        .w-net    { width: 5.2%; }
        .w-days   { width: 2.5%; }
        .w-bank   { width: 5.0%; }
        .w-acct   { width: 5.5%; }
    </style>
</head>
<body>

<div class="page-header">
    <div class="company-name">
        {{ $business->company_name ?? $business->name ?? 'Company' }}
    </div>
    <div class="period-info">
        Master Payroll Roll &nbsp;|&nbsp;
        Period: {{ \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F') }} {{ $payroll->payrun_year }}
        &nbsp;|&nbsp; Currency: {{ $currency }}
    </div>
</div>

@php

    $statutory = ['SHIF', 'NSSF', 'Housing Levy', 'Taxable Income', 'PAYE', 'Personal Relief', 'Ins. Relief'];

    $totals = [];

    $val = fn($ep, $field) => (float)($ep->{$field} ?? 0);

    $buildEpMaps = function($ep) use ($allowanceSlugs, $deductionSlugs) {
        $statutoryLower = ['shif','nssf','paye','housing levy','helb','helb loan repayment'];
        $aMap = [];
        $dMap = [];
        $absenteeism = 0.0;
        $overtime = 0.0;

        foreach ((json_decode($ep->allowances, true) ?? []) as $item) {
            if (!is_array($item)) continue;
            $iname = trim($item['item_name'] ?? '');
            if ($iname === '') continue;
            $amt = (float)($item['amount'] ?? 0);
            if (strtolower($iname) === 'overtime allowance') { $overtime += $amt; continue; }
            $aMap[strtolower($iname)] = $amt;
        }

        foreach ((json_decode($ep->deductions, true) ?? []) as $item) {
            if (!is_array($item)) continue;
            $iname = trim($item['item_name'] ?? '');
            if ($iname === '') continue;
            $nl = strtolower($iname);
            $amt = (float)($item['amount'] ?? 0);
            if (str_contains($nl, 'absenteeism')) { $absenteeism += $amt; continue; }
            if (!in_array($nl, $statutoryLower)) $dMap[$nl] = $amt;
        }

        $ps = \Illuminate\Support\Facades\DB::table('payroll_settings')
            ->where('employee_id', $ep->employee_id)
            ->where('year',  $ep->payroll?->payrun_year ?? date('Y'))
            ->where('month', $ep->payroll?->payrun_month ?? date('m'))
            ->first(['allowances','deductions']);
        if ($ps) {
            foreach ((json_decode($ps->allowances ?? '[]', true) ?? []) as $item) {
                if (!is_array($item)) continue;
                $iname = trim($item['item_name'] ?? '');
                if ($iname === '') continue;
                $amt = (float)($item['amount'] ?? 0);
                if (strtolower($iname) === 'overtime allowance') { if (!$overtime) $overtime = $amt; continue; }
                $k = strtolower($iname);
                if (!isset($aMap[$k])) $aMap[$k] = $amt;
            }
            foreach ((json_decode($ps->deductions ?? '[]', true) ?? []) as $item) {
                if (!is_array($item)) continue;
                $iname = trim($item['item_name'] ?? '');
                if ($iname === '') continue;
                $nl = strtolower($iname);
                $amt = (float)($item['amount'] ?? 0);
                if (str_contains($nl, 'absenteeism')) { if (!$absenteeism) $absenteeism = $amt; continue; }
                if (!in_array($nl, $statutoryLower) && !isset($dMap[$nl])) $dMap[$nl] = $amt;
            }
        }

        $otJson = json_decode($ep->overtime, true) ?? [];
        if (isset($otJson['amount'])) $overtime = (float)$otJson['amount'];

        return compact('aMap', 'dMap', 'absenteeism', 'overtime');
    };

    $parseReliefs = function($ep) {
        $personal = 0.0; $insurance = 0.0;
        foreach ((json_decode($ep->reliefs, true) ?? []) as $r) {
            if (!is_array($r)) continue;
            $rn = strtolower(trim($r['item_name'] ?? ''));
            $ra = (float)($r['amount'] ?? 0);
            if (str_contains($rn, 'personal'))  $personal  += $ra;
            if (str_contains($rn, 'insurance')) $insurance += $ra;
        }
        return compact('personal', 'insurance');
    };

    $fmt = fn($v) => $v == 0 ? '-' : number_format((float)$v, 2);

    $statutoryCount = 7;
@endphp

<table>
    <thead>
    <tr class="group-row">
        <th colspan="5" class="group-employee">EMPLOYEE INFO</th>

        @if(count($allowanceSlugs))
        <th colspan="{{ count($allowanceSlugs) }}" class="group-allowance">ALLOWANCES</th>
        @endif

        <th colspan="1" class="group-overtime">OVERTIME</th>
        <th colspan="1" class="group-gross">GROSS PAY</th>

        <th colspan="{{ $statutoryCount }}" class="group-statutory">STATUTORY DEDUCTIONS</th>

        @if(count($deductionSlugs))
        <th colspan="{{ count($deductionSlugs) }}" class="group-custom">CUSTOM DEDUCTIONS</th>
        @endif

        <th colspan="3" class="group-other">OTHER DEDUCTIONS</th>

        <th colspan="1" class="group-netpay">NET PAY</th>
        <th colspan="5" class="group-attend">ATTENDANCE &amp; BANK</th>
    </tr>

    <tr class="col-header">
        <th class="w-num  ch-employee">#</th>
        <th class="w-name ch-employee">Employee Name</th>
        <th class="w-code ch-employee">Emp Code</th>
        <th class="w-pin  ch-employee">KRA PIN</th>
        <th class="w-basic ch-employee">Basic Salary ({{ $currency }})</th>

        @foreach($allowanceSlugs as $slug => $name)
        <th class="w-allow ch-allowance">{{ $name }} ({{ $currency }})</th>
        @endforeach

        <th class="w-ot   ch-overtime">Overtime ({{ $currency }})</th>
        <th class="w-gross ch-gross">Gross Pay ({{ $currency }})</th>

        <th class="w-stat ch-statutory">SHIF ({{ $currency }})</th>
        <th class="w-stat ch-statutory">NSSF ({{ $currency }})</th>
        <th class="w-stat ch-statutory">Housing Levy ({{ $currency }})</th>
        <th class="w-stat ch-statutory">Taxable Income ({{ $currency }})</th>
         <th class="w-stat ch-statutory">Personal Relief ({{ $currency }})</th>
        <th class="w-stat ch-statutory">Ins. Relief ({{ $currency }})</th>
        <th class="w-stat ch-statutory">PAYE ({{ $currency }})</th>

        @foreach($deductionSlugs as $slug => $name)
        <th class="w-ded ch-custom">{{ $name }} ({{ $currency }})</th>
        @endforeach

        <th class="w-other ch-other">Absenteeism ({{ $currency }})</th>
        <th class="w-other ch-other">Loan Repay ({{ $currency }})</th>
        <th class="w-other ch-other">Adv. Recovery ({{ $currency }})</th>

        <th class="w-net  ch-netpay">NET PAY ({{ $currency }})</th>

        <th class="w-days ch-attend">Days Present</th>
        <th class="w-days ch-attend">Days Absent</th>
        <th class="w-days ch-attend">Days/Month</th>
        <th class="w-bank ch-attend">Bank Name</th>
        <th class="w-acct ch-attend">Account No.</th>
    </tr>
    </thead>

    <tbody>
    @foreach($employeePayrolls as $i => $ep)
    @php
        $maps    = $buildEpMaps($ep);
        $aMap    = $maps['aMap'];
        $dMap    = $maps['dMap'];
        $abAmt   = $maps['absenteeism'];
        $otAmt   = $maps['overtime'];
        $reliefs = $parseReliefs($ep);

        $basicSalary = (float)($ep->basic_salary ?? $ep->basic_pay
            ?? $ep->employee?->employmentDetails?->basic_salary
            ?? $ep->employee?->employmentDetails?->salary ?? 0);

        $grossPay        = $val($ep, 'gross_pay');
        $shif            = $val($ep, 'shif');
        $nssf            = $val($ep, 'nssf');
        $housingLevy     = $val($ep, 'housing_levy');
        $helb            = $val($ep, 'helb');
        $paye            = $val($ep, 'paye');
        $loanRepayment   = $val($ep, 'loan_repayment');
        $advanceRecovery = $val($ep, 'advance_recovery');
        $taxableIncome   = $val($ep, 'taxable_income');
        $personalRelief  = $val($ep, 'personal_relief') ?: $reliefs['personal'];
        $insRelief       = $val($ep, 'insurance_relief') ?: $reliefs['insurance'];
        $netPay          = $val($ep, 'net_pay');
        $daysPresent     = (int)($ep->attendance_present ?? 0);
        $daysAbsent      = (int)($ep->attendance_absent  ?? 0);
        $daysInMonth     = (int)($ep->days_in_month      ?? 0);

        $totals['basic']    = ($totals['basic']    ?? 0) + $basicSalary;
        $totals['gross']    = ($totals['gross']    ?? 0) + $grossPay;
        $totals['shif']     = ($totals['shif']     ?? 0) + $shif;
        $totals['nssf']     = ($totals['nssf']     ?? 0) + $nssf;
        $totals['housing']  = ($totals['housing']  ?? 0) + $housingLevy;
        $totals['helb']     = ($totals['helb']     ?? 0) + $helb;
        $totals['paye']     = ($totals['paye']     ?? 0) + $paye;
        $totals['personal'] = ($totals['personal'] ?? 0) + $personalRelief;
        $totals['ins']      = ($totals['ins']      ?? 0) + $insRelief;
        $totals['ab']       = ($totals['ab']       ?? 0) + $abAmt;
        $totals['loan']     = ($totals['loan']     ?? 0) + $loanRepayment;
        $totals['advance']  = ($totals['advance']  ?? 0) + $advanceRecovery;
        $totals['taxable']  = ($totals['taxable']  ?? 0) + $taxableIncome;
        $totals['net']      = ($totals['net']      ?? 0) + $netPay;
        $totals['ot']       = ($totals['ot']       ?? 0) + $otAmt;

        foreach ($allowanceSlugs as $slug => $name) {
            $k = strtolower($name);
            $totals['a_'.$k] = ($totals['a_'.$k] ?? 0) + ($aMap[$k] ?? 0);
        }
        foreach ($deductionSlugs as $slug => $name) {
            $k = strtolower($name);
            $totals['d_'.$k] = ($totals['d_'.$k] ?? 0) + ($dMap[$k] ?? 0);
        }

        $rowClass = ($i % 2 === 0) ? 'even' : 'odd';
@endphp
    <tr class="data-row {{ $rowClass }}">
        <td class="ctr">{{ $i + 1 }}</td>
        <td>{{ $ep->employee?->user?->name ?? 'N/A' }}</td>
        <td class="ctr">{{ $ep->employee?->employee_code ?? 'N/A' }}</td>
        <td class="ctr">{{ $ep->employee?->tax_no ?? 'N/A' }}</td>
        <td class="num">{{ $fmt($basicSalary) }}</td>

        @foreach($allowanceSlugs as $slug => $name)
        <td class="num">{{ $fmt($aMap[strtolower($name)] ?? 0) }}</td>
        @endforeach

        <td class="num">{{ $fmt($otAmt) }}</td>
        <td class="num">{{ $fmt($grossPay) }}</td>

        <td class="num">{{ $fmt($shif) }}</td>
        <td class="num">{{ $fmt($nssf) }}</td>
        <td class="num">{{ $fmt($housingLevy) }}</td>
        <td class="num">{{ $fmt($taxableIncome) }}</td>
        <td class="num">{{ $fmt($personalRelief) }}</td>
        <td class="num">{{ $fmt($insRelief) }}</td>
        <td class="num">{{ $fmt($paye) }}</td>

        @foreach($deductionSlugs as $slug => $name)
        <td class="num">{{ $fmt($dMap[strtolower($name)] ?? 0) }}</td>
        @endforeach

        <td class="num">{{ $fmt($abAmt) }}</td>
        <td class="num">{{ $fmt($loanRepayment) }}</td>
        <td class="num">{{ $fmt($advanceRecovery) }}</td>

        <td class="num netpay-cell">{{ $fmt($netPay) }}</td>

        <td class="ctr">{{ $daysPresent }}</td>
        <td class="ctr">{{ $daysAbsent }}</td>
        <td class="ctr">{{ $daysInMonth }}</td>
        <td>{{ $ep->bank_name ?? '' }}</td>
        <td>{{ $ep->account_number ?? '' }}</td>
    </tr>
    @endforeach
    </tbody>

    <tfoot>
    <tr class="totals-row">
        <td colspan="4" style="text-align:left;">TOTALS</td>
        <td>{{ $fmt($totals['basic'] ?? 0) }}</td>

        @foreach($allowanceSlugs as $slug => $name)
        <td>{{ $fmt($totals['a_'.strtolower($name)] ?? 0) }}</td>
        @endforeach

        <td>{{ $fmt($totals['ot'] ?? 0) }}</td>
        <td>{{ $fmt($totals['gross'] ?? 0) }}</td>

        <td>{{ $fmt($totals['shif'] ?? 0) }}</td>
        <td>{{ $fmt($totals['nssf'] ?? 0) }}</td>
        <td>{{ $fmt($totals['housing'] ?? 0) }}</td>
        <td>{{ $fmt($totals['taxable'] ?? 0) }}</td>
        <td>{{ $fmt($totals['personal'] ?? 0) }}</td>
        <td>{{ $fmt($totals['ins'] ?? 0) }}</td>
        <td>{{ $fmt($totals['paye'] ?? 0) }}</td>

        @foreach($deductionSlugs as $slug => $name)
        <td>{{ $fmt($totals['d_'.strtolower($name)] ?? 0) }}</td>
        @endforeach

        <td>{{ $fmt($totals['ab'] ?? 0) }}</td>
        <td>{{ $fmt($totals['loan'] ?? 0) }}</td>
        <td>{{ $fmt($totals['advance'] ?? 0) }}</td>

        <td class="netpay-cell">{{ $fmt($totals['net'] ?? 0) }}</td>

        <td colspan="5"></td>
    </tr>
    </tfoot>
</table>

</body>
</html>
