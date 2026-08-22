@extends('components.reports.layout')

@section('content')
<table class="table" style="margin-bottom:16px;">
    <tbody>
        <tr>
            <td style="width:25%;"><strong>Employee</strong></td>
            <td>{{ optional($checklist->employee->user)->name ?? 'N/A' }}</td>
            <td style="width:25%;"><strong>Department</strong></td>
            <td>{{ optional($checklist->employee->department)->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Initiated</strong></td>
            <td>{{ optional($checklist->initiated_at)->format('d M Y') }}</td>
            <td><strong>Reason</strong></td>
            <td>{{ optional($checklist->contractAction)->reason ?? '—' }}</td>
        </tr>
    </tbody>
</table>

<table class="table">
    <thead>
        <tr>
            <th>Task</th>
            <th class="center">Done</th>
            <th>Completed By</th>
            <th>Completed At</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($checklist->tasks as $task)
            <tr>
                <td>{{ $task->name }}</td>
                <td class="center">{{ $task->is_done ? 'Yes' : 'No' }}</td>
                <td>{{ optional($task->completedBy)->name ?? '—' }}</td>
                <td>{{ optional($task->completed_at)->format('d M Y') ?? '—' }}</td>
                <td>{{ $task->notes ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="no-data center">No tasks on this checklist.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:40px; display:flex; gap:60px;">
    <div style="flex:1; border-top:1px solid #1a202c; padding-top:6px;">HR Signature</div>
    <div style="flex:1; border-top:1px solid #1a202c; padding-top:6px;">Employee Signature</div>
</div>
@endsection
