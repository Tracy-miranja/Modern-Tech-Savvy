<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $employeePayroll->employee->user->name ?? 'Employee' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #222; background: #fff; padding: 50px 50px 40px; }
        .payslip { width: 100%; max-width: 600px; margin: 0 auto; background: #fff; padding: 0 16px; }
        .logo-wrap { text-align: center; margin-bottom: 12px; padding-top: 10px; }
        .logo-wrap img { max-height: 90px; max-width: 200px; object-fit: contain; }
        .company-name { text-align: center; font-size: 13px; font-weight: bold; margin: 0 0 4px; letter-spacing: 0.3px; }
        .payslip-title { text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 20px; color: #333; }
        .emp-info { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .emp-info td { font-size: 11px; padding: 2px 0; }
        .emp-info td.label { width: 120px; color: #555; }
        .emp-info td.value { font-weight: bold; text-align: right; }
        .pay-table { width: 100%; border-collapse: collapse; }
        .pay-table thead tr th { font-size: 11px; font-weight: bold; padding: 6px 4px 6px 0; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; text-align: left; }
        .pay-table thead tr th:not(:first-child) { text-align: right; }
        .pay-table tbody tr td { font-size: 11px; padding: 3px 4px 3px 0; vertical-align: middle; }
        .pay-table tbody tr td:not(:first-child) { text-align: right; }
        .row-subtotal td { font-weight: bold; color: #1a56c4; border-top: 1px solid #ddd; padding-top: 5px !important; padding-bottom: 5px !important; }
        .row-gap td { padding-top: 10px !important; }
        .row-netpay td { font-weight: bold; font-size: 12px; border-top: 1.5px solid #000; padding-top: 8px !important; padding-bottom: 6px !important; }
        .row-noncash td { color: #666; font-style: italic; }
        .neg { color: #c00; }
        .fx-note { font-size: 10px; color: #555; margin-top: 6px; margin-bottom: 14px; }
        .contributions { margin-top: 16px; font-size: 11px; }
        .contributions p { margin-bottom: 4px; }
        .bank-info { margin-top: 14px; font-size: 11px; }
        .bank-info table { width: 100%; border-collapse: collapse; }
        .bank-info td { padding: 2px 0; }
        .bank-info td.lbl { width: 120px; color: #555; }
        .bank-info td.val { font-weight: bold; }
        .signatures { margin-top: 32px; font-size: 11px; }
        .signatures p { margin-bottom: 24px; }
        .footer { text-align: center; font-size: 10px; font-weight: bold; color: #c00; margin-top: 10px; }
        .pension-note { margin-top: 14px; font-size: 10px; color: #555; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
<div class="payslip">

@php
    $ep       = $employeePayroll;
    $employee = $ep->employee;
    $payroll  = $ep->payroll;

    // ── Tax currency: the currency payroll is computed in (e.g. KES or UGX) ──
    $currency = strtoupper(trim($payroll->currency ?? 'KES'));

    // ── FX column logic ───────────────────────────────────────────────────────
    //
    // $exchangeRates  = rate passed from controller
    //                   meaning: 1 $targetCurrency = X $currency (tax currency)
    //                   e.g. storedRate=129 means 1 USD = 129 KES
    //
    // $targetCurrency = the employee's ORIGINAL pay currency (e.g. 'USD', 'UGX')
    //                   This is what should appear as the 4th column header.
    //
    // The 4th column converts tax-currency amounts BACK to the employee's
    // original currency by DIVIDING by storedRate:
    //     foreignAmount = taxAmount / storedRate
    //     e.g. 578,150 KES / 129 = 5,781.50 USD  ✓
    //
    // Show the 4th column ONLY when:
    //   1. $targetCurrency is different from the tax/payroll currency
    //   2. The stored rate is meaningfully different from 1.0
    //   3. The rate is a valid positive number
    //
    // Employees paid in the same currency as the business → NO 4th column.
    // Employees paid in a foreign currency (e.g. USD employee in a KES business)
    // → 4th column showing their amounts in their original currency.

    $storedRate  = floatval($exchangeRates ?? 1.0);
    $fxCurrency  = strtoupper(trim($targetCurrency ?? $currency));

    // The 4th column is shown ONLY for employees whose pay currency differs
    // from the tax/payroll currency AND the exchange rate is meaningful (not 1:1)
    $showFx = ($fxCurrency !== $currency)
           && ($storedRate > 0)
           && (abs($storedRate - 1.0) > 0.0001);

    // Header label: e.g. "UGX (R: 0.0077)" or "USD (R: 129)"
    // For rates >= 1 (e.g. 1 USD = 129 KES) → show as whole number
    // For rates < 1 (e.g. 1 UGX = 0.0077 KES) → show 4 decimal places
    $fxRateLabel = $storedRate >= 1
        ? number_format(round($storedRate), 0)
        : number_format($storedRate, 4);

    // Convert tax-currency amount → original employee currency
    // Divide because storedRate = "1 foreignUnit = X taxUnits"
    // So foreignUnits = taxAmount / storedRate
    $fx = fn(float $taxAmt): float => ($storedRate > 0)
        ? round($taxAmt / $storedRate, 2)
        : 0.0;

    // Format foreign value for display; $neg=true shows red brackets
    $fxFmt = function(float $taxAmt, bool $neg = false) use ($storedRate): string {
        if ($storedRate <= 0) return '-';
        $val = round($taxAmt / $storedRate, 2);
        if ($neg) {
            return '<span class="neg">(' . number_format(abs($val), 2) . ')</span>';
        }
        return $val == 0 ? '-' : number_format($val, 2);
    };

    // ── Logo ──────────────────────────────────────────────────────────────────
    $logoUrl    = $business->getImageUrl();
    $logoBase64 = null;
    $filePath   = public_path(parse_url($logoUrl, PHP_URL_PATH));
    if (is_file($filePath)) {
        $ext        = pathinfo($filePath, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
    }

    // ── Period ────────────────────────────────────────────────────────────────
    $month       = (int) $payroll->payrun_month;
    $year        = $payroll->payrun_year;
    $periodLabel = \Carbon\Carbon::create($year, $month)->format('F')
                   . ' (' . str_pad($month, 2, '0', STR_PAD_LEFT) . '), ' . $year;

    // ── Core figures (all in tax currency, e.g. KES) ──────────────────────────
    $basicSalary  = (float)($ep->basic_salary ?? 0);
    $grossPay     = (float)($ep->gross_pay    ?? 0);
    $overtimeData = json_decode($ep->overtime, true) ?? [];
    $overtimeAmt  = (float)($overtimeData['amount'] ?? 0);

    // ── Allowances — split cash allowances into taxable vs non-taxable ────────
    // Non-taxable cash allowances are paid out but shouldn't inflate "Gross pay"
    // or the taxable base — they're added back in just before Net pay instead.
    $allowancesRaw = json_decode($ep->allowances, true) ?? [];
    $taxableAllowanceRows    = [];
    $nonTaxableAllowanceRows = [];
    $employerContribRows     = [];
    foreach ($allowancesRaw as $a) {
        if (!is_array($a)) continue;
        $name = trim($a['name'] ?? $a['item_name'] ?? 'Allowance');
        if (strtolower($name) === 'hourly breakdown') continue;
        $amt          = (float)($a['amount'] ?? 0);
        $isEmpContrib = (bool)($a['is_employer_contribution'] ?? false);
        $isTaxable    = (bool)($a['is_taxable'] ?? false);

        if ($isEmpContrib) {
            $employerContribRows[] = ['name' => $name . ' (non cash)', 'amount' => $amt];
        } elseif ($isTaxable) {
            $taxableAllowanceRows[] = ['name' => $name, 'amount' => $amt];
        } else {
            $nonTaxableAllowanceRows[] = ['name' => $name, 'amount' => $amt];
        }
    }

    $totalTaxableAllowanceAmt    = array_sum(array_column($taxableAllowanceRows, 'amount'));
    $totalNonTaxableAllowanceAmt = array_sum(array_column($nonTaxableAllowanceRows, 'amount'));

    // Display gross pay = basic + overtime + TAXABLE cash allowances only.
    // (Stored $ep->gross_pay may include non-taxable allowances for net-pay math —
    // that's fine, we don't touch it — we just don't show it in this subtotal.)
    $displayGrossPay = $basicSalary + $overtimeAmt + $totalTaxableAllowanceAmt;

    // Switch: true when this payslip's tax currency is Uganda's UGX
    $isUganda = strtoupper(trim($currency)) === 'UGX';

    // Statutory
    $shif            = (float)($ep->shif         ?? 0);
    $nssf            = (float)($ep->nssf         ?? 0);
    $housingLevy     = (float)($ep->housing_levy  ?? 0);
    $helb            = (float)($ep->helb          ?? 0);
    $deductBeforeTax = $shif + $nssf + $housingLevy + $helb;

    // Employer pension
    $employerPensionTaxable = (float)($ep->employer_pension_taxable ?? 0);
    $employerPensionExempt  = (float)($ep->employer_pension_exempt  ?? 0);
    $employerPensionTotal   = (float)($ep->employer_pension         ?? 0);
    $taxableGross           = (float)($ep->taxable_gross            ?? $grossPay);
    $showEmployerPension    = $employerPensionTotal > 0;

    // PAYE
    $taxableIncome    = (float)($ep->taxable_income      ?? 0);
    $payeBeforeRelief = (float)($ep->paye_before_reliefs ?? 0);
    $personalRelief   = (float)($ep->personal_relief     ?? 0);
    $insuranceRelief  = (float)($ep->insurance_relief    ?? 0);
    $paye             = (float)($ep->paye                ?? 0);

    // Custom deductions
    $deductionsRaw   = json_decode($ep->deductions, true) ?? [];
    $loanRepayment   = (float)($ep->loan_repayment   ?? 0);
    $advanceRecovery = (float)($ep->advance_recovery ?? 0);

    $skipKeys = ['shif','nssf','paye','housing levy','housing_levy','helb','absenteeism','absenteeism charge'];
    $customDeductions = [];
    $absenteeismAmt   = 0.0;
    foreach ($deductionsRaw as $d) {
        if (!is_array($d)) continue;
        $nl  = strtolower(trim($d['name'] ?? $d['item_name'] ?? ''));
        $amt = (float)($d['amount'] ?? 0);
        if (str_contains($nl, 'absenteeism')) { $absenteeismAmt += $amt; continue; }
        if (in_array($nl, $skipKeys)) continue;
        if ($amt > 0) $customDeductions[] = ['name' => $d['name'] ?? $d['item_name'], 'amount' => $amt];
    }

    $netPay = (float)($ep->net_pay ?? 0);

    // Recompute deductions explicitly instead of (grossPay - netPay),
    // so a non-taxable allowance never gets miscounted as a negative deduction.
   $explicitDeductions = $nssf + $shif + $housingLevy + $helb + $paye
    + $loanRepayment + $advanceRecovery + $absenteeismAmt
    + array_sum(array_column($customDeductions, 'amount'));

$payBeforeNonTaxable = $displayGrossPay - $explicitDeductions;

    // NSSF year-to-date
    $nssfToDate = \App\Models\EmployeePayroll::where('employee_id', $employee->id)
        ->whereHas('payroll', fn($q) => $q->where('payrun_year', $year))
        ->sum('nssf');
@endphp

    {{-- Logo --}}
    <div class="logo-wrap">
        @if($logoBase64)<img src="{{ $logoBase64 }}" alt="Logo">@endif
    </div>

    <div class="company-name">{{ $entity->company_name ?? $entity->name ?? ($business->company_name ?? 'Company') }}</div>
    <div class="payslip-title">Payslip for the month of {{ $periodLabel }}</div>

    {{-- Employee info --}}
    <table class="emp-info">
        <tr><td class="label">Name:</td><td class="value">{{ $employee->user->name ?? 'N/A' }}</td></tr>
        @if($employee->national_id)
        <tr><td class="label">ID no:</td><td class="value">{{ $employee->national_id }}</td></tr>
        @endif
        @if($employee->tax_no)
        <tr><td class="label">PIN no:</td><td class="value">{{ $employee->tax_no }}</td></tr>
        @endif
        @if($employee->nssf_no)
        <tr><td class="label">NSSF no:</td><td class="value">{{ $employee->nssf_no }}</td></tr>
        @endif
        @if($employee->shif_no ?? $employee->nhif_no ?? null)
        <tr><td class="label">SHIF no:</td><td class="value">{{ $employee->shif_no ?? $employee->nhif_no }}</td></tr>
        @endif
        @if($employee->employmentDetails?->jobCategory?->name ?? null)
        <tr>
            <td class="label">Title:</td>
            <td class="value">
                {{ $employee->employmentDetails->jobCategory->name }}
                @if($employee->location?->name) at {{ $employee->location->name }}@endif
            </td>
        </tr>
        @endif
        @if($entityType === 'location')
        <tr><td class="label">Location:</td><td class="value">{{ $entity->name ?? 'N/A' }}</td></tr>
        @endif
        {{-- Show pay currency only for converted employees --}}
        @if($showFx)
        <tr><td class="label">Pay currency:</td><td class="value">{{ $fxCurrency }}</td></tr>
        @endif
    </table>

    {{-- Pay table --}}
    {{--
        Column layout:
          3 columns  → all employees paid in the business/payroll base currency
          4 columns  → ONLY employees whose pay currency differs from the base currency
                       4th column header = their original currency + conversion rate
                       e.g. "UGX (R: 0.0077)" or "USD (R: 129)"
    --}}
    <table class="pay-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Taxation</th>
                <th>Pay ({{ $currency }})</th>
                @if($showFx)
                {{-- 4th column: employee's original pay currency with the rate used --}}
                <th>{{ $fxCurrency }} (R: {{ $fxRateLabel }})</th>
                @endif
            </tr>
        </thead>
        <tbody>

            {{-- Basic salary --}}
            <tr>
                <td>Basic salary</td>
                <td>{{ number_format($basicSalary, 2) }}</td>
                <td>{{ number_format($basicSalary, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($basicSalary), 2) }}</td>@endif
            </tr>

            {{-- Overtime --}}
            @if($overtimeAmt > 0)
            <tr>
                <td>Overtime</td>
                <td></td>
                <td>{{ number_format($overtimeAmt, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($overtimeAmt), 2) }}</td>@endif
            </tr>
            @endif

            {{-- Taxable cash allowances only --}}
            @foreach($taxableAllowanceRows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td></td>
                <td>{{ $row['amount'] > 0 ? number_format($row['amount'], 2) : '-' }}</td>
                @if($showFx)<td>{{ $row['amount'] > 0 ? number_format($fx($row['amount']), 2) : '-' }}</td>@endif
            </tr>
            @endforeach

            {{-- Non-cash employer-contribution allowances (shown for info, not paid in cash) --}}
            @foreach($employerContribRows as $row)
            <tr class="row-noncash">
                <td>{{ $row['name'] }}</td>
                <td></td>
                <td>-</td>
                @if($showFx)<td>-</td>@endif
            </tr>
            @endforeach

            {{-- Employer pension non-cash --}}
            @if($showEmployerPension)
            <tr class="row-noncash">
                <td>Employer pension (non cash)</td>
                <td>{{ $employerPensionTaxable > 0 ? number_format($employerPensionTaxable, 2) : 'Nil' }}</td>
                <td>-</td>
                @if($showFx)<td>-</td>@endif
            </tr>
            @if($employerPensionExempt > 0)
            <tr class="row-noncash">
                <td style="padding-left:10px;color:#999;">↳ Exempt (≤ 30,000/mo)</td>
                <td style="color:#999;">{{ number_format($employerPensionExempt, 2) }}</td>
                <td></td>
                @if($showFx)<td></td>@endif
            </tr>
            @endif
            @endif

            {{-- Gross pay subtotal (basic + overtime + taxable allowances only) --}}
            <tr class="row-subtotal">
                <td>Gross pay</td>
                <td>{{ number_format($displayGrossPay, 2) }}</td>
                <td>{{ number_format($displayGrossPay, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($displayGrossPay), 2) }}</td>@endif
            </tr>
            @if($employerPensionTaxable > 0)
            <tr class="row-noncash">
                <td>Taxable gross (incl. employer pension excess)</td>
                <td>{{ number_format($taxableGross, 2) }}</td>
                <td></td>
                @if($showFx)<td></td>@endif
            </tr>
            @endif

            {{-- NSSF --}}
            @if($nssf > 0)
            <tr class="row-gap">
                <td>NSSF</td>
                <td>{{ number_format($nssf, 2) }}</td>
                <td>{{ number_format($nssf, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($nssf), 2) }}</td>@endif
            </tr>
            @endif

            {{-- SHIF --}}
            @if($shif > 0)
            <tr @if($nssf == 0) class="row-gap" @endif>
                <td>SHIF</td>
                <td>{{ number_format($shif, 2) }}</td>
                <td>{{ number_format($shif, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($shif), 2) }}</td>@endif
            </tr>
            @endif

            {{-- Pension custom deduction (pre-tax) --}}
            @foreach($customDeductions as $cd)
            @if(str_contains(strtolower($cd['name']), 'pension'))
            <tr>
                <td>{{ $cd['name'] }}</td>
                <td>{{ number_format($cd['amount'], 2) }}</td>
                <td>{{ number_format($cd['amount'], 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($cd['amount']), 2) }}</td>@endif
            </tr>
            @endif
            @endforeach

            {{-- Housing Levy --}}
            @if($housingLevy > 0)
            <tr>
                <td>Housing Levy</td>
                <td>{{ number_format($housingLevy, 2) }}</td>
                <td>{{ number_format($housingLevy, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($housingLevy), 2) }}</td>@endif
            </tr>
            @endif

            {{-- Deductions before tax subtotal — Kenya only --}}
            @if($deductBeforeTax > 0 && !$isUganda)
            <tr class="row-subtotal">
                <td>Deductions before tax</td>
                <td></td>
                <td>{{ number_format($deductBeforeTax, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($deductBeforeTax), 2) }}</td>@endif
            </tr>
            @endif

            {{-- Taxable income & PAYE block --}}
            @if($taxableIncome > 0)
                {{-- Deductible relief — Kenya only --}}
                @if(!$isUganda)
<tr class="row-gap">
    <td>Deductible relief</td>
    <td>{{ number_format($deductBeforeTax, 2) }}</td>
    <td></td>
    @if($showFx)<td>{{ number_format($fx($deductBeforeTax), 2) }}</td>@endif
</tr>
@endif
                <tr>
    <td>Taxable income</td>
    <td>{{ number_format($taxableIncome, 2) }}</td>
    <td></td>
    @if($showFx)<td>{{ number_format($fx($taxableIncome), 2) }}</td>@endif
</tr>
            @endif
            @if($payeBeforeRelief > 0)
<tr>
    <td>PAYE</td>
    <td>{{ number_format($payeBeforeRelief, 2) }}</td>
    <td></td>
    @if($showFx)<td>{{ number_format($fx($payeBeforeRelief), 2) }}</td>@endif
</tr>
@endif
         @if($insuranceRelief > 0)
<tr>
    <td>Insurance relief</td>
    <td>{{ number_format($insuranceRelief, 2) }}</td>
    <td></td>
    @if($showFx)<td>{{ number_format($fx($insuranceRelief), 2) }}</td>@endif
</tr>
@endif
@if($personalRelief > 0)
<tr>
    <td>Personal relief</td>
    <td>{{ number_format($personalRelief, 2) }}</td>
    <td></td>
    @if($showFx)<td>{{ number_format($fx($personalRelief), 2) }}</td>@endif
</tr>
@endif
            @if($paye > 0)
            <tr class="row-subtotal">
                <td>PAYE tax</td>
                <td>{{ number_format($paye, 2) }}</td>
                <td>{{ number_format($paye, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($paye), 2) }}</td>@endif
            </tr>
            @endif

            {{-- After-tax deductions --}}
            @if($helb > 0)
            <tr class="row-gap">
                <td>HELB</td>
                <td></td>
                <td>{{ number_format($helb, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($helb), 2) }}</td>@endif
            </tr>
            @endif
            @if($loanRepayment > 0)
            <tr @if($helb == 0) class="row-gap" @endif>
                <td>Loan Repayment</td>
                <td></td>
                <td>{{ number_format($loanRepayment, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($loanRepayment), 2) }}</td>@endif
            </tr>
            @endif
            @if($advanceRecovery > 0)
            <tr>
                <td>Advance Recovery</td>
                <td></td>
                <td>{{ number_format($advanceRecovery, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($advanceRecovery), 2) }}</td>@endif
            </tr>
            @endif
            @if($absenteeismAmt > 0)
            <tr>
                <td>Absenteeism Charge</td>
                <td></td>
                <td>{{ number_format($absenteeismAmt, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($absenteeismAmt), 2) }}</td>@endif
            </tr>
            @endif
            @foreach($customDeductions as $cd)
            @if(!str_contains(strtolower($cd['name']), 'pension'))
            <tr>
                <td>{{ $cd['name'] }}</td>
                <td></td>
                <td>{{ number_format($cd['amount'], 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($cd['amount']), 2) }}</td>@endif
            </tr>
            @endif
            @endforeach

            {{-- Total deductions (explicit sum, not gross - net) --}}
            <tr class="row-subtotal">
                <td>Total deductions</td>
                <td></td>
                <td><span class="neg">({{ number_format($explicitDeductions, 2) }})</span></td>
                @if($showFx)<td>{!! $fxFmt($explicitDeductions, true) !!}</td>@endif
            </tr>

            {{-- Pay before non-taxable allowances/benefits are added back --}}
<tr class="row-subtotal">
    <td>Pay</td>
    <td></td>
    <td>{{ number_format($payBeforeNonTaxable, 2) }}</td>
    @if($showFx)<td>{{ number_format($fx($payBeforeNonTaxable), 2) }}</td>@endif
</tr>

            {{-- Non-taxable allowances/benefits — added back in after deductions, before net pay --}}
            @foreach($nonTaxableAllowanceRows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td></td>
                <td>{{ number_format($row['amount'], 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($row['amount']), 2) }}</td>@endif
            </tr>
            @endforeach

            {{-- Net pay --}}
            <tr class="row-netpay">
                <td>Net pay</td>
                <td></td>
                <td>{{ number_format($netPay, 2) }}</td>
                @if($showFx)<td>{{ number_format($fx($netPay), 2) }}</td>@endif
            </tr>

        </tbody>
    </table>

    {{-- Exchange rate note (only shown when 4th column is present) --}}
    @if($showFx)
    <p class="fx-note">
        Exchange rate: 1 {{ $fxCurrency }} = {{ $fxRateLabel }} {{ $currency }}
        &nbsp;|&nbsp; Amounts in {{ $fxCurrency }} column = {{ $currency }} amount ÷ {{ $fxRateLabel }}
    </p>
    @endif

    {{-- NSSF contributions to date --}}
    @if($nssfToDate > 0)
    <div class="contributions">
        <p><strong>Contributions to date</strong></p>
        <p>NSSF: {{ number_format($nssfToDate, 2) }}</p>
    </div>
    @endif

    {{-- Bank details --}}
    @php
        $paymentDetail = \App\Models\EmployeePaymentDetail::where('employee_id', $employee->id)->first();
    @endphp
    @if($paymentDetail && ($paymentDetail->bank_name || $paymentDetail->account_number))
    <div class="bank-info">
        <p style="font-weight:bold;margin-bottom:4px;">Salary deposited to:</p>
        <table>
            @if($paymentDetail->bank_name)
            <tr><td class="lbl">Bank:</td><td class="val">{{ $paymentDetail->bank_name }}</td></tr>
            @endif
            @if($paymentDetail->bank_branch)
            <tr><td class="lbl">Branch:</td><td class="val">{{ $paymentDetail->bank_branch }}</td></tr>
            @endif
            <tr><td class="lbl">Account name:</td><td class="val">{{ $employee->user->name ?? 'N/A' }}</td></tr>
            @if($paymentDetail->account_number)
            <tr><td class="lbl">Account No:</td><td class="val">{{ $paymentDetail->account_number }}</td></tr>
            @endif
        </table>
    </div>
    @endif

    {{-- Employer pension note --}}
    @if($showEmployerPension)
    <div class="pension-note">
        <strong>Employer Pension Note (KRA — Income Tax Act Cap 470):</strong><br>
        Employer contributes {{ $currency }} {{ number_format($employerPensionTotal, 2) }} to pension fund.
        @if($employerPensionExempt > 0) {{ number_format($employerPensionExempt, 2) }} exempt (≤ KES 30,000/month).@endif
        @if($employerPensionTaxable > 0) {{ number_format($employerPensionTaxable, 2) }} is a taxable benefit in kind — increases PAYE base, does <strong>not</strong> reduce take-home pay.@endif
    </div>
    @endif

    <div class="signatures">
        <p>Employer's signature _________________</p>
        <p>Employee's signature _________________</p>
    </div>

    <div class="footer">Thank you for your service</div>

</div>
</body>
</html>
