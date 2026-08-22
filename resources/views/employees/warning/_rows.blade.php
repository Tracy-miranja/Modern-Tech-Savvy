<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Employee</th>
                <th>Stage</th>
                <th>Issue Date</th>
                <th>Reason</th>
                <th>Issued By</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warnings as $warning)
            <tr>
                <td>{{ $warning->employee && $warning->employee->user ? $warning->employee->user->name : 'N/A' }}</td>
                <td>{{ $warning->stageType->name ?? '—' }}</td>
                <td>{{ $warning->issue_date->format('M d, Y') }}</td>
                <td>{{ $warning->reason }}</td>
                <td>{{ $warning->issuedBy->name ?? 'N/A' }}</td>
                <td>
                    <span class="badge {{ $warning->status === 'active' ? 'bg-warning' : 'bg-success' }} text-dark">
                        {{ ucfirst($warning->status) }}
                    </span>
                </td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('business.employees.warning.show', [$currentBusiness->slug, $warning->id]) }}">
                        <i class="fa fa-eye"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-warning" data-warning="{{ $warning->id }}" onclick="editWarning(this)">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-warning="{{ $warning->id }}" onclick="deleteWarning(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
