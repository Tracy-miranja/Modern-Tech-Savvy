<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Bank Advice Report - Payroll {{ $payroll->id }}</title>
    <style>
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        font-size: 12pt;
        color: #1a202c;
    }

    .header,
    .footer {
        width: 100%;
        padding-bottom: 8px;
        margin-bottom: 15px;
        border-bottom: 2px solid #1a202c;
    }

    .header .left,
    .header .right {
        width: 48%;
        display: inline-block;
        vertical-align: top;
    }

    .header .left {
        margin-right: 3%;
    }

    .header .right {
        text-align: right;
    }

    .header h1 {
        font-size: 18pt;
        margin: 0;
        font-weight: 700;
    }

    .header h2 {
        font-size: 14pt;
        margin: 0;
        font-weight: 600;
    }

    .text-muted {
        color: #6b7280;
        font-size: 10pt;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .table th,
    .table td {
        border: 1px solid #1a202c;
        padding: 6px;
        text-align: left;
        font-size: 9pt;
        word-wrap: break-word;
        vertical-align: top;
    }

    .table th {
        background-color: #1a202c;
        color: #fff;
        font-weight: 600;
        font-size: 10pt;
        text-transform: uppercase;
        text-align: center;
    }

    .table td {
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table th:nth-child(1),
    .table td:nth-child(1) {
        width: 15%;
    }

    /* Employee Name */
    .table th:nth-child(2),
    .table td:nth-child(2) {
        width: 10%;
    }

    /* Employee Code */
    .table th:nth-child(3),
    .table td:nth-child(3) {
        width: 12%;
    }

    /* Bank Name */
    .table th:nth-child(4),
    .table td:nth-child(4) {
        width: 8%;
    }

    /* Bank Code */
    .table th:nth-child(5),
    .table td:nth-child(5) {
        width: 12%;
    }

    /* Bank Branch */
    .table th:nth-child(6),
    .table td:nth-child(6) {
        width: 8%;
    }

    /* Branch Code */
    .table th:nth-child(7),
    .table td:nth-child(7) {
        width: 12%;
    }

    /* Account Number */
    .table th:nth-child(8),
    .table td:nth-child(8) {
        width: 8%;
    }

    /* Payment Mode */
    .table th:nth-child(9),
    .table td:nth-child(9) {
        width: 8%;
    }

    /* Currency */
    .table th:nth-child(10),
    .table td:nth-child(10) {
        width: 7%;
    }

    /* Net Pay */

    .footer {
        margin-top: 15px;
        border-top: 2px solid #1a202c;
        padding-top: 8px;
        text-align: left;
    }

    .logo {
        max-height: 50px;
        max-width: 120px;
        object-fit: contain;
        margin-bottom: 8px;
    }

    .logo-placeholder {
        width: 50px;
        height: 50px;
        background-color: #e5e7eb;
        text-align: center;
        line-height: 50px;
        font-size: 20pt;
        font-weight: bold;
        color: #6b7280;
        margin-bottom: 8px;
    }

    @page {
        margin: 8mm;
    }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="left">
            @php
            $logoUrl = $payroll->business->getImageUrl();
            $logoBase64 = null;

            $filePath = public_path(parse_url($logoUrl, PHP_URL_PATH));

            if (is_file($filePath)) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
            }
            @endphp

            @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="{{ $payroll->business->company_name }} Logo"
                style="max-height:60px; max-width:150px; object-fit:contain;">
            @else
            <div class="logo-placeholder">{{ strtoupper(substr($payroll->business->company_name ?? 'Company', 0, 1)) }}
            </div>
            @endif
            <h1>{{ $payroll->business->company_name ?? 'Default Company Name' }}</h1>
            <p class="text-muted">{{ $payroll->business->physical_address ?? 'Default Address' }}</p>
            <p class="text-muted">Phone: {{ $payroll->business->phone ?? '+123-456-7890' }}</p>
            <p class="text-muted">Email: {{ $payroll->business->user->email ?? 'info@company.com' }}</p>
        </div>
        <div class="right">
            <h2>Bank Advice Report</h2>
            <p class="text-muted">Payroll Period: {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}</p>
            <p class="text-muted">Payroll ID: {{ $payroll->id }}</p>
            <p class="text-muted">Currency: {{ $payroll->currency ?? 'KES' }}</p>
            <p class="text-muted">Date: {{ now()->format('F d, Y') }}</p>
        </div>
    </div>

    <!-- Table -->
    @php
    $data = isset($data) ? $data : $payroll->employeePayrolls->map(function ($ep) {
    return [
    'employee_name' => $ep->employee->user->name ?? 'N/A',
    'employee_code' => $ep->employee->employee_code ?? 'N/A',
    'bank_name' => $ep->employee->paymentDetails->bank_name ?? 'N/A',
    'bank_code' => $ep->employee->paymentDetails->bank_code ?? 'N/A',
    'bank_branch' => $ep->employee->paymentDetails->bank_branch ?? 'N/A',
    'bank_branch_code' => $ep->employee->paymentDetails->bank_branch_code ?? 'N/A',
    'account_number' => $ep->employee->paymentDetails->account_number ?? 'N/A',
    'status' => $ep->employee->paymentDetails->payment_mode ?? 'N/A',
    'currency' => $ep->employee->paymentDetails->currency ?? 'N/A',
    'net_pay' => $ep->employee->paymentDetails->basic_salary ?? 0, // Unformatted for calculation
    'net_pay_formatted' => number_format($ep->employee->paymentDetails->basic_salary ?? 0, 2), // Formatted for display
    ];
    })->toArray();
    $business = $business ?? $payroll->business;
    $totals = isset($totals) ? $totals : ['totalNetPay' => array_sum(array_column($data, 'net_pay'))];
    @endphp

    @if(empty($data))
    <p>No payment details available for this payroll.</p>
    @else
   <table class="table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Amount</th>
            <th>Debit Account</th>
            <th>Debit Bank</th>
            <th>Debit Branch</th>
            <th>Employee Account</th>
            <th>Employee Bank</th>
            <th>Employee Branch</th>
            <th>Employee Name</th>
            <th>Reference</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
        <tr>
            <td>{{ $row['employee_code'] }}</td>
            <td>{{ $row['net_pay_formatted'] }}</td>

            <!-- Manual entry fields -->
            <td>{{ $manualDebitAccount ?? '' }}</td>
            <td>{{ $manualDebitBank ?? '' }}</td>
            <td>{{ $manualDebitBranch ?? '' }}</td>

            <!-- Employee details -->
            <td>{{ $row['account_number'] }}</td>
            <td>{{ $row['bank_name'] }}</td>
            <td>{{ $row['bank_branch'] }}</td>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ $row['reference'] ?? '' }}</td>
            <td>{{ $row['status'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="10" class="text-right"><strong>Total:</strong></td>
            <td><strong>{{ number_format($totals['totalNetPay'] ?? 0, 2) }}</strong></td>
        </tr>
    </tfoot>
</table>

    @endif

    <!-- Footer -->
    <div class="footer">
        <p class="text-muted">Generated on: {{ now()->format('F d, Y H:i:s') }}</p>
        <p class="text-muted">For official use only.</p>
    </div>
</body>

</html>
