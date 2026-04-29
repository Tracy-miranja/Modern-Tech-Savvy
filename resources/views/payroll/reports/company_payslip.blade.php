<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payroll Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8px;
            color: #222;
        }

        /* ── HEADER ─────────────────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td { vertical-align: top; padding: 2px 4px; }
        .logo {
            max-height: 50px;
            max-width: 120px;
        }
        .company-name { font-size: 14px; font-weight: bold; color: #1a1a2e; }
        .company-detail { font-size: 8px; color: #555; margin-top: 2px; }
        .report-title { font-size: 14px; font-weight: bold; text-align: right; color: #1a1a2e; }
        .report-meta { font-size: 8px; color: #555; text-align: right; margin-top: 2px; }

        .divider {
            border: none;
            border-top: 1.5px solid #1a1a2e;
            margin: 6px 0;
        }

        /* ── SECTION TITLES ─────────────────────────────────────────── */
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #1a1a2e;
            background: #f0f0f0;
            padding: 4px 6px;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        /* ── DATA TABLES ─────────────────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            table-layout: fixed;
        }
        .data-table thead tr {
            background-color: #1a1a2e;
            color: #ffffff;
        }
        .data-table thead th {
            padding: 4px 3px;
            font-size: 7px;
            font-weight: bold;
            text-align: left;
            border: 1px solid #ccc;
            word-wrap: break-word;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .data-table tbody td {
            padding: 3px;
            font-size: 7px;
            border: 1px solid #ddd;
            word-wrap: break-word;
        }
        .data-table tfoot tr {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        .data-table tfoot td {
            padding: 4px 3px;
            font-size: 7px;
            border: 1px solid #ccc;
        }

        /* ── SUMMARY BAR ─────────────────────────────────────────────── */
        .summary-bar {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 6px;
            background: #1a1a2e;
            color: #fff;
        }
        .summary-bar td {
            padding: 5px 8px;
            font-size: 8px;
            font-weight: bold;
        }

        /* ── SIGNATORIES ─────────────────────────────────────────────── */
        .sig-table {
            width: 60%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .sig-table th {
            background: #1a1a2e;
            color: #fff;
            padding: 3px 6px;
            font-size: 7.5px;
            border: 1px solid #ccc;
        }
        .sig-table td {
            border: 1px solid #ccc;
            padding: 8px 6px;
            font-size: 7.5px;
        }

        .footer-note {
            font-size: 7px;
            color: #888;
            margin-top: 12px;
            text-align: right;
        }

        .no-data { color: #999; font-style: italic; }
    </style>
</head>
<body>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- HEADER — flat table so DomPDF renders it immediately on page 1  --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<table class="header-table">
    <tr>
        <td width="50%">
            @php
                $logoUrl  = $entity->getImageUrl() ?? $business->getImageUrl();
                $logoBase64 = null;
                try {
                    $filePath = public_path(parse_url($logoUrl, PHP_URL_PATH));
                    if ($filePath && is_file($filePath)) {
                        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                        $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
                    }
                } catch (\Exception $e) {}
            @endphp

            @if($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
            @else
                <span style="font-size:18px; font-weight:bold; color:#1a1a2e;">
                    {{ strtoupper(substr($entity->company_name ?? $entity->name ?? 'C', 0, 1)) }}
                </span>
            @endif

            <div class="company-name" style="margin-top:4px;">
                {{ $entity->company_name ?? $entity->name ?? 'Company Name' }}
            </div>
            <div class="company-detail">{{ $entity->physical_address ?? '' }}</div>
            <div class="company-detail">
                Phone: {{ ($entityType === 'business' ? $entity->phone : $business->phone) ?? '' }}
            </div>
            <div class="company-detail">
                Email: {{ ($entityType === 'business' && isset($entity->user) ? $entity->user->email : (isset($business->user) ? $business->user->email : '')) ?? '' }}
            </div>
        </td>
        <td width="50%">
            <div class="report-title">Payroll Report</div>
            <div class="report-meta">
                Period: {{ $payroll->payrun_year }} - {{ str_pad($payroll->payrun_month, 2, '0', STR_PAD_LEFT) }}
            </div>
            <div class="report-meta">Payroll ID: {{ $payroll->id }}</div>
            <div class="report-meta">Currency: {{ $currency ?? 'KES' }}</div>
            <div class="report-meta">Date: {{ now()->format('F d, Y') }}</div>
        </td>
    </tr>
</table>

<hr class="divider">

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- SUMMARY BAR                                                      --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<table class="summary-bar">
    <tr>
        <td>{{ count($data) }} payslip(s)</td>
        <td>Total payroll: {{ number_format($totals['totalGrossPay'], 2) }}</td>
        <td>Total net pay: {{ number_format($totals['totalNetPay'], 2) }}</td>
    </tr>
</table>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 1 — EMPLOYEE DETAILS                                     --}}
{{-- Rendered immediately below the header — no blank page            --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="section-title">Employee Details</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:22%">Name</th>
            <th style="width:10%">Code</th>
            <th style="width:14%">Tax No</th>
            <th style="width:18%">Bank Name</th>
            <th style="width:18%">Account Number</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $row)
        <tr>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ $row['employee_code'] }}</td>
            <td>{{ $row['tax_no'] }}</td>
            <td>{{ $row['bank_name'] }}</td>
            <td>{{ $row['account_number'] }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="no-data">No data available</td></tr>
        @endforelse
    </tbody>
</table>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 2 — EARNINGS & TAX                                       --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="section-title">Earnings and Tax</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:14%">Name</th>
            <th style="width:8%">Basic Salary ({{ $currency ?? 'KES' }})</th>
            <th style="width:8%">Gross Pay</th>
            <th style="width:7%">Overtime</th>
            <th style="width:8%">Taxable Income</th>
            <th style="width:7%">PAYE</th>
            <th style="width:8%">PAYE Before Reliefs</th>
            <th style="width:8%">Personal Relief</th>
            <th style="width:8%">Insurance Relief</th>
            <th style="width:8%">Pay After Tax</th>
            <th style="width:8%">Net Pay</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $row)
        <tr>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ number_format($row['basic_salary'], 2) }}</td>
            <td>{{ number_format($row['gross_pay'], 2) }}</td>
            <td>{{ number_format($row['overtime'], 2) }}</td>
            <td>{{ number_format($row['taxable_income'], 2) }}</td>
            <td>{{ number_format($row['paye'], 2) }}</td>
            <td>{{ number_format($row['paye_before_reliefs'], 2) }}</td>
            <td>{{ number_format($row['personal_relief'], 2) }}</td>
            <td>{{ number_format($row['insurance_relief'], 2) }}</td>
            <td>{{ number_format($row['pay_after_tax'], 2) }}</td>
            <td>{{ number_format($row['net_pay'], 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="11" class="no-data">No data available</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td><strong>Totals</strong></td>
            <td>{{ number_format($totals['totalBasicSalary'], 2) }}</td>
            <td>{{ number_format($totals['totalGrossPay'], 2) }}</td>
            <td>{{ number_format($totals['totalOvertime'], 2) }}</td>
            <td>{{ number_format($totals['totalTaxableIncome'], 2) }}</td>
            <td>{{ number_format($totals['totalPaye'], 2) }}</td>
            <td>{{ number_format($totals['totalPayeBeforeReliefs'], 2) }}</td>
            <td>{{ number_format($totals['totalPersonalRelief'], 2) }}</td>
            <td>{{ number_format($totals['totalInsuranceRelief'], 2) }}</td>
            <td>{{ number_format($totals['totalPayAfterTax'], 2) }}</td>
            <td>{{ number_format($totals['totalNetPay'], 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 3 — DEDUCTIONS & ATTENDANCE                              --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="section-title">Deductions and Attendance</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width:13%">Name</th>
            <th style="width:7%">SHIF</th>
            <th style="width:7%">NSSF</th>
            <th style="width:8%">Housing Levy</th>
            <th style="width:6%">HELB</th>
            <th style="width:8%">Loan Repayment</th>
            <th style="width:8%">Advance Recovery</th>
            <th style="width:8%">Custom Deductions</th>
            <th style="width:9%">Deductions After Tax</th>
            <th style="width:6%">Days Present</th>
            <th style="width:6%">Days Absent</th>
            <th style="width:7%">Days in Month</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $row)
        <tr>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ number_format($row['shif'], 2) }}</td>
            <td>{{ number_format($row['nssf'], 2) }}</td>
            <td>{{ number_format($row['housing_levy'], 2) }}</td>
            <td>{{ number_format($row['helb'], 2) }}</td>
            <td>{{ number_format($row['loan_repayment'], 2) }}</td>
            <td>{{ number_format($row['advance_recovery'], 2) }}</td>
            <td>{{ number_format($row['custom_deductions'], 2) }}</td>
            <td>{{ number_format($row['deductions_after_tax'], 2) }}</td>
            <td>{{ $row['attendance_present'] }}</td>
            <td>{{ $row['attendance_absent'] }}</td>
            <td>{{ $row['days_in_month'] }}</td>
        </tr>
        @empty
        <tr><td colspan="12" class="no-data">No data available</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td><strong>Totals</strong></td>
            <td>{{ number_format($totals['totalShif'], 2) }}</td>
            <td>{{ number_format($totals['totalNssf'], 2) }}</td>
            <td>{{ number_format($totals['totalHousingLevy'], 2) }}</td>
            <td>{{ number_format($totals['totalHelb'], 2) }}</td>
            <td>{{ number_format($totals['totalLoans'], 2) }}</td>
            <td>{{ number_format($totals['totalAdvances'], 2) }}</td>
            <td>{{ number_format($totals['totalCustomDeductions'], 2) }}</td>
            <td>{{ number_format($totals['totalDeductionsAfterTax'], 2) }}</td>
            <td>{{ $totals['totalAttendancePresent'] }}</td>
            <td>{{ $totals['totalAttendanceAbsent'] }}</td>
            <td>{{ $totals['totalDaysInMonth'] }}</td>
        </tr>
    </tfoot>
</table>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- SIGNATORIES                                                       --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="section-title">
    Scales Master roll for the month of
    @php
        try { echo \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F'); }
        catch(\Exception $e) { echo $payroll->payrun_month; }
    @endphp
    ({{ str_pad($payroll->payrun_month, 2, '0', STR_PAD_LEFT) }}), {{ $payroll->payrun_year }}
</div>

<table class="sig-table">
    <thead>
        <tr>
            <th></th>
            <th>Name</th>
            <th>Date</th>
            <th>Sign</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Prepared by</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Verified by</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Approved by</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>Authorized by</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    </tbody>
</table>

<div class="footer-note">
    Generated on: {{ now()->format('F d, Y H:i:s') }} &nbsp;|&nbsp; For official use only.
</div>

</body>
</html>
