<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NSSF Monthly Summary — {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8pt; color: #222; }

        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1F4E79; padding-bottom: 8px; }
        .header h1 { font-size: 14pt; color: #1F4E79; font-weight: bold; }
        .header h2 { font-size: 11pt; color: #333; margin-top: 3px; }
        .header p  { font-size: 8pt;  color: #666; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }

        thead tr { background: #1F4E79; color: #fff; }
        thead th { padding: 5px 3px; text-align: center; border: 1px solid #ccc; font-size: 7.5pt; white-space: nowrap; }
        thead th.name-col { text-align: left; width: 18%; }

        tbody tr:nth-child(even) { background: #f5f8fc; }
        tbody td { padding: 3px 3px; border: 1px solid #ddd; font-size: 7.5pt; }
        tbody td.name-col { text-align: left; }
        tbody td.num { text-align: right; }
        tbody td.dash { text-align: center; color: #aaa; }

        .total-row { background: #BDD7EE !important; font-weight: bold; font-size: 8.5pt; }
        .total-row td { border: 1px solid #aaa; padding: 4px 3px; }

        .footer { margin-top: 20px; font-size: 8pt; color: #555; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $business->company_name ?? $business->name }}</h1>
    <h2>NSSF Monthly Summary &mdash; Year {{ $year }}</h2>
    <p>Generated: {{ now()->format('d M Y H:i') }}</p>
</div>

@php
    $monthNames = [
        1 => 'Jan', 2 => 'Feb',  3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun',  7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    $totalRow     = !empty($rows) ? end($rows) : [];
    $employeeRows = array_slice($rows, 0, count($rows) - 1);
@endphp

<table>
    <thead>
        <tr>
            <th class="name-col">Employee Name</th>
            @foreach($monthNames as $m => $label)
                <th>{{ $label }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>

        @foreach($employeeRows as $row)
        <tr>
            <td class="name-col">{{ $row['name'] }}</td>
            @foreach(range(1, 12) as $m)
                @php

                    $val = $row[$m] ?? null;
@endphp
                @if($val !== null && $val > 0)
                    <td class="num">{{ number_format($val, 2) }}</td>
                @else
                    <td class="dash">-</td>
                @endif
            @endforeach
            <td class="num">{{ number_format($row['total'] ?? 0, 2) }}</td>
        </tr>
        @endforeach

        <tr class="total-row">
            <td class="name-col">TOTAL</td>
            @foreach(range(1, 12) as $m)
                @php $val = $totalRow[$m] ?? null;@endphp
                @if($val !== null && $val > 0)
                    <td class="num">{{ number_format($val, 2) }}</td>
                @else
                    <td class="dash">-</td>
                @endif
            @endforeach
            <td class="num">{{ number_format($totalRow['total'] ?? 0, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    <p>Authorised Signature: _________________________________ &nbsp;&nbsp;&nbsp; Date: _________________</p>
</div>

</body>
</html>
