@extends('components.reports.layout')

@section('content')
<table class="table">
    <thead>
        <tr>
            <th>Level</th>
            <th>Stage</th>
            <th>Issue Date</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Issued By</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($chain as $index => $case)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ optional($case->stageType)->name ?? ucwords(str_replace('_', ' ', $case->case_type)) }}</td>
                <td>{{ optional($case->issue_date)->format('d M Y') }}</td>
                <td>{{ $case->reason }}</td>
                <td class="center" style="text-transform:capitalize;">{{ $case->status }}</td>
                <td>{{ optional($case->issuedBy)->name ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="no-data center">No escalation chain to display.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
