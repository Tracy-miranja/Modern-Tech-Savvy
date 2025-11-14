<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">Leave Requests</h5>

            @php
                $activeRole = session('active_role');
                $isEmployee = $activeRole === 'business-employee';
            @endphp
            
        </div>

        @if ($activeRole === 'business-admin')
            <a href="{{ route('business.leave.create', $currentBusiness->slug) }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Create Leave Request
            </a>
        @else
            <a href="{{ route('myaccount.leave.requests.create', $currentBusiness->slug) }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Request Leave
            </a>
        @endif
    </div>

    <div class="card-body">
        <table class="table table-bordered" style="width: 100%" id="{{ $status }}LeaveRequestsTable">
            <thead>
                <tr>
                    <th>Ref. No.</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>Days</th>
                    <th>End Date</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Attachment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($leaveRequests as $request)
                @php
                    $viewUrl = $isEmployee
                        ? route('myaccount.leave.show', ['business' => $currentBusiness->slug, 'leave' => $request->reference_number])
                        : route('business.leave.show', ['business' => $currentBusiness->slug, 'leave' => $request->reference_number]);

                    $requiredLevels     = (int) optional($request->leaveType)->approval_levels ?: 1;
                    $currentLevel       = (int) ($request->current_approval_level ?? 0);
                    $progressPercentage = $requiredLevels > 0 ? min(100, round(($currentLevel / $requiredLevels) * 100)) : 0;
                    if ($request->status === 'approved') { $progressPercentage = 100; }

                    $canApprove = $request->status === 'pending'
                        && method_exists($request, 'canUserApprove')
                        && $request->canUserApprove(auth()->user());

                    $authEmpId = auth()->user()->employee->id ?? null;
                    $isOwner   = $authEmpId && ((int)$authEmpId === (int)$request->employee_id);
                @endphp

                <tr>
                    <td class="fw-bold">
                        {{ $request->reference_number }}
                        @if($request->is_tentative)
                            <small class="badge bg-warning text-dark ms-1">Tentative</small>
                        @endif
                    </td>

                    <td>{{ optional(optional($request->employee)->user)->name ?? 'N/A' }}</td>

                    <td class="text-white text-center
                        @if (optional($request->leaveType)->name === 'Sick Leave') bg-danger
                        @elseif (optional($request->leaveType)->name === 'Annual Leave') bg-primary
                        @else bg-secondary @endif">
                        {{ optional($request->leaveType)->name ?? '—' }}
                    </td>

                    <td class="fw-bold text-primary">{{ optional($request->start_date)->format('M d, Y') }}</td>
                    <td>{{ number_format((float) $request->total_days, 1) }}</td>
                    <td class="fw-bold text-danger">{{ optional($request->end_date)->format('M d, Y') }}</td>

                    {{-- Progress --}}
                    <td>
                        @if($request->status === 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @elseif($request->status === 'approved')
                            <span class="badge bg-success">Complete</span>
                        @else
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 8px; min-width: 60px;">
                                    <div class="progress-bar
                                        @if($progressPercentage < 50) bg-warning
                                        @elseif($progressPercentage < 100) bg-info
                                        @else bg-success @endif"
                                        role="progressbar" style="width: {{ $progressPercentage }}%">
                                    </div>
                                </div>
                                <small class="text-muted">{{ $currentLevel }}/{{ $requiredLevels }}</small>
                            </div>
                            @if($request->requires_documentation && !$request->attachment)
                                <small class="text-warning d-block mt-1">
                                    <i class="fa-solid fa-exclamation-triangle me-1"></i> Docs Required
                                </small>
                            @endif
                        @endif
                    </td>

                    {{-- Status --}}
                    <td>
                        
                        @php
                            $hasRevocations = is_array($request->revocation_history ?? null) && count($request->revocation_history);
                        @endphp

                        @if ($request->status === 'approved')
                            @if($hasRevocations)
                                <span class="badge bg-secondary">
                                    <i class="fa-solid me-1 fa-rotate-left"></i> Shortened / Revoked
                                </span>
                            @else
                                <span class="badge bg-success">
                                    <i class="fa-solid me-1 fa-check-circle"></i> Approved
                                </span>
                            @endif
                        @elseif ($request->status === 'pending')
                            @if($canApprove)
                                <span class="badge bg-info">
                                    <i class="fa-solid me-1 fa-hourglass-half"></i> Awaiting Your Approval
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="fa-solid me-1 fa-clock"></i> Under Review
                                </span>
                            @endif
                        @elseif ($request->status === 'rejected')
                            <span class="badge bg-danger">
                                <i class="fa-solid me-1 fa-times-circle"></i> Rejected
                            </span>
                        @endif
                    </td>

                    {{-- Attachment (owner can "Upload to complete") --}}
                    <td>
                        @if($request->attachment)
                            <a href="{{ asset('storage/' . $request->attachment) }}"
                               class="btn btn-outline-info btn-sm" target="_blank" download
                               title="Download attachment">
                                <i class="fa-solid fa-download"></i>
                            </a>
                        @elseif($isOwner && $request->requires_documentation && $request->status !== 'rejected')
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    onclick="openUploadAttachmentModal_{{ $status }}('{{ $request->reference_number }}')">
                                <i class="fa-solid fa-upload"></i> Upload to complete
                            </button>
                        @elseif($request->requires_documentation)
                            <span class="text-warning">
                                <i class="fa-solid fa-exclamation-triangle"></i> Required
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ $viewUrl }}" class="btn btn-outline-primary btn-sm" title="View Details">
                                <i class="fa-solid fa-eye"></i>
                            </a>

                            {{-- Approvers (HOD/HR at correct level) --}}
                            @if ($canApprove)
                                <button type="button" onclick="manageLeave(this)"
                                        data-action="approve" data-leave="{{ $request->reference_number }}"
                                        class="btn btn-success btn-sm" title="Approve">
                                    <i class="fa-solid fa-check"></i>
                                </button>

                                <button type="button" onclick="manageLeave(this)"
                                        data-action="reject" data-leave="{{ $request->reference_number }}"
                                        class="btn btn-danger btn-sm" title="Reject">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            @endif

                            {{-- Owner can delete own pending request --}}
                            @if ($isOwner && $request->status === 'pending')
                                <button type="button" onclick="deleteLeave(this)"
                                        data-leave="{{ $request->reference_number }}"
                                        class="btn btn-outline-danger btn-sm" title="Delete Request">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        @if($leaveRequests->isEmpty())
            <div class="text-center py-4">
                <div class="text-muted">
                    <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                    <h5>No {{ ucfirst($status) }} Leave Requests</h5>
                    <p>There are currently no {{ $status }} leave requests to display.</p>
                </div>
            </div>
        @endif

        {{-- Upload Attachment Modal (unique per status tab) --}}
        <div class="modal fade" id="uploadAttachmentModal-{{ $status }}" tabindex="-1" aria-labelledby="uploadAttachmentLabel-{{ $status }}" aria-hidden="true">
            <div class="modal-dialog">
                <form id="uploadAttachmentForm-{{ $status }}" class="modal-content" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadAttachmentLabel-{{ $status }}">Upload Attachment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="reference_number" id="uploadRef-{{ $status }}" value="">
                        <div class="mb-3">
                            <label class="form-label">Select file</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.png,.doc,.docx" required>
                            <small class="text-muted">Accepted: PDF, JPG, PNG, DOC, DOCX (max 2MB)</small>
                        </div>
                        <div class="alert alert-info mb-0">
                            This will attach your document and notify the approver to continue the approval.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
            
        </div>

                    @if (in_array($activeRole, ['head-of-department','business-hr','business-admin','business-head', 'chief-of-staff'], true))
                <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm ms-2">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            @endif
    </div>
</div>

<script>
(function() {
    // Bootstrap modal cache (per tab)
    let modal_{{ $status }} = null;

    window.openUploadAttachmentModal_{{ $status }} = function(referenceNumber) {
        const modalEl = document.getElementById('uploadAttachmentModal-{{ $status }}');
        if (!modal_{{ $status }}) {
            modal_{{ $status }} = new bootstrap.Modal(modalEl);
        }
        document.getElementById('uploadRef-{{ $status }}').value = referenceNumber;
        modal_{{ $status }}.show();
    };

    const form = document.getElementById('uploadAttachmentForm-{{ $status }}');
    form?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(form);
        try {
            const res  = await fetch(@json(route('leave.upload-document')), {
                method: "POST",
                headers: { "X-CSRF-TOKEN": @json(csrf_token()) },
                body: fd
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json?.status !== 'success') {
                throw new Error(json?.message || 'Upload failed.');
            }

            if (typeof Swal !== 'undefined') {
                await Swal.fire('Uploaded', 'Document uploaded successfully.', 'success');
            } else {
                alert('Document uploaded successfully.');
            }

            // Close and reset the modal
            if (modal_{{ $status }}) modal_{{ $status }}.hide();
            form.reset();

            // Refresh this tab (prefer function; fallback to reload)
            if (typeof getLeave === 'function') {
                getLeave('{{ $status }}');
            } else {
                location.reload();
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', err?.message || 'Failed to upload attachment.', 'error');
            } else {
                alert(err?.message || 'Failed to upload attachment.');
            }
        }
    });

    // DataTable init (safe re-init)
    const tableId = '#{{ $status }}LeaveRequestsTable';
    if (window.jQuery && $(tableId).length > 0 && $.fn.DataTable) {
        try {
            if ($.fn.dataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            $(tableId).DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [-1, -2] }, // actions + attachment
                    { orderable: false, targets: [6] }       // progress
                ],
                language: {
                    emptyTable: "No {{ $status }} leave requests found",
                    info: "Showing _START_ to _END_ of _TOTAL_ requests",
                    infoEmpty: "Showing 0 to 0 of 0 requests",
                    lengthMenu: "Show _MENU_ requests per page",
                    search: "Search requests:"
                }
            });
        } catch (e) {
            console.warn('DataTable initialization failed:', e);
        }
    }
})();
</script>
