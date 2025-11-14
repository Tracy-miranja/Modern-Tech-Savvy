<!--  This is the employee Leave slip pdf template -->
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Leave {{ $leave->reference_number }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    .header { border-bottom: 2px solid #000; margin-bottom: 16px; padding-bottom: 8px; }
    .title { font-size: 18px; font-weight: bold; }
    .meta { margin-top: 4px; color: #555; }
    .section { margin-top: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
    th { background: #f7f7f7; text-align: left; }
    .small { font-size: 11px; color: #666; }
  </style>
</head>
<body>
  <div class="header">
    <div class="title">Approved Leave Certificate</div>
    <div class="meta">
      Business: {{ $business->name ?? $business->slug }} • Ref: {{ $leave->reference_number }} • Generated: {{ now()->format('Y-m-d H:i') }}
    </div>
  </div>

  <div class="section">
    <table>
      <tr>
        <th style="width: 30%">Employee</th>
        <td>{{ optional(optional($leave->employee)->user)->name ?? '—' }}</td>
      </tr>
      <tr>
        <th>Leave Type</th>
        <td>{{ optional($leave->leaveType)->name ?? '—' }}</td>
      </tr>
      <tr>
        <th>Start Date</th>
        <td>{{ optional($leave->start_date)->toDateString() }}</td>
      </tr>
      <tr>
        <th>End Date</th>
        <td>{{ optional($leave->end_date)->toDateString() }}</td>
      </tr>
      <tr>
        <th>Total Days</th>
        <td>{{ number_format((float)$leave->total_days, 2) }}</td>
      </tr>
      <tr>
        <th>Approved By</th>
        <td>
          {{ optional($leave->approvedBy)->name ?? ('User ID: '.$leave->approved_by) }}
          @if($leave->approved_at) • at {{ $leave->approved_at->format('Y-m-d H:i') }} @endif
        </td>
      </tr>
      @if($leave->reason)
      <tr>
        <th>Reason</th>
        <td>{{ $leave->reason }}</td>
      </tr>
      @endif
    </table>
  </div>

  @if(is_array($leave->revocation_history ?? null) && count($leave->revocation_history))
    <div class="section">
      <div style="font-weight:bold; margin-bottom:6px;">Revocation History</div>
      <table>
        <thead>
          <tr>
            <th>When</th>
            <th>By</th>
            <th>New End</th>
            <th>Return To Work</th>
            <th>Refunded</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
        @foreach($leave->revocation_history as $rev)
          <tr>
            <td>{{ \Carbon\Carbon::parse($rev['revoked_at'])->format('Y-m-d H:i') }}</td>
            <td>{{ $rev['revoked_by_name'] ?? ('#'.$rev['revoked_by']) }}</td>
            <td>{{ $rev['new_end_date'] ?? '—' }}</td>
            <td>{{ $rev['return_to_work_date'] ?? '—' }}</td>
            <td>{{ (float)($rev['refund_days'] ?? 0) }}</td>
            <td>{{ $rev['reason'] ?? '—' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="small">* Latest row represents the most recent shortening.</div>
    </div>
  @endif
</body>
</html>
