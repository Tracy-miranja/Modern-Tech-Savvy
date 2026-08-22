<div class="mb-2">
    @if ($currentPeriod)
        <span class="badge bg-info-subtle text-info-emphasis">
            <i class="bi bi-calendar-range me-1"></i> Current open period: {{ $currentPeriod->name }}
            ({{ $currentPeriod->start_date->format('jS M Y') }} &ndash; {{ $currentPeriod->end_date->format('jS M Y') }})
        </span>
    @else
        <span class="badge bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-exclamation-triangle me-1"></i> No open leave period configured - showing policies effective today.
        </span>
    @endif
</div>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Leave Type</th>
                <th>Scope</th>
                <th>Default Days</th>
                <th>Accrual</th>
                <th>Carryover</th>
                <th>Carryover Valid For</th>
                <th>Min Interval</th>
                <th>Effective</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($policies as $policy)
                <tr>
                    <td>{{ optional($policy->leaveType)->name ?? '—' }}</td>
                    <td>
                        {{ optional($policy->department)->name ?? 'All Departments' }} /
                        {{ optional($policy->jobCategory)->name ?? 'All Job Categories' }}
                        <div class="small text-muted text-capitalize">{{ $policy->gender_applicable ?? 'all' }}</div>
                    </td>
                    <td>{{ $policy->default_days }}</td>
                    <td>{{ $policy->accrual_amount }} / {{ $policy->accrual_frequency }}</td>
                    <td>
                        @if ($policy->carryover_type === 'fixed')
                            {{ $policy->carryover_value }} days
                        @elseif ($policy->carryover_type === 'percent')
                            {{ $policy->carryover_value }}%
                        @else
                            Full
                        @endif
                        <div class="small text-muted">cap {{ $policy->max_carryover_days }}</div>
                    </td>
                    <td>{{ $policy->carryover_expiry_months ? $policy->carryover_expiry_months . ' month(s)' : 'Never' }}</td>
                    <td>{{ $policy->min_interval_days ? $policy->min_interval_days . ' day(s)' : '—' }}</td>
                    <td>
                        {{ optional($policy->effective_date)->format('jS M Y') }}
                        @if ($policy->end_date)
                            &ndash; {{ $policy->end_date->format('jS M Y') }}
                        @endif
                    </td>
                    <td>
                        @if ($policy->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">No leave policies configured yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
