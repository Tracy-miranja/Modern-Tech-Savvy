<x-app-layout>
    @php
        $canEscalate = $warning->status === 'active' && $warning->suggestedNextStage() !== null;
    @endphp
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <a href="{{ route('business.employees.warning', $business->slug) }}" class="text-decoration-none small text-muted">
                            <i class="bi bi-arrow-left"></i> Back to Disciplinary Cases
                        </a>
                        <h3 class="fw-bold text-dark mb-0 mt-1">Case #{{ $warning->id }} - {{ optional($warning->employee->user)->name ?? 'N/A' }}</h3>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary btn-sm" target="_blank"
                            href="{{ route('business.disciplinary.reports.escalation-trail.download', [$business->slug, $warning->id]) }}">
                            <i class="bi bi-printer me-1"></i> Print Escalation Trail
                        </a>
                        @if ($canEscalate)
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#escalateModal">
                                <i class="bi bi-arrow-up-circle me-1"></i> Escalate to {{ ucwords(str_replace('_', ' ', $warning->suggestedNextStage())) }}
                            </button>
                        @endif
                        @if ($warning->status === 'active')
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#resolveModal">
                                <i class="bi bi-check2-circle me-1"></i> Resolve
                            </button>
                        @endif
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 rounded-3 mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-info text-dark">{{ optional($warning->stageType)->name ?? ucwords(str_replace('_', ' ', $warning->case_type)) }}</span>
                                    <span class="badge bg-secondary text-uppercase">{{ $warning->severity }}</span>
                                    <span class="badge {{ $warning->status === 'active' ? 'bg-warning text-dark' : 'bg-success' }}">{{ ucfirst($warning->status) }}</span>
                                    @if (optional($warning->stageType)->is_terminal)
                                        <span class="badge bg-dark">Terminal Stage</span>
                                    @endif
                                </div>
                                <p class="mb-1"><strong>Issue Date:</strong> {{ $warning->issue_date->format('M d, Y') }}</p>
                                <p class="mb-1"><strong>Department:</strong> {{ optional($warning->employee->department)->name ?? '—' }}</p>
                                <p class="mb-1"><strong>Reason:</strong> {{ $warning->reason }}</p>
                                <p class="mb-1"><strong>Description:</strong> {{ $warning->description ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Issued By:</strong> {{ optional($warning->issuedBy)->name ?? 'N/A' }}</p>
                                <p class="mb-1"><strong>Acknowledged:</strong> {{ $warning->acknowledged_at ? $warning->acknowledged_at->format('M d, Y H:i') : 'Not yet' }}</p>
                                @if ($warning->attachment)
                                    <p class="mb-1"><a href="{{ asset('storage/' . $warning->attachment) }}" target="_blank"><i class="bi bi-paperclip me-1"></i>View Attachment</a></p>
                                @endif
                                @if ($warning->status === 'resolved' && $warning->resolution_notes)
                                    <div class="alert alert-light border small mt-3 mb-0"><strong>Resolution:</strong> {{ $warning->resolution_notes }}</div>
                                @endif
                            </div>
                        </div>

                        @if (optional($warning->stageType)->requires_response)
                            <div class="card shadow-sm border-0 rounded-3 mb-3">
                                <div class="card-header bg-white fw-semibold">Show Cause Response</div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Due:</strong> {{ $warning->response_due_at ? $warning->response_due_at->format('M d, Y') : 'Not set' }}</p>
                                    @if ($warning->employee_responded_at)
                                        <p class="mb-1"><strong>Responded:</strong> {{ $warning->employee_responded_at->format('M d, Y H:i') }}</p>
                                        <div class="border rounded p-2 bg-light small">{{ $warning->employee_response }}</div>
                                    @else
                                        <p class="text-muted small mb-0">No response submitted yet.</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="card shadow-sm border-0 rounded-3 mb-3">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Investigations</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#investigationModal">
                                    <i class="bi bi-plus-circle me-1"></i> Record Investigation
                                </button>
                            </div>
                            <div class="card-body">
                                @forelse ($warning->investigations as $investigation)
                                    <div class="border-bottom pb-2 mb-2">
                                        <p class="mb-1 small"><strong>Investigator:</strong> {{ optional(optional($investigation->investigator)->user)->name ?? 'Unassigned' }}</p>
                                        <p class="mb-1 small"><strong>Started:</strong> {{ optional($investigation->started_at)->format('M d, Y') ?? '—' }} <strong class="ms-2">Concluded:</strong> {{ optional($investigation->concluded_at)->format('M d, Y') ?? '—' }}</p>
                                        <p class="mb-1 small"><strong>Outcome:</strong> {{ $investigation->outcome ?? '—' }}</p>
                                        <p class="mb-0 small">{{ $investigation->findings ?? 'No findings recorded yet.' }}</p>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No investigations recorded.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 rounded-3 mb-3">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Minutes</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#minutesModal">
                                    <i class="bi bi-plus-circle me-1"></i> Record Minutes
                                </button>
                            </div>
                            <div class="card-body">
                                @forelse ($warning->minutes as $minute)
                                    <div class="border-bottom pb-2 mb-2">
                                        <p class="mb-1 small"><strong>Meeting Date:</strong> {{ $minute->meeting_date->format('M d, Y') }}</p>
                                        <p class="mb-1 small"><strong>Attendees:</strong> {{ $minute->attendees ?? '—' }}</p>
                                        <p class="mb-0 small">{{ $minute->notes ?? 'No notes recorded.' }}</p>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No minutes recorded.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-white fw-semibold">Escalation Chain</div>
                            <div class="card-body">
                                @if ($warning->previousCase)
                                    <p class="small mb-2">
                                        <i class="bi bi-arrow-90deg-up me-1"></i>
                                        Escalated from
                                        <a href="{{ route('business.employees.warning.show', [$business->slug, $warning->previousCase->id]) }}">
                                            Case #{{ $warning->previousCase->id }} ({{ optional($warning->previousCase->stageType)->name ?? ucwords(str_replace('_',' ',$warning->previousCase->case_type)) }})
                                        </a>
                                    </p>
                                @endif
                                <p class="small mb-2"><strong>This case:</strong> {{ optional($warning->stageType)->name ?? ucwords(str_replace('_', ' ', $warning->case_type)) }} (level {{ $warning->escalation_level }})</p>
                                @forelse ($warning->nextCases as $nextCase)
                                    <p class="small mb-2">
                                        <i class="bi bi-arrow-90deg-down me-1"></i>
                                        Escalated to
                                        <a href="{{ route('business.employees.warning.show', [$business->slug, $nextCase->id]) }}">
                                            Case #{{ $nextCase->id }} ({{ optional($nextCase->stageType)->name ?? ucwords(str_replace('_',' ',$nextCase->case_type)) }})
                                        </a>
                                    </p>
                                @empty
                                    <p class="text-muted small mb-0">Not escalated further.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Escalate Modal -->
    <div class="modal fade" id="escalateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Escalate Case</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">This creates a new case at the next configured stage
                        (<strong>{{ $canEscalate ? ucwords(str_replace('_', ' ', $warning->suggestedNextStage())) : '—' }}</strong>)
                        and marks this one resolved-by-escalation.</p>
                    <div class="mb-3">
                        <label class="form-label small">Issue Date</label>
                        <input type="date" class="form-control" id="escalateIssueDate" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Reason</label>
                        <input type="text" class="form-control" id="escalateReason" value="{{ $warning->reason }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="submitEscalateBtn">Escalate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Investigation Modal -->
    <div class="modal fade" id="investigationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Investigation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Investigator</label>
                        <select class="form-select" id="investigatorSelect">
                            <option value="">— Unassigned —</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ optional($emp->user)->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Started</label>
                            <input type="date" class="form-control" id="investigationStarted">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Concluded</label>
                            <input type="date" class="form-control" id="investigationConcluded">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Outcome</label>
                        <input type="text" class="form-control" id="investigationOutcome" placeholder="e.g. Substantiated, Not substantiated">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Findings</label>
                        <textarea class="form-control" id="investigationFindings" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitInvestigationBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Minutes Modal -->
    <div class="modal fade" id="minutesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Minutes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Meeting Date</label>
                        <input type="date" class="form-control" id="minutesMeetingDate" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Attendees</label>
                        <input type="text" class="form-control" id="minutesAttendees" placeholder="Comma-separated names">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Notes</label>
                        <textarea class="form-control" id="minutesNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitMinutesBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resolve Modal -->
    <div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resolve Case</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Resolution Notes</label>
                        <textarea class="form-control" id="resolveNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submitResolveBtn">Mark Resolved</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const warningId = {{ $warning->id }};
        const escalateUrl = `/warning/${warningId}/escalate`;
        const investigationStoreUrl = `/warning/${warningId}/investigations/store`;
        const minutesStoreUrl = `/warning/${warningId}/minutes/store`;
        const resolveUrl = `/warning/${warningId}/update`;

        async function postJson(url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data || {}),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        document.getElementById('submitEscalateBtn').addEventListener('click', async function () {
            try {
                await postJson(escalateUrl, {
                    issue_date: document.getElementById('escalateIssueDate').value,
                    reason: document.getElementById('escalateReason').value,
                });
                if (window.toastr) toastr.success('Case escalated.');
                window.location.reload();
            } catch (e) {
                if (window.toastr) toastr.error(e.message || 'Could not escalate.');
            }
        });

        document.getElementById('submitInvestigationBtn').addEventListener('click', async function () {
            try {
                await postJson(investigationStoreUrl, {
                    investigator_id: document.getElementById('investigatorSelect').value || null,
                    started_at: document.getElementById('investigationStarted').value || null,
                    concluded_at: document.getElementById('investigationConcluded').value || null,
                    outcome: document.getElementById('investigationOutcome').value || null,
                    findings: document.getElementById('investigationFindings').value || null,
                });
                if (window.toastr) toastr.success('Investigation recorded.');
                window.location.reload();
            } catch (e) {
                if (window.toastr) toastr.error(e.message || 'Could not save investigation.');
            }
        });

        document.getElementById('submitMinutesBtn').addEventListener('click', async function () {
            try {
                await postJson(minutesStoreUrl, {
                    meeting_date: document.getElementById('minutesMeetingDate').value,
                    attendees: document.getElementById('minutesAttendees').value || null,
                    notes: document.getElementById('minutesNotes').value || null,
                });
                if (window.toastr) toastr.success('Minutes recorded.');
                window.location.reload();
            } catch (e) {
                if (window.toastr) toastr.error(e.message || 'Could not save minutes.');
            }
        });

        document.getElementById('submitResolveBtn').addEventListener('click', async function () {
            try {
                await postJson(resolveUrl, {
                    warning_id: warningId,
                    employee_id: {{ $warning->employee_id }},
                    case_type: @json($warning->case_type),
                    severity: @json($warning->severity),
                    issue_date: @json($warning->issue_date->toDateString()),
                    reason: @json($warning->reason),
                    description: @json($warning->description),
                    status: 'resolved',
                    resolution_notes: document.getElementById('resolveNotes').value || null,
                });
                if (window.toastr) toastr.success('Case marked resolved.');
                window.location.reload();
            } catch (e) {
                if (window.toastr) toastr.error(e.message || 'Could not resolve case.');
            }
        });
    })();
    </script>
    @endpush
</x-app-layout>
