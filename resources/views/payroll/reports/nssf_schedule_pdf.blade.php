<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NSSF Schedule - {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; color: #222; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1F4E79; padding-bottom: 10px; }
        .header h1 { font-size: 14pt; color: #1F4E79; }
        .header h2 { font-size: 11pt; color: #333; margin-top: 4px; }
        .header p  { font-size: 9pt; color: #555; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead tr { background: #1F4E79; color: #fff; }
        thead th { padding: 5px 6px; text-align: center; font-size: 8.5pt; border: 1px solid #ccc; }
        tbody tr:nth-child(even) { background: #F0F6FB; }
        tbody td { padding: 4px 6px; border: 1px solid #ddd; font-size: 8.5pt; }
        td.num { text-align: right; }
        td.ctr { text-align: center; }
        tfoot tr { background: #BDD7EE; font-weight: bold; }
        tfoot td { padding: 5px 6px; border: 1px solid #aaa; font-size: 9pt; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 9pt; }
        .meta span { color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $business->company_name ?? $business->name }}</h1>
        <h2>NSSF Remittance Schedule</h2>
        <p>
            Period: {{ \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F') }} {{ $payroll->payrun_year }}
            &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }}
        </p>
    </div>

    <div class="meta">
        <span>Employer NSSF No: {{ $business->nssf_no ?? 'N/A' }}</span>
        <span>KRA PIN: {{ $business->tax_pin_no ?? 'N/A' }}</span>
        <span>Total Employees: {{ count($data) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Payroll No</th>
                <th>Employee Name</th>
                <th>ID No</th>
                <th>NSSF No</th>
                <th>Gross Pay (KES)</th>
                <th>Employee (KES)</th>
                <th>Employer (KES)</th>
                <th>Total (KES)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalEmployee = 0;
                $totalEmployer = 0;
                $totalGross    = 0;
                $grandTotal    = 0;
            @endphp
            @foreach($data as $index => $row)
            @php
                $totalEmployee += $row['employee'];
                $totalEmployer += $row['employer'];
                $totalGross    += $row['gross_pay'];
                $grandTotal    += $row['total'];
            @endphp
            <tr>
                <td class="ctr">{{ $index + 1 }}</td>
                <td>{{ $row['payroll_no'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="ctr">{{ $row['id_no'] }}</td>
                <td class="ctr">{{ $row['nssf_no'] }}</td>
                <td class="num">{{ number_format($row['gross_pay'], 2) }}</td>
                <td class="num">{{ number_format($row['employee'], 2) }}</td>
                <td class="num">{{ number_format($row['employer'], 2) }}</td>
                <td class="num">{{ number_format($row['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">TOTALS</td>
                <td class="num">{{ number_format($totalGross, 2) }}</td>
                <td class="num">{{ number_format($totalEmployee, 2) }}</td>
                <td class="num">{{ number_format($totalEmployer, 2) }}</td>
                <td class="num">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top:30px; font-size:9pt; color:#555;">
        <p>Authorised Signature: _________________________________ &nbsp;&nbsp; Date: _________________</p>
    </div>
</body>
</html>
