@extends('components.reports.layout')

@section('content')
<table class="table">
    <thead>
        <tr>
            <th>Leave Type</th>
            <th>Request Count</th>
            <th>Total Days Taken (Approved)</th>
            <th>Average Duration (Approved)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ optional($row['leave_type'])->name ?? 'Unknown' }}</td>
                <td class="center">{{ $row['request_count'] }}</td>
                <td class="center">{{ $row['total_days'] }}</td>
                <td class="center">{{ $row['average_duration'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="no-data center">No leave requests match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
