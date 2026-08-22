@extends('components.reports.layout')

@section('content')
<table class="table">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            @foreach ($leaveTypeNames as $typeName)
                <th>{{ $typeName }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ optional(optional($row['employee'])->user)->name ?? 'N/A' }}</td>
                <td>{{ optional(optional($row['employee'])->department)->name ?? '—' }}</td>
                @foreach ($leaveTypeNames as $typeName)
                    <td class="center">{{ $row['remaining'][$typeName] !== null ? rtrim(rtrim(number_format($row['remaining'][$typeName], 1), '0'), '.') : '—' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ 2 + count($leaveTypeNames) }}" class="no-data center">No entitlements match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
