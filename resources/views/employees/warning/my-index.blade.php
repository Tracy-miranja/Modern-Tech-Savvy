<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $page }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse ($warnings as $warning)
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card shadow-sm border-0 rounded-3 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-secondary text-uppercase">{{ str_replace('_', ' ', $warning->case_type) }}</span>
                                            <span class="badge {{ $warning->status === 'active' ? 'bg-warning text-dark' : 'bg-success' }}">
                                                {{ ucfirst($warning->status) }}
                                            </span>
                                        </div>
                                        <p class="text-muted small mb-1"><strong>Issue Date:</strong> {{ $warning->issue_date->format('M d, Y') }}</p>
                                        <p class="text-muted small mb-1"><strong>Reason:</strong> {{ $warning->reason }}</p>
                                        <p class="text-muted small mb-0">
                                            <strong>Acknowledged:</strong>
                                            {{ $warning->acknowledged_at ? $warning->acknowledged_at->format('M d, Y') : 'Not yet' }}
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0 pt-0">
                                        <button type="button" class="btn btn-sm btn-outline-primary w-100"
                                            data-bs-toggle="modal" data-bs-target="#warningModal{{ $warning->id }}">
                                            <i class="bi bi-eye me-1"></i> View
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="warningModal{{ $warning->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ str_replace('_', ' ', ucfirst($warning->case_type)) }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Issue Date:</strong> {{ $warning->issue_date->format('M d, Y') }}</p>
                                            <p><strong>Severity:</strong> {{ ucfirst($warning->severity ?? 'N/A') }}</p>
                                            <p><strong>Reason:</strong> {{ $warning->reason }}</p>
                                            <p><strong>Description:</strong> {{ $warning->description ?? 'N/A' }}</p>
                                            <p><strong>Issued By:</strong> {{ optional($warning->issuedBy)->name ?? 'N/A' }}</p>
                                            <p><strong>Status:</strong> {{ ucfirst($warning->status) }}</p>
                                            @if ($warning->status === 'resolved' && $warning->resolution_notes)
                                                <p><strong>Resolution Notes:</strong> {{ $warning->resolution_notes }}</p>
                                            @endif
                                            @if ($warning->attachment)
                                                <p><a href="{{ asset('storage/' . $warning->attachment) }}" target="_blank">
                                                    <i class="bi bi-paperclip me-1"></i> View Attachment
                                                </a></p>
                                            @endif

                                            @if (optional($warning->stageType)->requires_response)
                                                <hr>
                                                <p class="fw-semibold mb-1">Show Cause Response</p>
                                                @if ($warning->response_due_at)
                                                    <p class="text-muted small mb-2">Due by {{ $warning->response_due_at->format('M d, Y') }}</p>
                                                @endif
                                                @if ($warning->employee_responded_at)
                                                    <p class="text-muted small mb-1">Submitted {{ $warning->employee_responded_at->format('M d, Y H:i') }}</p>
                                                    <div class="border rounded p-2 bg-light small">{{ $warning->employee_response }}</div>
                                                @else
                                                    <textarea class="form-control form-control-sm response-textarea" data-warning-id="{{ $warning->id }}" rows="3" placeholder="Enter your response…"></textarea>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            @if (optional($warning->stageType)->requires_response && !$warning->employee_responded_at)
                                                <button type="button" class="btn btn-outline-primary submit-response-btn" data-warning-id="{{ $warning->id }}">
                                                    <i class="bi bi-send me-1"></i> Submit Response
                                                </button>
                                            @endif
                                            @if (!$warning->acknowledged_at)
                                                <button type="button" class="btn btn-primary acknowledge-btn" data-warning-id="{{ $warning->id }}">
                                                    <i class="bi bi-check2 me-1"></i> Acknowledge
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="card shadow-sm border-0 rounded-3">
                                    <div class="card-body text-center py-5">
                                        <i class="bi bi-info-circle text-muted fs-2 mb-3 d-block"></i>
                                        <p class="text-muted mb-0">You have no disciplinary cases on record.</p>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.acknowledge-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const warningId = this.dataset.warningId;
                    fetch(`{{ url('myaccount/' . $business->slug . '/disciplinary') }}/${warningId}/acknowledge`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    }).then(() => window.location.reload());
                });
            });

            document.querySelectorAll('.submit-response-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const warningId = this.dataset.warningId;
                    const textarea = document.querySelector(`.response-textarea[data-warning-id="${warningId}"]`);
                    const response = textarea ? textarea.value.trim() : '';
                    if (!response) { if (window.toastr) toastr.error('Enter a response first.'); return; }

                    fetch(`{{ url('myaccount/' . $business->slug . '/disciplinary') }}/${warningId}/respond`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ employee_response: response }),
                    }).then(resp => resp.json()).then(payload => {
                        if (window.toastr) toastr.success(payload.message || 'Response submitted.');
                        window.location.reload();
                    }).catch(() => { if (window.toastr) toastr.error('Could not submit response.'); });
                });
            });
        </script>
    @endpush
</x-app-layout>
