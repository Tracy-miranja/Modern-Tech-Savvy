@extends('components.reports.layout')

@section('content')
@if(!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
<table class="table">
    <thead>
        <tr>
            <th>Tag</th>
            <th>Name</th>
            <th>Category</th>
            <th>Status</th>
            <th>Condition</th>
            <th>Purchase Date</th>
            <th>Purchase Cost</th>
            <th>Currently Assigned To</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            @php
                $assignment = $row->currentAssignment;
                $assignedTo = $assignment && $assignment->employee
                    ? (optional($assignment->employee->user)->name ?? 'N/A')
                    : 'Unassigned';
                $assignedDept = $assignment && $assignment->employee
                    ? optional($assignment->employee->department)->name
                    : null;
            @endphp
            <tr>
                <td>{{ $row->asset_tag ?? '—' }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $row->category ?? '—' }}</td>
                <td class="center">{{ ucfirst($row->status ?? '—') }}</td>
                <td class="center">{{ $row->condition ?? '—' }}</td>
                <td class="center">{{ optional($row->purchase_date)->format('d M Y') ?? '—' }}</td>
                <td class="center">{{ $row->purchase_cost !== null ? number_format((float) $row->purchase_cost, 2) : '—' }}</td>
                <td>{{ $assignedTo }}{{ $assignedDept ? ' (' . $assignedDept . ')' : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="no-data center">No assets found for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endif
@endsection
