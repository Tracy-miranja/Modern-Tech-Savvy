<div class="table-responsive">
<table class="table table-striped table-hover" id="mandatoryLeavePeriodsTable">
    <thead>
        <tr>
            <th>Name</th>
            <th>Leave Type</th>
            <th>Dates</th>
            <th>Scope</th>
            <th>Employees Affected</th>
            <th>Total Days Deducted</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($periods as $period)
            <tr>
                <td>{{ $period->name }}</td>
                <td>{{ $period->leaveType->name ?? '—' }}</td>
                <td>{{ $period->start_date->format('jS M Y') }} - {{ $period->end_date->format('jS M Y') }}</td>
                <td>
                    @if ($period->scope_type === 'organization')
                        <span class="badge bg-primary">Whole Organization</span>
                    @elseif ($period->scope_type === 'department')
                        <span class="badge bg-info">{{ count($period->scope_ids ?? []) }} Department(s)</span>
                    @else
                        <span class="badge bg-warning">{{ count($period->scope_ids ?? []) }} Location(s)</span>
                    @endif
                </td>
                <td>{{ $period->deductions_count }}</td>
                <td>{{ number_format($period->deductions_sum_days_deducted ?? 0, 2) }}</td>
                <td>
                    <button onclick="editMandatoryLeavePeriod(this)" data-period="{{ $period->slug }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button onclick="deleteMandatoryLeavePeriod(this)" data-period="{{ $period->slug }}" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>

@if ($periods->isEmpty())
    <p class="text-muted text-center my-3">No company-mandated leave days set up yet.</p>
@endif
