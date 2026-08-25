<!DOCTYPE html>
<html>
<head>
    <title>KRA P9A Form - {{ $year }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        th, td {
            border: 1px solid black;
            padding: 3px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        h3 {
            text-align: center;
            margin: 0 0 5px;
            font-size: 12px;
        }
        .note {
            font-size: 9px;
            margin-top: 20px;
            line-height: 1.5;
        }
        .totals {
            margin-top: 20px;
            font-size: 10px;
            font-weight: bold;
        }
        .employee-details {
            margin-bottom: 10px;
            font-size: 10px;
        }
        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>KENYA REVENUE AUTHORITY</h3>
        <h3>DOMESTIC TAXES DEPARTMENT</h3>
        <h3>P9A FORM - {{ $year }}</h3>
    </div>

    <div class="employee-details">
        <p><strong>Employer's Name:</strong> {{ $data['employer_name'] ?? 'N/A' }} &nbsp;&nbsp;&nbsp;&nbsp; <strong>Employer's PIN:</strong> {{ $data['employer_pin'] ?? 'N/A' }}</p>
        <p><strong>Employee's Main Name:</strong> {{ $data['employee_main_name'] ?? 'N/A' }} &nbsp;&nbsp;&nbsp;&nbsp; <strong>Employee's PIN:</strong> {{ $data['employee_pin'] ?? 'N/A' }}</p>
        <p><strong>Employee's Other Names:</strong> {{ $data['employee_other_names'] ?? 'N/A' }}</p>
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
                <th></th>
                <th>A</th>
                <th>B</th>
                <th>C</th>
                <th>D</th>
                <th>E1</th>
                <th>E2</th>
                <th>E3</th>
                <th>F</th>
                <th>G</th>
                <th>H</th>
                <th>I</th>
                <th>J</th>
                <th>K</th>
                <th>L</th>
                <th>M</th>
                <th>N</th>
                <th>O</th>
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
                    'retirement_e1', 'retirement_e2', 'retirement_e3', 'ahl', 'shif', 'prmf',
                    'owner_occupied_interest', 'total_deductions', 'chargeable_pay', 'tax_charged',
                    'personal_relief', 'insurance_relief', 'paye'
                ], 0);

                $monthlyData = $data['monthly_data'] ?? [];
                if (!is_array($monthlyData)) {
                    $monthlyData = [];
                }
@endphp

           @foreach($months as $monthNumber => $monthName)
    @php

        $row = isset($monthlyData[$monthNumber]) ? $monthlyData[$monthNumber] : (
            isset($monthlyData[$monthNumber - 1]) ? $monthlyData[$monthNumber - 1] : [
                'basic_salary' => 0, 'benefits_non_cash' => 0, 'value_of_quarters' => 0,
                'total_gross_pay' => 0, 'retirement_e1' => 0, 'retirement_e2' => 0,
                'retirement_e3' => 30000, 'ahl' => 0, 'shif' => 0, 'prmf' => 0,
                'owner_occupied_interest' => 0, 'total_deductions' => 0,
                'chargeable_pay' => 0, 'tax_charged' => 0, 'personal_relief' => 2400,
                'insurance_relief' => 0, 'paye' => 0
            ]
        );

        if (!isset($row['retirement_e1']) || $row['retirement_e1'] == 0) {
            $row['retirement_e1'] = $row['basic_salary'] * 0.3;
        }

        if (!isset($row['ahl']) || $row['ahl'] == 0) {
            $row['ahl'] = $row['total_gross_pay'] * 0.015;
        }

        if (!isset($row['shif']) || $row['shif'] == 0) {
            $row['shif'] = $row['total_gross_pay'] * 0.0275;
        }

        if (!isset($row['retirement_e2'])) {
            $row['retirement_e2'] = 0;
        }
        if (!isset($row['retirement_e3'])) {
            $row['retirement_e3'] = 30000;
        }

        $row['prmf'] = min($row['prmf'], 15000);

        $row['owner_occupied_interest'] = min($row['owner_occupied_interest'], 30000);

        $retirement = min($row['retirement_e1'], $row['retirement_e2'], $row['retirement_e3']);

        $row['total_deductions'] = $retirement + $row['ahl'] + $row['shif'] + $row['prmf'] + $row['owner_occupied_interest'];

        $row['chargeable_pay'] = max(0, $row['total_gross_pay'] - $row['total_deductions']);

        $tempPay = $row['chargeable_pay'];
        $taxCharged = 0;

        if ($tempPay > 800000) {
            $taxCharged += ($tempPay - 800000) * 0.35;
            $tempPay = 800000;
        }

        if ($tempPay > 500000) {
            $taxCharged += ($tempPay - 500000) * 0.325;
            $tempPay = 500000;
        }

        if ($tempPay > 32333.33) {
            $taxCharged += ($tempPay - 32333.33) * 0.3;
            $tempPay = 32333.33;
        }

        if ($tempPay > 24000) {
            $taxCharged += ($tempPay - 24000) * 0.25;
            $tempPay = 24000;
        }

        $taxCharged += $tempPay * 0.1;
        $row['tax_charged'] = $taxCharged;

        $row['paye'] = max(0, $row['tax_charged'] - $row['personal_relief'] - $row['insurance_relief']);

        $totalCols['basic_salary'] += $row['basic_salary'];
        $totalCols['benefits_non_cash'] += $row['benefits_non_cash'];
        $totalCols['value_of_quarters'] += $row['value_of_quarters'];
        $totalCols['total_gross_pay'] += $row['total_gross_pay'];
        $totalCols['retirement_e1'] += $row['retirement_e1'];
        $totalCols['retirement_e2'] += $row['retirement_e2'];
        $totalCols['retirement_e3'] += $row['retirement_e3'];
        $totalCols['ahl'] += $row['ahl'];
        $totalCols['shif'] += $row['shif'];
        $totalCols['prmf'] += $row['prmf'];
        $totalCols['owner_occupied_interest'] += $row['owner_occupied_interest'];
        $totalCols['total_deductions'] += $row['total_deductions'];
        $totalCols['chargeable_pay'] += $row['chargeable_pay'];
        $totalCols['tax_charged'] += $row['tax_charged'];
        $totalCols['personal_relief'] += $row['personal_relief'];
        $totalCols['insurance_relief'] += $row['insurance_relief'];
        $totalCols['paye'] += $row['paye'];
@endphp

    <tr>
        <td class="text-left">{{ $monthName }}</td>
        <td>{{ number_format($row['basic_salary'], 2) }}</td>
        <td>{{ number_format($row['benefits_non_cash'], 2) }}</td>
        <td>{{ number_format($row['value_of_quarters'], 2) }}</td>
        <td>{{ number_format($row['total_gross_pay'], 2) }}</td>
        <td>{{ number_format($row['retirement_e1'], 2) }}</td>
        <td>{{ number_format($row['retirement_e2'], 2) }}</td>
        <td>{{ number_format($row['retirement_e3'], 2) }}</td>
        <td>{{ number_format($row['ahl'], 2) }}</td>
        <td>{{ number_format($row['shif'], 2) }}</td>
        <td>{{ number_format($row['prmf'], 2) }}</td>
        <td>{{ number_format($row['owner_occupied_interest'], 2) }}</td>
        <td>{{ number_format($row['total_deductions'], 2) }}</td>
        <td>{{ number_format($row['chargeable_pay'], 2) }}</td>
        <td>{{ number_format($row['tax_charged'], 2) }}</td>
        <td>{{ number_format($row['personal_relief'], 2) }}</td>
        <td>{{ number_format($row['insurance_relief'], 2) }}</td>
        <td>{{ number_format($row['paye'], 2) }}</td>
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
        <p>c) Attach</p>
        <p>1. Use P9A</p>
        <p>(a) For all liable employees and where director/employee received Benefits in addition to cash emoluments</p>
        <p>(i) Photostat copy of interest certificate and statement of account from the Financial Institution</p>
        <p>(b) Where an employee is eligible to deduction on owner occupier interest.</p>
        <p>(c) Where an employee contributes to a post retirement medical fund</p>
        <p>2. (a) Deductible interest in respect of any month prior to December 2024 must not exceed Kshs. 25,000/= and commencing December 2024 must not exceed 30,000/=</p>
        <p>(ii) The DECLARATION duly signed by the employee.</p>
        <p>(b) Deductible pension contribution in respect of any month prior to December 2024 must not exceed Kshs. 20,000/= and commencing December 2024 must not exceed 30,000/=</p>
        <p>(c) Deductible contribution to a post retirement medical fund in respect of any month is effective from December 2024, must not exceed Kshs. 15,000/=</p>
        <p>(d) Deductible Contribution to the Social Health Insurance Fund (SHIF) and deductions made towards Affordable Housing Levy (AHL) are effective December 2024</p>
        <p>(e) Personal Relief is Kshs. 2,400 per Month or 28,800 per year</p>
        <p>(f) Insurance Relief is 15% of the Premium up to a Maximum of Kshs. 5,000 per month or Kshs. 60,000 per year</p>
    </div>
</body>
</html>
