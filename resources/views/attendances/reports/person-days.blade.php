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
    tr.employee-header td {
        background-color: #f8fafc;
        font-weight: 600;
        padding: 5px 6px;
        border-top: 1px solid #1a202c;
    }
    tr.totals-row td {
        font-weight: 700;
        background-color: #f1f5f9;
        border-top: 1px solid #1a202c;
        border-bottom: 2px solid #1a202c;
    }
</style>
@endsection

@section('content')
@if(!empty($error))
    <p class="no-data">{{ $error }}</p>
@elseif($departments->isEmpty() || $departments->every(fn ($employees) => $employees->isEmpty()))
    <p class="no-data">No {{ $viewKey === 'overtime' ? 'overtime' : 'lateness' }} records found for the selected filters.</p>
@else
    @php
        $point = function ($row) {
            if ($row->punch_latitude && $row->punch_longitude) {
                return number_format($row->punch_latitude, 5) . ', ' . number_format($row->punch_longitude, 5);
            }
            return $row->device_mac ?: '—';
        };
@endphp
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>{{ $viewKey === 'overtime' ? 'Expected Clock Out' : 'Expected Clock In' }}</th>
            <th>{{ $viewKey === 'overtime' ? 'Clock Out' : 'Clock In' }}</th>
            <th>{{ $viewKey === 'overtime' ? 'Overtime' : 'Late' }}</th>
            <th>Clock-In Point</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($departments as $departmentName => $employees)
            @continue($employees->isEmpty())
            <tr class="dept-section-header">
                <td colspan="5">{{ $departmentName }} &mdash; {{ $employees->count() }} employee{{ $employees->count() === 1 ? '' : 's' }}</td>
            </tr>
            @foreach ($employees as $entry)
                <tr class="employee-header">
                    <td colspan="5">{{ optional(optional($entry['employee'])->user)->name ?? 'N/A' }}</td>
                </tr>
                @foreach ($entry['days'] as $row)
                    <tr>
                        <td class="center">{{ optional($row->date)->format('d M Y') }}</td>
                        <td class="center">
                            {{ $viewKey === 'overtime'
                                ? (optional($row->expected_clock_out)->format('H:i') ?? '—')
                                : (optional($row->expected_clock_in)->format('H:i') ?? '—') }}
                        </td>
                        <td class="center">
                            {{ $viewKey === 'overtime'
                                ? (optional($row->clock_out)->format('H:i') ?? '—')
                                : (optional($row->clock_in)->format('H:i') ?? '—') }}
                        </td>
                        <td class="center">
                            {{ $viewKey === 'overtime'
                                ? \App\Support\TimeFmt::hoursToHm($row->overtime_hours ?? 0)
                                : round($row->late_minutes ?? 0) . 'm' }}
                        </td>
                        <td class="center">{{ $point($row) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="3" class="center">Total for {{ optional(optional($entry['employee'])->user)->name ?? 'N/A' }}</td>
                    <td class="center">
                        {{ $viewKey === 'overtime'
                            ? \App\Support\TimeFmt::hoursToTotalLabel($entry['total_overtime_hours'])
                            : \App\Support\TimeFmt::hoursToTotalLabel($entry['total_late_minutes'] / 60) }}
                    </td>
                    <td></td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
@endif
@endsection
