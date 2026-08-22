<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Report' }} - {{ $business->company_name ?? 'Company' }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            font-size: 12pt;
            color: #1a202c;
        }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .header-table td.right { text-align: right; }

        .logo { max-height: 60px; max-width: 150px; object-fit: contain; margin-bottom: 8px; }
        .logo-placeholder {
            width: 50px; height: 50px; background-color: #e5e7eb; text-align: center;
            line-height: 50px; font-size: 20pt; font-weight: bold; color: #6b7280; margin-bottom: 8px;
        }

        .company-name { font-size: 18pt; font-weight: 700; margin: 0; }
        .company-detail, .report-meta { color: #6b7280; font-size: 10pt; margin: 2px 0; }
        .report-title { font-size: 14pt; font-weight: 600; margin: 0; }

        .divider { border: none; border-top: 2px solid #1a202c; margin: 8px 0 15px; }

        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td {
            border: 1px solid #1a202c; padding: 6px; text-align: left;
            font-size: 9pt; word-wrap: break-word; vertical-align: top;
        }
        .table th {
            background-color: #1a202c; color: #fff; font-weight: 600;
            font-size: 10pt; text-transform: uppercase; text-align: center;
        }
        .table td.center { text-align: center; }
        .no-data { color: #999; font-style: italic; }

        @page { margin: 8mm; }

        {{-- Report-specific extra styles, if any --}}
        @yield('styles')
    </style>
</head>
<body>
    @include('components.reports.letterhead', [
        'business' => $business,
        'reportTitle' => $reportTitle ?? 'Report',
        'periodLabel' => $periodLabel ?? null,
        'meta' => $meta ?? [],
    ])

    @yield('content')
</body>
</html>
