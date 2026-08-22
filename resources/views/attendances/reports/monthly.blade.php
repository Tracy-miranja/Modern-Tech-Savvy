@extends('components.reports.layout')

@section('content')
<table class="table">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Present Days</th>
            <th>Late Days</th>
            <th>Absent Days</th>
            <th>Expected Hours</th>
            <th>Actual Hours</th>
            <th>Variance</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>
                    {{ optional($row['employee']->user)->name ?? 'N/A' }}
                    @if($row['is_flexible'])
                        <span style="color:#6b7280; font-size:8pt;">(Flexible)</span>
                    @endif
                </td>
                <td>{{ optional($row['employee']->department)->name ?? '—' }}</td>
                <td class="center">{{ $row['present_days'] }}</td>
                <td class="center">{{ $row['late_days'] }}</td>
                <td class="center">{{ $row['is_flexible'] ? 'N/A' : $row['absent_days'] }}</td>
                <td class="center">{{ \App\Support\TimeFmt::hoursToTotalLabel($row['expected_hours']) }}</td>
                <td class="center">{{ \App\Support\TimeFmt::hoursToTotalLabel($row['actual_hours']) }}</td>
                <td class="center" style="{{ $row['variance_hours'] < 0 ? 'color:#b91c1c;' : 'color:#166534;' }}">
                    {{ $row['variance_hours'] >= 0 ? '+' : '' }}{{ \App\Support\TimeFmt::hoursToTotalLabel($row['variance_hours']) }}
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="no-data center">No employees match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
