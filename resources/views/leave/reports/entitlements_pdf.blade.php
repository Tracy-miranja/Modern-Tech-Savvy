<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Leave Entitlements Report - {{ $business->company_name }}</title>
    <style>
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        font-size: 12pt;
        color: #1a202c;
    }

    .header {
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

    .table td.sno,
    .table td.days {
        text-align: center;
    }

    .table th.sno {
        width: 5%;
    }

    .table th.employee {
        width: 20%;
        text-align: left;
    }

    .logo {
        max-height: 60px;
        max-width: 150px;
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
    <div class="header">
        <div class="left">
            @php
                $logoUrl = $business->getImageUrl();
                $logoBase64 = null;
                $filePath = $logoUrl ? public_path(parse_url($logoUrl, PHP_URL_PATH)) : null;
                if ($filePath && is_file($filePath)) {
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                    $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
                }
@endphp

            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="{{ $business->company_name }} Logo" class="logo">
            @else
                <div class="logo-placeholder">{{ strtoupper(substr($business->company_name ?? 'Company', 0, 1)) }}</div>
            @endif
            <h1>{{ $business->company_name ?? 'Company' }}</h1>
            <p class="text-muted">{{ $business->physical_address ?? '' }}</p>
            <p class="text-muted">Phone: {{ $business->phone ?? 'N/A' }}</p>
            <p class="text-muted">Email: {{ $business->user->email ?? '' }}</p>
        </div>
        <div class="right">
            <h2>Leave Entitlements Report</h2>
            <p class="text-muted">Leave Period: {{ $leavePeriod->name }}</p>
            <p class="text-muted">{{ $leavePeriod->start_date->format('jS M Y') }} - {{ $leavePeriod->end_date->format('jS M Y') }}</p>
            <p class="text-muted">Generated: {{ now()->format('F d, Y') }}</p>
            <p class="text-muted">Total Employees: {{ $rows->count() }}</p>
        </div>
    </div>

    @if ($rows->isEmpty())
        <p>No leave entitlements found for the selected filters.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th class="sno">S/No</th>
                    <th class="employee">Employee</th>
                    @foreach ($leaveTypeNames as $typeName)
                        <th>{{ $typeName }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $row)
                    <tr>
                        <td class="sno">{{ $index + 1 }}</td>
                        <td>{{ optional(optional($row['employee'])->user)->name ?? 'N/A' }}</td>
                        @foreach ($leaveTypeNames as $typeName)
                            <td class="days">{{ $row['remaining'][$typeName] !== null ? number_format($row['remaining'][$typeName], 2) : '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>

</html>
