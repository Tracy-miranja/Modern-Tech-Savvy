<!DOCTYPE html>
<html>
<head>
    <title>TAX DEDUCTION CARD YEAR- {{ $year }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 10px; }
        th, td { border: 1px solid black; padding: 3px; text-align: right; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-left { text-align: left; }
        h3 { text-align: center; margin: 0 0 5px; }
        .note { font-size: 9px; margin-top: 30px; line-height: 1.5; }
        .totals { margin-top: 20px; font-size: 10px; }
        .employee-details { margin-bottom: 10px; }
        .employee-page { page-break-after: always; }
        .employee-page:last-child { page-break-after: auto; }
    </style>
</head>
<body>

@php
    // Normalize to a plain list of employee records regardless of caller.
    $employeeRecords = array_values($data ?? []);
@endphp

@forelse($employeeRecords as $item)
<div class="employee-page">

    <h3><strong>KENYA REVENUE AUTHORITY</strong></h3>
    <h3>DOMESTIC TAXES DEPARTMENT</h3>
    <h3><strong>P9A FORM - {{ $year }}</strong></h3>

    @php
        // Canonical keys, with fallbacks to whatever the caller actually sent
        // (older controller variants used different, inconsistent key names).
        $employerName = $item['employer_name']
            ?? ($business->company_name ?? $business->name ?? 'N/A');
        $employerPin  = $item['employer_pin']
            ?? ($business->tax_pin_no ?? 'N/A');

        $employeeName = $item['employee_name_display']
            ?? $item['main_name']
            ?? ($item['employee_name'] ?? 'N/A');

        $employeePin  = $item['employee_pin_display']
            ?? $item['pin']
            ?? ($item['tax_no'] ?? 'N/A');

        $employeeNssf = $item['nssf'] ?? 'N/A';
        $employeeShif = $item['shif'] ?? 'N/A';
    @endphp

    <div class="employee-details">
        <p><strong>Employer's Name:</strong> {{ $employerName }} &nbsp;&nbsp;&nbsp;&nbsp; <strong>Employer's PIN:</strong> {{ $employerPin }}</p>
        <p><strong>Employee's Main Name:</strong> {{ $employeeName }} &nbsp;&nbsp;&nbsp;&nbsp; <strong>Employee's PIN:</strong> {{ $employeePin }}</p>
        <p><strong>Employee's NSSF:</strong> {{ $employeeNssf }} &nbsp;&nbsp;&nbsp;&nbsp; <strong>Employee's SHIF:</strong> {{ $employeeShif }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Month</th>
                <th rowspan="2">Basic Salary</th>
                <th rowspan="2">Benefits - Non Cash</th>
                <th rowspan="2">Value of Quarters</th>
                <th rowspan="2">Total Gross Pay</th>
                <th colspan="3">Defined Contribution Retirement Scheme</th>
                <th rowspan="2">Affordable Housing Levy (AHL)</th>
                <th rowspan="2">Social Health Insurance Fund (SHIF)</th>
                <th rowspan="2">Post Retirement Medical Fund (PRMF)</th>
                <th rowspan="2">Owner Occupied Interest</th>
                <th rowspan="2">Total Deductions<br><small>(Lower of E+F+G+H+I)</small></th>
                <th rowspan="2">Chargeable Pay<br><small>(D - J)</small></th>
                <th rowspan="2">Tax Charged</th>
                <th rowspan="2">Personal Relief</th>
                <th rowspan="2">Insurance Relief</th>
                <th rowspan="2">PAYE Tax<br><small>(L - M - N)</small></th>
            </tr>
            <tr>
                <th>E1<br>30% of A</th>
                <th>E2<br>Actual</th>
                <th>E3<br>Fixed</th>
            </tr>
            <tr>
                <th></th><th>A</th><th>B</th><th>C</th><th>D</th>
                <th>E1</th><th>E2</th><th>E3</th><th>F</th><th>G</th>
                <th>H</th><th>I</th><th>J</th><th>K</th><th>L</th>
                <th>M</th><th>N</th><th>O</th>
            </tr>
        </thead>

        <tbody>
            @php
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];

                $totalCols = array_fill_keys([
                    'basic_salary', 'benefits_non_cash', 'value_of_quarters', 'total_gross_pay',
                    'retirement_e1', 'retirement_e2', 'retirement_e3', 'housing_levy', 'shif', 'prmf',
                    'owner_occupied_interest', 'total_deductions', 'chargeable_pay', 'tax_charged',
                    'personal_relief', 'insurance_relief', 'paye'
                ], 0);

                $monthlyData = $item['monthly_data'] ?? [];
                if (!is_array($monthlyData)) $monthlyData = [];
            @endphp

            @foreach($months as $monthNumber => $monthName)
                @php
                    $row = $monthlyData[$monthNumber] ?? [
                        'basic_salary' => 0, 'benefits_non_cash' => 0, 'value_of_quarters' => 0,
                        'total_gross_pay' => 0, 'retirement_e1' => 0, 'retirement_e2' => 0,
                        'retirement_e3' => 30000, 'housing_levy' => 0, 'shif' => 0, 'prmf' => 0,
                        'owner_occupied_interest' => 0, 'personal_relief' => 2400,
                        'insurance_relief' => 0, 'paye' => 0,
                    ];

                    // Accept either 'ahl' or 'housing_levy' as the AHL source key
                    $housingLevy = $row['housing_levy'] ?? ($row['ahl'] ?? 0);
                    if ($housingLevy == 0) {
                        $housingLevy = ($row['total_gross_pay'] ?? 0) * 0.015;
                    }

                    $shif = $row['shif'] ?? 0;
                    if ($shif == 0) {
                        $shif = ($row['total_gross_pay'] ?? 0) * 0.0275;
                    }

                    $retE1 = $row['retirement_e1'] ?? (($row['basic_salary'] ?? 0) * 0.3);
                    $retE2 = $row['retirement_e2'] ?? ($row['retirement_contribution'] ?? 0);
                    $retE3 = $row['retirement_e3'] ?? 30000;

                    $prmf = min($row['prmf'] ?? 0, 15000);
                    $ownerOccupiedInterest = min($row['owner_occupied_interest'] ?? 0, 30000);

                    $retirement = min($retE1, $retE2, $retE3);
                    $totalDeductions = $retirement + $housingLevy + $shif + $prmf + $ownerOccupiedInterest;
                    $chargeablePay = max(0, ($row['total_gross_pay'] ?? 0) - $totalDeductions);

                    $tempPay = $chargeablePay;
                    $taxCharged = 0;
                    if ($tempPay > 800000) { $taxCharged += ($tempPay - 800000) * 0.35; $tempPay = 800000; }
                    if ($tempPay > 500000) { $taxCharged += ($tempPay - 500000) * 0.325; $tempPay = 500000; }
                    if ($tempPay > 32333.33) { $taxCharged += ($tempPay - 32333.33) * 0.3; $tempPay = 32333.33; }
                    if ($tempPay > 24000) { $taxCharged += ($tempPay - 24000) * 0.25; $tempPay = 24000; }
                    $taxCharged += $tempPay * 0.1;

                    $personalRelief = $row['personal_relief'] ?? 2400;
                    $insuranceRelief = $row['insurance_relief'] ?? 0;
                    $paye = max(0, $taxCharged - $personalRelief - $insuranceRelief);

                    $displayRow = [
                        'basic_salary' => $row['basic_salary'] ?? 0,
                        'benefits_non_cash' => $row['benefits_non_cash'] ?? 0,
                        'value_of_quarters' => $row['value_of_quarters'] ?? 0,
                        'total_gross_pay' => $row['total_gross_pay'] ?? 0,
                        'retirement_e1' => $retE1,
                        'retirement_e2' => $retE2,
                        'retirement_e3' => $retE3,
                        'housing_levy' => $housingLevy,
                        'shif' => $shif,
                        'prmf' => $prmf,
                        'owner_occupied_interest' => $ownerOccupiedInterest,
                        'total_deductions' => $totalDeductions,
                        'chargeable_pay' => $chargeablePay,
                        'tax_charged' => $taxCharged,
                        'personal_relief' => $personalRelief,
                        'insurance_relief' => $insuranceRelief,
                        'paye' => $paye,
                    ];

                    foreach ($totalCols as $key => $val) {
                        $totalCols[$key] += $displayRow[$key] ?? 0;
                    }
                @endphp

                <tr>
                    <td class="text-left">{{ $monthName }}</td>
                    <td>{{ number_format($displayRow['basic_salary'], 2) }}</td>
                    <td>{{ number_format($displayRow['benefits_non_cash'], 2) }}</td>
                    <td>{{ number_format($displayRow['value_of_quarters'], 2) }}</td>
                    <td>{{ number_format($displayRow['total_gross_pay'], 2) }}</td>
                    <td>{{ number_format($displayRow['retirement_e1'], 2) }}</td>
                    <td>{{ number_format($displayRow['retirement_e2'], 2) }}</td>
                    <td>{{ number_format($displayRow['retirement_e3'], 2) }}</td>
                    <td>{{ number_format($displayRow['housing_levy'], 2) }}</td>
                    <td>{{ number_format($displayRow['shif'], 2) }}</td>
                    <td>{{ number_format($displayRow['prmf'], 2) }}</td>
                    <td>{{ number_format($displayRow['owner_occupied_interest'], 2) }}</td>
                    <td>{{ number_format($displayRow['total_deductions'], 2) }}</td>
                    <td>{{ number_format($displayRow['chargeable_pay'], 2) }}</td>
                    <td>{{ number_format($displayRow['tax_charged'], 2) }}</td>
                    <td>{{ number_format($displayRow['personal_relief'], 2) }}</td>
                    <td>{{ number_format($displayRow['insurance_relief'], 2) }}</td>
                    <td>{{ number_format($displayRow['paye'], 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td class="text-left"><strong>Total</strong></td>
                @foreach($totalCols as $val)
                    <td><strong>{{ number_format($val, 2) }}</strong></td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <p><strong>To be completed by Employer at end of year</strong></p>
        <p><strong>TOTAL CHARGEABLE PAY (COL. K):</strong> Kshs. {{ number_format($totalCols['chargeable_pay'], 2) }}</p>
        <p><strong>TOTAL TAX (COL. O):</strong> Kshs. {{ number_format($totalCols['paye'], 2) }}</p>
    </div>

    <div class="note">
        <strong>IMPORTANT</strong>
        <ol type="c">
            <li>
                Attach:<br>
                1. Use P9A<br>
                &nbsp;&nbsp;&nbsp;&nbsp;(a) For all liable employees and where director/employee received benefits in addition to cash emoluments.<br>
                &nbsp;&nbsp;&nbsp;&nbsp;(b) Where an employee is eligible to deduction on owner occupier interest.<br>
                &nbsp;&nbsp;&nbsp;&nbsp;(c) Where an employee contributes to a post retirement medical fund.<br>
                2. (i) Photostat copy of interest certificate and statement of account from the Financial Institution.<br>
                &nbsp;&nbsp;&nbsp;&nbsp;(ii) The DECLARATION duly signed by the employee.
            </li>
            <li>
                (a) Deductible interest in respect of any month prior to December 2024 must not exceed Kshs. 25,000/= and commencing December 2024 must not exceed 30,000/=<br>
                (b) Deductible pension contribution prior to December 2024: max 20,000/=; from December 2024: max 30,000/=<br>
                (c) Deductible contribution to PRMF from December 2024 must not exceed 15,000/= per month<br>
                (d) Contributions to SHIF and AHL effective December 2024<br>
                (e) Personal Relief: Kshs. 2,400/month or 28,800/year<br>
                (f) Insurance Relief: 15% of premiums up to Kshs. 5,000/month or 60,000/year
            </li>
        </ol>
    </div>

</div>
@empty
    <p>No P9 data available for {{ $year }}.</p>
@endforelse

</body>
</html>
