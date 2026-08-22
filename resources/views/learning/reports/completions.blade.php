@extends('components.reports.layout')

@section('content')
@if (!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Course</th>
                <th>Session</th>
                <th>Status</th>
                <th>Score</th>
                <th>Enrolled</th>
                <th>Completed</th>
                <th>Certificate</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ optional($row->employee->user)->name ?? 'N/A' }}</td>
                    <td>{{ optional($row->employee->department)->name ?? '—' }}</td>
                    <td>{{ optional($row->course)->title ?? '—' }}</td>
                    <td>{{ optional($row->session)->start_date?->format('jS M Y') ?? '—' }}</td>
                    <td class="center" style="text-transform:capitalize;">{{ str_replace('_', ' ', $row->status) }}</td>
                    <td class="center">{{ $row->score !== null ? $row->score . '%' : '—' }}</td>
                    <td class="center">{{ optional($row->enrolled_at)->format('jS M Y') ?? '—' }}</td>
                    <td class="center">{{ optional($row->completed_at)->format('jS M Y') ?? '—' }}</td>
                    <td class="center">
                        @if ($row->certificate_issued)
                            {{ $row->certificate_number ?? 'Issued' }}
                            @if ($row->certificate_expiry_date)
                                (exp. {{ $row->certificate_expiry_date->format('jS M Y') }})
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="no-data center">No enrollments match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
@endsection
