<!DOCTYPE html>
<html>

<head>
    <title>NHDF Report - {{ $payroll->payrun_year }}/{{ $payroll->payrun_month }}</title>
    <style>
    body {
        font-family: Arial, sans-serif;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .total {
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="header">
        <h1>NHDF Contribution Report</h1>
        <p>Business: {{ $payroll->business->name ?? 'N/A' }}</p>
        <p>Period: {{ $payroll->payrun_year }} - {{ str_pad($payroll->payrun_month, 2, '0', STR_PAD_LEFT) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>KRA PIN</th>
                <th>Gross Pay ({{ $payroll->currency }})</th>
                <th>NHDF ({{ $payroll->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @php $totalNhdf = 0;@endphp
            @forelse($payroll->employeePayrolls as $ep)
            <tr>
                <td>{{ $ep->employee->full_name ?? 'N/A' }}</td>
                <td>{{ $ep->employee->tax_no ?? 'N/A' }}</td>
                <td>{{ number_format($ep->gross_pay, 2) }}</td>
                <td>{{ number_format($ep->housing_levy, 2) }}</td>
                @php $totalNhdf += $ep->housing_levy;@endphp
            </tr>
            @empty
            <tr>
                <td colspan="4">No data available</td>
            </tr>
            @endforelse
            <tr class="total">
                <td colspan="3">Total</td>
                <td>{{ number_format($totalNhdf, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>