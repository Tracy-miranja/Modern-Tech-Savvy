<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Leads Report - {{ $business->company_name }}</title>
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
        width: 5%;
    }

    /* # */
    .table th:nth-child(2),
    .table td:nth-child(2) {
        width: 10%;
    }

    /* Name */
    .table th:nth-child(3),
    .table td:nth-child(3) {
        width: 15%;
    }

    /* Email */
    .table th:nth-child(4),
    .table td:nth-child(4) {
        width: 10%;
    }

    /* Phone */
    .table th:nth-child(5),
    .table td:nth-child(5) {
        width: 10%;
    }

    /* Country */
    .table th:nth-child(6),
    .table td:nth-child(6) {
        width: 10%;
    }

    /* Status */
    .table th:nth-child(7),
    .table td:nth-child(7) {
        width: 10%;
    }

    /* Label */
    .table th:nth-child(8),
    .table td:nth-child(8) {
        width: 10%;
    }

    /* Campaign */
    .table th:nth-child(9),
    .table td:nth-child(9) {
        width: 10%;
    }

    /* Source */
    .table th:nth-child(10),
    .table td:nth-child(10) {
        width: 5%;
    }

    /* Contact Submission ID */
    .table th:nth-child(11),
    .table td:nth-child(11) {
        width: 15%;
    }

    /* Survey Responses */
    .table th:nth-child(12),
    .table td:nth-child(12) {
        width: 10%;
    }

    /* Created At */

    .footer {
        margin-top: 15px;
        border-top: 2px solid #1a202c;
        padding-top: 8px;
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
            $filePath = public_path(parse_url($logoUrl, PHP_URL_PATH));
            if (is_file($filePath)) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
            }
@endphp

            @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="{{ $business->company_name }} Logo" class="logo">
            @else
            <div class="logo-placeholder">{{ strtoupper(substr($business->company_name ?? 'Company', 0, 1)) }}</div>
            @endif
            <h1>{{ $business->company_name ?? 'Default Company Name' }}</h1>
            <p class="text-muted">{{ $business->physical_address ?? 'Default Address' }}</p>
            <p class="text-muted">Phone: {{ $business->phone ?? 'N/A' }}</p>
            <p class="text-muted">Email: {{ $business->user->email ?? 'info@company.com' }}</p>
        </div>
        <div class="right">
            <h2>Leads Report</h2>
            <p class="text-muted">Business: {{ $business->company_name }}</p>
            <p class="text-muted">Generated: {{ now()->format('F d, Y') }}</p>
            <p class="text-muted">Total Leads: {{ $leads->count() }}</p>
        </div>
    </div>

    @if($leads->isEmpty())
    <p>No leads available for this business.</p>
    @else
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Country</th>
                <th>Status</th>
                <th>Label</th>
                <th>Campaign</th>
                <th>Source</th>
                <th>Contact Submission ID</th>
                <th>Survey Responses</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leads as $lead)
            <tr>
                <td>{{ $lead->id }}</td>
                <td>{{ $lead->name ?? 'Unknown' }}</td>
                <td>{{ $lead->email ?? 'N/A' }}</td>
                <td>{{ $lead->phone ?? 'N/A' }}</td>
                <td>{{ $lead->country ?? $lead->user?->country ?? 'N/A' }}</td>
                <td>{{ ucfirst($lead->status ?? 'N/A') }}</td>
                <td>{{ $lead->label ?? 'N/A' }}</td>
                <td>{{ $lead->campaign ? $lead->campaign->name : 'N/A' }}</td>
                <td>{{ $lead->source ?? 'Unknown' }}</td>
                <td>{{ $lead->contact_submission_id ?? 'N/A' }}</td>
                <td>
                    @if($lead->survey_responses)
                    @php
                    $responses = is_array($lead->survey_responses) ? collect($lead->survey_responses)->map(function
                    ($response) {
                    return "{$response['label']}: {$response['value']}";
                    })->implode('; ')
                    : json_encode($lead->survey_responses);
@endphp
                    {{ $responses }}
                    @else
                    N/A
                    @endif
                </td>
                <td>{{ $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="11" class="text-right"><strong>Total Leads:</strong></td>
                <td><strong>{{ $leads->count() }}</strong></td>
            </tr>
        </tfoot>
    </table>
    @endif

</body>

</html>