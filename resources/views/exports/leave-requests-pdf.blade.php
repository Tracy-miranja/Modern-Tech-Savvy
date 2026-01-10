{{-- resources/views/exports/leave-requests-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Requests Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #4472C4;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #4472C4;
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .header .subtitle {
            color: #666;
            font-size: 11px;
        }
        .metadata {
            margin-bottom: 15px;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
        }
        .metadata table {
            width: 100%;
        }
        .metadata td {
            padding: 3px 0;
        }
        .metadata .label {
            font-weight: bold;
            width: 150px;
            color: #333;
        }
        .filters {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .filters h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #856404;
        }
        .filters ul {
            margin: 0;
            padding-left: 20px;
        }
        .filters li {
            margin: 3px 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th {
            background-color: #4472C4;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #2d5aa0;
        }
        table.data-table td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background-color: #28a745; color: white; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-danger  { background-color: #dc3545; color: white; }
        .badge-info    { background-color: #17a2b8; color: white; }
        .badge-secondary { background-color: #6c757d; color: white; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #4472C4;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .summary {
            background-color: #e7f3ff;
            padding: 10px;
            border-left: 4px solid #4472C4;
            margin-top: 15px;
        }
        .summary table {
            width: 100%;
        }
        .summary td {
            padding: 3px 0;
        }
        .summary .label {
            font-weight: bold;
            width: 180px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ strtoupper($businessName ?? 'LEAVE REQUESTS REPORT') }}</h1>
        <div class="subtitle">
            LEAVE REQUESTS REPORT — Status: {{ ucfirst($status) }}
        </div>
    </div>

    <div class="metadata">
        <table>
            <tr>
                <td class="label">Business:</td>
                <td>{{ $businessName ?? '-' }}</td>
                <td class="label">Generated:</td>
                <td>{{ $generatedAt }}</td>
            </tr>
            <tr>
                <td class="label">Report Type:</td>
                <td>{{ ucfirst($status) }} Leave Requests</td>
                <td class="label">Total Records:</td>
                <td>{{ $totalRecords }}</td>
            </tr>
        </table>
    </div>

    @php
        $filters = $filters ?? [];
    @endphp

    @if(!empty(array_filter($filters)))
    <div class="filters">
        <h3>Applied Filters:</h3>
        <ul>
            @if(!empty($filters['leave_type']))
                <li><strong>Leave Type ID:</strong> {{ $filters['leave_type'] }}</li>
            @endif
            @if(!empty($filters['employee']))
                <li><strong>Employee ID:</strong> {{ $filters['employee'] }}</li>
            @endif
            @if(!empty($filters['department']))
                <li><strong>Department ID:</strong> {{ $filters['department'] }}</li>
            @endif
            @if(!empty($filters['job_category']))
                <li><strong>Job Category ID:</strong> {{ $filters['job_category'] }}</li>
            @endif
            @if(!empty($filters['start_date']))
                <li><strong>From Date:</strong> {{ $filters['start_date'] }}</li>
            @endif
            @if(!empty($filters['end_date']))
                <li><strong>To Date:</strong> {{ $filters['end_date'] }}</li>
            @endif
            @if(!empty($filters['days_range']))
                <li><strong>Days Range:</strong> {{ $filters['days_range'] }}</li>
            @endif
            @if(!empty($filters['approval_status']))
                <li><strong>Approval Status:</strong> {{ $filters['approval_status'] }}</li>
            @endif
            @if(!empty($filters['documentation']))
                <li><strong>Documentation:</strong> {{ $filters['documentation'] }}</li>
            @endif
            @if(!empty($filters['tentative']))
                <li><strong>Tentative:</strong> {{ $filters['tentative'] }}</li>
            @endif
        </ul>
    </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Ref. No.</th>
                <th style="width: 15%;">Employee</th>
                <th style="width: 12%;">Leave Type</th>
                <th style="width: 10%;">Start Date</th>
                <th style="width: 7%;">Days</th>
                <th style="width: 10%;">End Date</th>
                <th style="width: 15%;">Status</th>
                <th style="width: 21%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                @php
                    $ref        = strip_tags($item['ref']        ?? '');
                    $employee   = strip_tags($item['employee']   ?? '');
                    $leaveType  = strip_tags($item['leave_type'] ?? '');
                    $startDate  = strip_tags($item['start_date'] ?? '');
                    $days       = strip_tags($item['days']       ?? '');
                    $endDate    = strip_tags($item['end_date']   ?? '');
                    $statusText = strip_tags($item['status']     ?? '');

                    $badgeClass = 'badge-secondary';
                    if (str_contains($statusText, 'Approved') || str_contains($statusText, 'Complete')) {
                        $badgeClass = 'badge-success';
                    } elseif (str_contains($statusText, 'Awaiting')) {
                        $badgeClass = 'badge-info';
                    } elseif (str_contains($statusText, 'Under Review')) {
                        $badgeClass = 'badge-warning';
                    } elseif (str_contains($statusText, 'Rejected')) {
                        $badgeClass = 'badge-danger';
                    }
                @endphp
                <tr>
                    <td>{{ $ref }}</td>
                    <td>{{ $employee }}</td>
                    <td>{{ $leaveType }}</td>
                    <td>{{ $startDate }}</td>
                    <td style="text-align: center;">{{ $days }}</td>
                    <td>{{ $endDate }}</td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                    </td>
                    <td>&nbsp;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #999;">
                        No leave requests found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td class="label">Total Records in Report:</td>
                <td><strong>{{ $totalRecords }}</strong></td>
            </tr>
            <tr>
                <td class="label">Total Days Requested:</td>
                <td><strong>{{ $totalDays }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>Generated on {{ $generatedAt }} | {{ config('app.name') }}</p>
    </div>
</body>
</html>
