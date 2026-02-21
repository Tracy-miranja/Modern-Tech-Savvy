<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payroll Variance Report</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; color: #222; }
        .header { text-align:center; margin-bottom:12px; border-bottom:2px solid #1F4E79; padding-bottom:8px; }
        .header h1 { font-size:14pt; color:#1F4E79; font-weight:bold; }
        .header h2 { font-size:10pt; color:#333; margin-top:3px; }
        .header p  { font-size:8pt;  color:#666; margin-top:2px; }
        .section   { font-size:10pt; font-weight:bold; color:#1F4E79; margin:12px 0 5px 0;
                     border-bottom:1px solid #1F4E79; padding-bottom:2px; }
        table { width:100%; border-collapse:collapse; margin-bottom:14px; }
        thead tr { background:#1F4E79; color:#fff; }
        thead th { padding:5px 4px; text-align:center; border:1px solid #ccc; font-size:8pt; }
        thead th.left { text-align:left; }
        tbody tr:nth-child(even) { background:#EBF5FB; }
        tbody td { padding:3px 4px; border:1px solid #ddd; font-size:8.5pt; }
        td.num { text-align:right; }
        td.ctr { text-align:center; }
        .inc { color:#C0392B; font-weight:bold; }
        .dec { color:#1E8449; font-weight:bold; }
        .total-row { background:#BDD7EE !important; font-weight:bold; }
        .total-row td { border:1px solid #aaa; padding:4px; }
        .footer { margin-top:18px; font-size:8pt; color:#555; }
    </style>
</head>
<body>

@php
    $monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
                   7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
    $cur = $business->currency ?? 'KES';

    if ($params['mode'] === 'year') {
        $p1Label = (string) $params['year1'];
        $p2Label = (string) $params['year2'];
    } else {
        $p1Label = ($monthNames[$params['month1']] ?? '') . ' ' . $params['year1'];
        $p2Label = ($monthNames[$params['month2']] ?? '') . ' ' . $params['year2'];
    }
@endphp

<div class="header">
    <h1>{{ $business->company_name ?? $business->name }}</h1>
    <h2>Payroll Variance: {{ $p1Label }} vs {{ $p2Label }}</h2>
    <p>Generated: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; Currency: {{ $cur }}</p>
</div>

{{-- Summary table --}}
<p class="section">Key Metrics Comparison</p>
<table>
    <thead>
        <tr>
            <th class="left">Metric</th>
            <th>{{ $p1Label }} ({{ $cur }})</th>
            <th>{{ $p2Label }} ({{ $cur }})</th>
            <th>Variance ({{ $cur }})</th>
            <th>Variance %</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['summary'] as $row)
        @php $cls = $row['variance'] > 0 ? 'inc' : ($row['variance'] < 0 ? 'dec' : ''); @endphp
        <tr>
            <td>{{ $row['metric'] }}</td>
            <td class="num">{{ number_format($row['period1'], 2) }}</td>
            <td class="num">{{ number_format($row['period2'], 2) }}</td>
            <td class="num {{ $cls }}">
                @if($row['variance'] < 0)({{ number_format(abs($row['variance']), 2) }})
                @else {{ number_format($row['variance'], 2) }}
                @endif
            </td>
            <td class="ctr {{ $cls }}">{{ $row['variance_pct'] }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Month-by-month (year mode only) --}}
@if($params['mode'] === 'year' && !empty($data['monthly']))
<p class="section">Month-by-Month Gross Pay</p>
<table>
    <thead>
        <tr>
            <th class="left">Month</th>
            <th>{{ $p1Label }} ({{ $cur }})</th>
            <th>{{ $p2Label }} ({{ $cur }})</th>
            <th>Variance ({{ $cur }})</th>
            <th>Variance %</th>
            <th>Headcount {{ $p1Label }}</th>
            <th>Headcount {{ $p2Label }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['monthly'] as $row)
        @if($row['period1'] > 0 || $row['period2'] > 0)
        @php $cls = $row['variance'] > 0 ? 'inc' : ($row['variance'] < 0 ? 'dec' : ''); @endphp
        <tr>
            <td>{{ $row['month'] }}</td>
            <td class="num">{{ $row['period1'] > 0 ? number_format($row['period1'],2) : '-' }}</td>
            <td class="num">{{ $row['period2'] > 0 ? number_format($row['period2'],2) : '-' }}</td>
            <td class="num {{ $cls }}">
                @if($row['variance'] < 0)({{ number_format(abs($row['variance']),2) }})
                @elseif($row['variance'] > 0){{ number_format($row['variance'],2) }}
                @else -
                @endif
            </td>
            <td class="ctr {{ $cls }}">{{ $row['var_pct'] }}%</td>
            <td class="ctr">{{ $row['count1'] ?: '-' }}</td>
            <td class="ctr">{{ $row['count2'] ?: '-' }}</td>
        </tr>
        @endif
        @endforeach

        {{-- Totals row --}}
        @php
            $t1 = collect($data['monthly'])->sum('period1');
            $t2 = collect($data['monthly'])->sum('period2');
            $tv = $t2 - $t1;
            $tp = $t1 != 0 ? round(($tv/abs($t1))*100,2) : 0;
            $cls = $tv > 0 ? 'inc' : ($tv < 0 ? 'dec' : '');
        @endphp
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="num">{{ number_format($t1,2) }}</td>
            <td class="num">{{ number_format($t2,2) }}</td>
            <td class="num {{ $cls }}">
                @if($tv < 0)({{ number_format(abs($tv),2) }})
                @else{{ number_format($tv,2) }}
                @endif
            </td>
            <td class="ctr {{ $cls }}">{{ $tp }}%</td>
            <td class="ctr">—</td>
            <td class="ctr">—</td>
        </tr>
    </tbody>
</table>
@endif

<div class="footer">
    <p>Authorised Signature: _________________________________ &nbsp;&nbsp; Date: _________________</p>
</div>
</body>
</html>
