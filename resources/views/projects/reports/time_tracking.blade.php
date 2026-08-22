@extends('components.reports.layout')

@section('content')
@if (!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Project</th>
                <th>Task</th>
                <th>Hours</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="center">{{ optional($row->date)->format('jS M Y') }}</td>
                    <td>{{ optional($row->employee->user)->name ?? 'N/A' }}</td>
                    <td>{{ optional($row->employee->department)->name ?? '—' }}</td>
                    <td>{{ optional($row->project)->name ?? '—' }}</td>
                    <td>{{ optional($row->task)->title ?? '—' }}</td>
                    <td class="center">{{ $row->hours }}</td>
                    <td>{{ $row->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="no-data center">No time entries match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
@endsection
