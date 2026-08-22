@extends('components.reports.layout')

@section('content')
@if (!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    @if (isset($entitlements) && $entitlements->isNotEmpty())
        <table class="table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>Leave Period</th>
                    <th>Leave Type</th>
                    <th>Entitled</th>
                    <th>Taken</th>
                    <th>Pending</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entitlements as $entitlement)
                    <tr>
                        <td>{{ optional($entitlement->leavePeriod)->name ?? '—' }}</td>
                        <td>{{ optional($entitlement->leaveType)->name ?? '—' }}</td>
                        <td class="center">{{ $entitlement->total_days }}</td>
                        <td class="center">{{ $entitlement->days_taken }}</td>
                        <td class="center">{{ $entitlement->days_pending }}</td>
                        <td class="center">{{ $entitlement->days_remaining }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Leave Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Days</th>
                <th>Status</th>
                <th>Approved By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->reference_number }}</td>
                    <td>{{ optional(optional($row->employee)->user)->name ?? 'N/A' }}</td>
                    <td>{{ optional(optional($row->employee)->department)->name ?? '—' }}</td>
                    <td>{{ optional($row->leaveType)->name ?? '—' }}</td>
                    <td>{{ optional($row->start_date)->format('d M Y') }}</td>
                    <td>{{ optional($row->end_date)->format('d M Y') }}</td>
                    <td class="center">{{ $row->total_days }}</td>
                    <td class="center" style="text-transform:capitalize;">{{ $row->status }}</td>
                    <td>{{ optional($row->approvedBy)->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="no-data center">No leave requests match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
@endsection
