@extends('components.reports.layout')

@section('styles')
<style>
    tr.dept-section-header td {
        background-color: #e2e8f0;
        color: #1a202c;
        font-weight: 700;
        font-size: 10.5pt;
        padding: 7px 6px;
        border-top: 2px solid #1a202c;
        border-bottom: 1px solid #1a202c;
        text-align: center;
    }
</style>
@endsection

@section('content')
@if(!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    @php
        $grouped = $rows->groupBy(fn ($row) => optional(optional($row->employee)->department)->name ?? 'No Department')
            ->sortKeys();
@endphp
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Regular Hours</th>
            <th>Overtime</th>
            <th>Late</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($grouped as $departmentName => $deptRows)
            <tr class="dept-section-header">
                <td colspan="8">{{ $departmentName }} &mdash; {{ $deptRows->count() }} record{{ $deptRows->count() === 1 ? '' : 's' }}</td>
            </tr>
            @foreach ($deptRows as $row)
                <tr>
                    <td class="center">{{ optional($row->date)->format('d M Y') }}</td>
                    <td>{{ optional(optional($row->employee)->user)->name ?? 'N/A' }}</td>
                    <td class="center">{{ optional($row->clock_in)->format('H:i') ?? '—' }}</td>
                    <td class="center">{{ optional($row->clock_out)->format('H:i') ?? '—' }}</td>
                    <td class="center">{{ \App\Support\TimeFmt::hoursToHm($row->regular_hours ?? 0) }}</td>
                    <td class="center">{{ \App\Support\TimeFmt::hoursToHm($row->overtime_hours ?? 0) }}</td>
                    <td class="center">{{ $row->late_minutes > 0 ? round($row->late_minutes) . 'm' : '—' }}</td>
                    <td class="center">{{ $row->is_on_leave ? 'On Leave' : ($row->is_absent ? 'Absent' : 'Present') }}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="8" class="no-data center">No attendance records found for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endif
@endsection
