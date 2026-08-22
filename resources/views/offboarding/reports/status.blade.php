@extends('components.reports.layout')

@section('content')
<table class="table">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Initiated</th>
            <th>Status</th>
            <th>Tasks Complete</th>
            <th>Completed</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ optional(optional($row->employee)->user)->name ?? 'N/A' }}</td>
                <td>{{ optional(optional($row->employee)->department)->name ?? '—' }}</td>
                <td>{{ optional($row->initiated_at)->format('d M Y') }}</td>
                <td class="center" style="text-transform:capitalize;">{{ str_replace('_', ' ', $row->status) }}</td>
                <td class="center">{{ $row->progressPercent() }}%</td>
                <td>{{ optional($row->completed_at)->format('d M Y') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="no-data center">No offboarding checklists match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
