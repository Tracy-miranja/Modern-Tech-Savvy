<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ ucwords(str_replace('_', ' ', $column)) }} Report - Payroll {{ $payroll->id }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 5mm;
            font-size: 12pt;
            color: #1a202c;
        }

        .header,
        .footer {
            width: 100%;
            padding-bottom: 10px;
            margin-bottom: 20px;
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
            font-size: 20pt;
            margin: 0;
            font-weight: 700;
        }

        .header h2 {
            font-size: 16pt;
            margin: 0;
            font-weight: 600;
        }

        .text-muted {
            color: #6b7280;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #1a202c;
            padding: 10px;
            text-align: left;
        }

        .table th {
            background-color: #1a202c;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }

        .table td {
            font-size: 11pt;
        }

        .footer {
            margin-top: 20px;
            border-top: 2px solid #1a202c;
            padding-top: 10px;
            text-align: left;
        }

        .logo {
            max-height: 60px;
            max-width: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        @page {
            margin: 10mm;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="left">
            @php
            $logoUrl = $business->getImageUrl();
            $logoBase64 = null;

            $filePath = public_path(parse_url($logoUrl, PHP_URL_PATH));

            if (is_file($filePath)) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
            }
@endphp

            @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="{{ $business->company_name }} Logo"
                style="max-height:60px; max-width:150px; object-fit:contain;">
            @else
            <div class="logo-placeholder">{{ strtoupper(substr($business->company_name ?? 'Company', 0, 1)) }}</div>
            @endif
            <h1>{{ $business->company_name ?? 'Default Company Name' }}</h1>
            <p class="text-muted">{{ $business->physical_address ?? 'Default Address' }}</p>
            <p class="text-muted">Phone: {{ $business->phone ?? '+123-456-7890' }}</p>
            <p class="text-muted">Email: {{ $business->user->email ?? 'info@company.com' }}</p>
        </div>
        <div class="right">
            <h2>{{ ucwords(str_replace('_', ' ', $column)) }} Report</h2>
            <p class="text-muted">Payroll Period: {{ $payroll->payrun_month }}/{{ $payroll->payrun_year }}</p>
            <p class="text-muted">Payroll ID: {{ $payroll->id }}</p>
            <p class="text-muted">Currency: {{ $payroll->currency ?? 'KES' }}</p>
            <p class="text-muted">Date: {{ now()->format('F d, Y') }}</p>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                @if($column === 'paye')
                <th>PIN of Employee</th>
                <th>Name of Employee</th>
                <th>Resident Status</th>
                <th>Type of Employee</th>
                <th>Total Cash Pay ({{ $currency }})</th>
                <th>NSSF ({{ $currency }})</th>
                <th>SHIF ({{ $currency }})</th>
                <th>Housing Levy ({{ $currency }})</th>
                <th>Personal Relief ({{ $currency }})</th>
                <th>PAYE Tax ({{ $currency }})</th>
                @elseif($column === 'shif')
                <th>PAYROLL NUMBER</th>
                <th>FIRSTNAME</th>
                <th>LASTNAME</th>
                <th>ID NO</th>
                <th>KRA PIN</th>
                <th>SHIF NO</th>
                <th>CONTRIBUTION AMOUNT ({{ $currency }})</th>
                <th>PHONE</th>
                @elseif($column === 'nssf')
                <th>PAYROLL NUMBER</th>
                <th>SURNAME</th>
                <th>OTHER NAMES</th>
                <th>ID NO</th>
                <th>KRA PIN</th>
                <th>NSSF NO</th>
                <th>GROSS PAY ({{ $currency }})</th>
                <th>VOLUNTARY</th>
                @elseif($column === 'housing_levy')
                <th>EMP NO</th>
                <th>FULL NAME</th>
                <th>TAX_NO</th>
                <th>HOUSE_LEVY AMOUNT ({{ $currency }})</th>
                @else
                <th>Name</th>
                <th>Code</th>
                <th>KRA PIN</th>
                <th>Basic Salary ({{ $currency }})</th>
                <th>Gross Pay ({{ $currency }})</th>
                <th>Net Pay ({{ $currency }})</th>
                <th>{{ ucwords(str_replace('_', ' ', $column)) }} ({{ $currency }})</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                @if($column === 'paye')

                <td>{{ $row[0] ?? 'N/A' }}</td>
                <td>{{ $row[1] ?? 'N/A' }}</td>
                <td>{{ $row[2] ?? 'N/A' }}</td>
                <td>{{ $row[3] ?? 'N/A' }}</td>
                <td>{{ is_numeric($row[6] ?? 0) ? number_format($row[6], 2) : ($row[6] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[15] ?? 0) ? number_format($row[15], 2) : ($row[15] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[14] ?? 0) ? number_format($row[14], 2) : ($row[14] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[19] ?? 0) ? number_format($row[19], 2) : ($row[19] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[21] ?? 0) ? number_format($row[21], 2) : ($row[21] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[24] ?? 0) ? number_format($row[24], 2) : ($row[24] ?? 'N/A') }}</td>
                @elseif($column === 'shif')
                <td>{{ $row[0] ?? 'N/A' }}</td>
                <td>{{ $row[1] ?? 'N/A' }}</td>
                <td>{{ $row[2] ?? 'N/A' }}</td>
                <td>{{ $row[3] ?? 'N/A' }}</td>
                <td>{{ $row[4] ?? 'N/A' }}</td>
                <td>{{ $row[5] ?? 'N/A' }}</td>
                <td>{{ is_numeric($row[6] ?? 0) ? number_format($row[6], 2) : ($row[6] ?? 'N/A') }}</td>
                <td>{{ $row[7] ?? 'N/A' }}</td>
                @elseif($column === 'nssf')
                <td>{{ $row[0] ?? 'N/A' }}</td>
                <td>{{ $row[1] ?? 'N/A' }}</td>
                <td>{{ $row[2] ?? 'N/A' }}</td>
                <td>{{ $row[3] ?? 'N/A' }}</td>
                <td>{{ $row[4] ?? 'N/A' }}</td>
                <td>{{ $row[5] ?? 'N/A' }}</td>
                <td>{{ is_numeric($row[6] ?? 0) ? number_format($row[6], 2) : ($row[6] ?? 'N/A') }}</td>
                <td>{{ $row[7] ?? '' }}</td>
                @elseif($column === 'housing_levy')
                <td>{{ $row[0] ?? 'N/A' }}</td>
                <td>{{ $row[1] ?? 'N/A' }}</td>
                <td>{{ $row[2] ?? 'N/A' }}</td>
                <td>{{ is_numeric($row[3] ?? 0) ? number_format($row[3], 2) : ($row[3] ?? 'N/A') }}</td>
                @else

                <td>{{ $row[0] ?? 'N/A' }}</td>
                <td>{{ $row[1] ?? 'N/A' }}</td>
                <td>{{ $row[2] ?? 'N/A' }}</td>
                <td>{{ is_numeric($row[3] ?? 0) ? number_format($row[3], 2) : ($row[3] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[4] ?? 0) ? number_format($row[4], 2) : ($row[4] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[5] ?? 0) ? number_format($row[5], 2) : ($row[5] ?? 'N/A') }}</td>
                <td>{{ is_numeric($row[6] ?? 0) ? number_format($row[6], 2) : ($row[6] ?? 'N/A') }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p class="text-muted">Generated on: {{ now()->format('F d, Y H:i:s') }}</p>
        <p class="text-muted">For official use only.</p>
    </div>
</body>

</html>
