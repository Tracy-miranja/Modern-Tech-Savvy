@extends('components.reports.layout')

@section('content')
@if (!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Project</th>
                <th>Assignee</th>
                <th>Department</th>
                <th>Category</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Due Date</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ optional($row->project)->name ?? '—' }}</td>
                    <td>{{ optional(optional($row->assignee)->user)->name ?? 'Unassigned' }}</td>
                    <td>{{ optional(optional($row->assignee)->department)->name ?? '—' }}</td>
                    <td>{{ optional($row->category)->name ?? '—' }}</td>
                    <td class="center">{{ optional($row->status)->name ?? '—' }}</td>
                    <td class="center" style="text-transform:capitalize;">{{ $row->priority }}</td>
                    <td class="center">{{ optional($row->due_date)->format('jS M Y') ?? '—' }}</td>
                    <td class="center">{{ optional($row->completed_at)->format('jS M Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="no-data center">No tasks match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
@endsection
