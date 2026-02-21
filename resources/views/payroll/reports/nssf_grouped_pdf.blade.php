<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NSSF Grouped by {{ $groupBy }} - {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; color: #222; }
        .header { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #1F4E79; padding-bottom: 8px; }
        .header h1 { font-size: 14pt; color: #1F4E79; }
        .header h2 { font-size: 11pt; color: #333; margin-top: 4px; }
        .header p  { font-size: 8pt; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .group-header { background: #2E75B6; color: #fff; font-weight: bold; font-size: 10pt; }
        .group-header td { padding: 5px 8px; }
        thead.col-header tr { background: #1F4E79; color: #fff; }
        thead.col-header th { padding: 4px 5px; text-align: center; font-size: 8pt; border: 1px solid #ccc; }
        tbody tr:nth-child(even) { background: #F0F6FB; }
        tbody td { padding: 3px 5px; border: 1px solid #ddd; font-size: 8.5pt; }
        td.num { text-align: right; }
        td.ctr { text-align: center; }
        .subtotal-row { background: #DEEAF1 !important; font-weight: bold; font-style: italic; }
        .grand-total-row { background: #BDD7EE !important; font-weight: bold; font-size: 10pt; }
        .grand-total-row td { padding: 5px; border: 1px solid #aaa; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $business->company_name ?? $business->name }}</h1>
        <h2>NSSF Grouped by {{ $groupBy }}</h2>
        <p>
            Period: {{ \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F') }} {{ $payroll->payrun_year }}
            &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }}
        </p>
    </div>

    @php
        $overallTotal = 0;
        $overallEmployee = 0;
        $overallEmployer = 0;
    @endphp

    @foreach($data as $group)
    <table>
        <tr class="group-header">
            <td colspan="9">{{ strtoupper($group['group_name']) }}</td>
        </tr>
        <thead class="col-header">
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
            @foreach($group['employees'] as $idx => $row)
            <tr>
                <td class="ctr">{{ $idx + 1 }}</td>
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
        <tbody>
            <tr class="subtotal-row">
                <td colspan="5" style="text-align:right;">Sub-total ({{ $group['group_name'] }})</td>
                <td class="num">{{ number_format($group['subtotal']['gross_pay'], 2) }}</td>
                <td class="num">{{ number_format($group['subtotal']['employee'], 2) }}</td>
                <td class="num">{{ number_format($group['subtotal']['employer'], 2) }}</td>
                <td class="num">{{ number_format($group['subtotal']['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>
    @php
        $overallTotal    += $group['subtotal']['total'];
        $overallEmployee += $group['subtotal']['employee'];
        $overallEmployer += $group['subtotal']['employer'];
    @endphp
    @endforeach

    <table>
        <tbody>
            <tr class="grand-total-row">
                <td style="width:55%; text-align:right; padding:6px;">GRAND TOTAL</td>
                <td class="num" style="width:15%;">{{ number_format($overallEmployee, 2) }}</td>
                <td class="num" style="width:15%;">{{ number_format($overallEmployer, 2) }}</td>
                <td class="num" style="width:15%;">{{ number_format($overallTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:24px; font-size:9pt; color:#555;">
        <p>Authorised Signature: _________________________________ &nbsp;&nbsp; Date: _________________</p>
    </div>
</body>
</html>
