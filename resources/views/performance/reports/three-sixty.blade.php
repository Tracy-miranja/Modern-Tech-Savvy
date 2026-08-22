@extends('components.reports.layout')

@section('content')
@if (!empty($error))
    <p class="no-data">{{ $error }}</p>
@else
    <table class="table" style="margin-bottom:16px;">
        <tbody>
            <tr>
                <td style="width:25%;"><strong>Employee</strong></td>
                <td>{{ optional($employee->user)->name ?? 'N/A' }}</td>
                <td style="width:25%;"><strong>Department</strong></td>
                <td>{{ optional($employee->department)->name ?? '—' }}</td>
            </tr>
            <tr>
                <td><strong>KPI Score</strong></td>
                <td>{{ $scoreRow['kpi_score'] }}%</td>
                <td><strong>OKR Score</strong></td>
                <td>{{ $scoreRow['okr_score'] }}%</td>
            </tr>
            <tr>
                <td><strong>Competency Score</strong></td>
                <td>{{ $scoreRow['competency_score'] }}%</td>
                <td><strong>Overall Score</strong></td>
                <td>{{ $scoreRow['overall_score'] }}% ({{ $scoreRow['grade_band'] ?? '—' }})</td>
            </tr>
        </tbody>
    </table>

    <h4 style="font-size:12pt; margin: 16px 0 6px;">Objectives</h4>
    <table class="table" style="margin-bottom:16px;">
        <thead>
            <tr>
                <th>Title</th>
                <th>Scope</th>
                <th>Weight</th>
                <th>Progress</th>
                <th>Confidence</th>
                <th>Grade Band</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($objectives as $objective)
                <tr>
                    <td>{{ $objective->title }}</td>
                    <td class="center" style="text-transform:capitalize;">{{ $objective->scope }}</td>
                    <td class="center">{{ $objective->weight }}</td>
                    <td class="center">{{ $objective->progress }}%</td>
                    <td class="center" style="text-transform:capitalize;">{{ str_replace('_', ' ', $objective->confidence ?? '—') }}</td>
                    <td class="center" style="text-transform:capitalize;">{{ $objective->grade_band ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="no-data center">No objectives for this cycle.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h4 style="font-size:12pt; margin: 16px 0 6px;">360 Feedback</h4>
    @forelse ($feedback as $request)
        <div style="border:1px solid #1a202c; padding:8px; margin-bottom:10px;">
            <p style="margin:0 0 4px;">
                <strong>Reviewer:</strong> {{ optional($request->reviewer->user)->name ?? 'N/A' }}
                &nbsp;&nbsp;<strong>Status:</strong> <span style="text-transform:capitalize;">{{ $request->status }}</span>
            </p>
            @if ($request->response)
                @foreach ($questions as $key => $label)
                    <p style="margin:4px 0;"><strong>{{ $label }}</strong><br>{{ $request->response->answers[$key] ?? '—' }}</p>
                @endforeach
            @else
                <p class="no-data" style="margin:4px 0;">No response submitted yet.</p>
            @endif
        </div>
    @empty
        <p class="no-data">No 360 feedback requests for this cycle.</p>
    @endforelse
@endif
@endsection
