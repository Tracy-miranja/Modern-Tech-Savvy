<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Leave Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height:1.5; color:#222;">
    <h2 style="margin-bottom:8px;">New Leave Request Submitted</h2>

    <p>A new leave request has been submitted. Here are the details:</p>

    @php
        $employee    = $leaveRequest->employee;
        $user        = $employee?->user;
        $department  = $employee?->department?->name ?? 'N/A';

        // Remaining days for THIS employee + leave type (best-effort)
        $remainingDays = 'N/A';
        try {
            $entitlement = \App\Models\LeaveEntitlement::query()
                ->where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('business_id', $leaveRequest->business_id)
                ->orderByDesc('id')
                ->first();

            if ($entitlement) {
                $remainingDays = number_format((float) $entitlement->getRemainingDays(), 1);
            }
        } catch (\Throwable $e) {
            $remainingDays = 'N/A';
        }

        $appliedAt = optional($leaveRequest->created_at)->format('d M Y H:i');
    @endphp

    <ul>
        <li>
            <strong>Employee:</strong>
            {{ $user?->name ?? 'N/A' }} ({{ $user?->email ?? 'N/A' }})
        </li>
        <li><strong>Department:</strong> {{ $department }}</li>
        <li><strong>Leave Type:</strong> {{ $leaveRequest->leaveType->name ?? 'N/A' }}</li>
        <li><strong>Date of Application:</strong> {{ $appliedAt ?? 'N/A' }}</li>
        <li><strong>Start Date:</strong> {{ optional($leaveRequest->start_date)->format('d M Y') }}</li>
        <li><strong>End Date:</strong> {{ optional($leaveRequest->end_date)->format('d M Y') }}</li>
        <li><strong>Total Days:</strong> {{ $leaveRequest->total_days }}</li>
        <li><strong>Remaining Days (Current Balance):</strong> {{ $remainingDays }}</li>
        <li><strong>Reason:</strong> {{ $leaveRequest->reason ?? 'N/A' }}</li>

        @if($leaveRequest->attachment)
            <li>
                <strong>Attachment:</strong>
                <a href="{{ asset('storage/' . $leaveRequest->attachment) }}" target="_blank">Download</a>
            </li>
        @endif
    </ul>

    @isset($showUrl)
        <p style="margin:24px 0;">
            <a href="{{ $showUrl }}"
               style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px;">
                View Request
            </a>
        </p>
    @endisset

    <p style="color:#666;">Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>
