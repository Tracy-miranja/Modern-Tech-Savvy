@forelse ($warnings as $warning)
<div class="col-lg-3 col-md-4 col-sm-6 mb-4">
    <div class="card shadow-sm border-0 rounded-3 h-100 warning-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title fw-semibold text-dark mb-0">
                    {{ $warning->employee && $warning->employee->user ? $warning->employee->user->name : 'N/A' }}
                </h5>
                <span class="badge {{ $warning->status === 'active' ? 'bg-warning' : 'bg-success' }} text-dark">
                    {{ ucfirst($warning->status) }}
                </span>
            </div>
            <ul class="list-unstyled text-muted small">
                <li><strong>Issue Date:</strong> {{ $warning->issue_date->format('M d, Y') }}</li>
                <li><strong>Reason:</strong> {{ $warning->reason }}</li>
                <li><strong>Description:</strong> {{ $warning->description ?? 'N/A' }}</li>
                <li><strong>Issued By:</strong> {{ $warning->issuedBy->name ?? 'N/A' }}</li>
            </ul>
        </div>
        <div class="card-footer bg-transparent border-top-0 pt-0">
            <div class="d-flex justify-content-end gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('business.employees.warning.show', [$currentBusiness->slug, $warning->id]) }}">
                    <i class="fa fa-eye"></i> View
                </a>
                <button class="btn btn-sm btn-outline-warning" data-warning="{{ $warning->id }}"
                    onclick="editWarning(this)">
                    <i class="fa fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger" data-warning="{{ $warning->id }}"
                    onclick="deleteWarning(this)">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@empty
<div class="col-12">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body text-center py-5">
            <i class="fa fa-info-circle text-muted fa-2x mb-3"></i>
            <p class="text-muted mb-0">No warnings have been issued yet.</p>
        </div>
    </div>
</div>
@endforelse

