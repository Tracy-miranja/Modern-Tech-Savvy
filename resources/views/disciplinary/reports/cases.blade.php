@extends('components.reports.layout')

@section('content')
<table class="table">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Stage</th>
            <th>Severity</th>
            <th>Issue Date</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Issued By</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ optional(optional($row->employee)->user)->name ?? 'N/A' }}</td>
                <td>{{ optional(optional($row->employee)->department)->name ?? '—' }}</td>
                <td>{{ optional($row->stageType)->name ?? ucwords(str_replace('_', ' ', $row->case_type)) }}</td>
                <td class="center" style="text-transform:capitalize;">{{ $row->severity }}</td>
                <td>{{ optional($row->issue_date)->format('d M Y') }}</td>
                <td>{{ $row->reason }}</td>
                <td class="center" style="text-transform:capitalize;">{{ $row->status }}</td>
                <td>{{ optional($row->issuedBy)->name ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="no-data center">No disciplinary cases match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
