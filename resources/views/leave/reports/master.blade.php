@extends('components.reports.layout')

@section('content')
@if (!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Leave Type</th>
                <th>Entitled</th>
                <th>Carryover</th>
                <th>Accrued</th>
                <th>Adjustment</th>
                <th>Total</th>
                <th>Used</th>
                <th>Pending</th>
                <th>Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ optional(optional($row->employee)->user)->name ?? 'N/A' }}</td>
                    <td>{{ optional(optional($row->employee)->department)->name ?? '—' }}</td>
                    <td>{{ optional($row->leaveType)->name ?? '—' }}</td>
                    <td class="center">{{ $row->entitled_days }}</td>
                    <td class="center">{{ $row->carryover_days }}</td>
                    <td class="center">{{ $row->accrued_days }}</td>
                    <td class="center">{{ $row->adjustment_days }}</td>
                    <td class="center">{{ $row->total_days }}</td>
                    <td class="center">{{ $row->days_taken }}</td>
                    <td class="center">{{ $row->days_pending }}</td>
                    <td class="center">{{ $row->days_remaining }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="no-data center">No entitlements match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
@endsection
