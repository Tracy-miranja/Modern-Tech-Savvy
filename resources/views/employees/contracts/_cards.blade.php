<div id="contractActionsContainer" class="row g-4">
    @forelse ($contractActions as $contractAction)
    <div class="col-md-4 col-sm-6">
        <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title fw-semibold text-dark mb-0">
                        {{ optional($contractAction->employee)->user->name ?? 'N/A' }}
                    </h5>
                    <span
                        class="badge {{ $contractAction->status === 'active' ? 'bg-warning' : ($contractAction->status === 'sent' ? 'bg-info' : 'bg-success') }} text-dark">
                        {{ ucfirst($contractAction->status) }}
                    </span>
                </div>
                <ul class="list-unstyled text-muted small">
                    <li><strong>Action Type:</strong> {{ ucfirst($contractAction->action_type) }}</li>
                    <li><strong>Action Date:</strong> {{ $contractAction->action_date->format('M d, Y') }}</li>
                    <li><strong>Reason:</strong> {{ $contractAction->reason }}</li>
                    <li><strong>Description:</strong> {{ $contractAction->description ?? 'N/A' }}</li>
                    <li><strong>Issued By:</strong> {{ optional($contractAction->issuedBy)->name ?? 'N/A' }}</li>
                </ul>
            </div>
            @if(in_array($contractAction->action_type, ['termination', 'suspension']))
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-sm btn-outline-warning" data-contract-action="{{ $contractAction->id }}"
                        onclick="editContractAction(this)">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-contract-action="{{ $contractAction->id }}"
                        onclick="deleteContractAction(this)">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body text-center py-5">
                <i class="fa fa-info-circle text-muted fa-2x mb-3"></i>
                <p class="text-muted mb-0">No contract actions have been recorded yet.</p>
            </div>
        </div>
    </div>
    @endforelse
</div>