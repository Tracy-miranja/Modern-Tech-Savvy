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
                <th>KPI Score</th>
                <th>OKR Score</th>
                <th>Competency Score</th>
                <th>Overall Score</th>
                <th>Grade Band</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ optional($row['employee']->user)->name ?? 'N/A' }}</td>
                    <td>{{ optional($row['employee']->department)->name ?? '—' }}</td>
                    <td class="center">{{ $row['kpi_score'] }}%</td>
                    <td class="center">{{ $row['okr_score'] }}%</td>
                    <td class="center">{{ $row['competency_score'] }}%</td>
                    <td class="center">{{ $row['overall_score'] }}%</td>
                    <td class="center" style="text-transform:capitalize;">{{ $row['grade_band'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="no-data center">No employees match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
@endsection
