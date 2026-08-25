<!DOCTYPE html>
<html>

<head>
    <title>Tax Filing Report - {{ $payroll->payrun_year }}/{{ $payroll->payrun_month }}</title>
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
        <h1>Tax Filing Report</h1>
        <p>Business: {{ $payroll->business->name ?? 'N/A' }}</p>
        <p>Period: {{ $payroll->payrun_year }} - {{ str_pad($payroll->payrun_month, 2, '0', STR_PAD_LEFT) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>KRA PIN</th>
                <th>PAYE</th>
                <th>SHIF</th>
                <th>NSSF</th>
                <th>NHDF</th>
                <th>Total ({{ $payroll->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0;@endphp
            @forelse($payroll->employeePayrolls as $ep)
            @php $rowTotal = $ep->paye + $ep->shif + $ep->nssf + $ep->housing_levy;@endphp
            <tr>
                <td>{{ $ep->employee->full_name ?? 'N/A' }}</td>
                <td>{{ $ep->employee->tax_no ?? 'N/A' }}</td>
                <td>{{ number_format($ep->paye, 2) }}</td>
                <td>{{ number_format($ep->shif, 2) }}</td>
                <td>{{ number_format($ep->nssf, 2) }}</td>
                <td>{{ number_format($ep->housing_levy, 2) }}</td>
                <td>{{ number_format($rowTotal, 2) }}</td>
                @php $total += $rowTotal;@endphp
            </tr>
            @empty
            <tr>
                <td colspan="7">No data available</td>
            </tr>
            @endforelse
            <tr class="total">
                <td colspan="6">Total</td>
                <td>{{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>