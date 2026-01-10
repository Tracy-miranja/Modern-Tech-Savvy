<x-app-layout>
    @php
        /** @var \App\Models\LeaveRequest $leave */
        $statusName = !is_null($leave->rejection_reason)
            ? 'rejected'
            : (!is_null($leave->approved_by) ? 'approved' : 'pending');

        $statusColors = [
            'pending'  => ['icon' => 'fa fa-clock',         'color' => '#ffc107', 'label' => 'Pending'],
            'approved' => ['icon' => 'fa fa-check-circle',  'color' => '#28a745', 'label' => 'Approved'],
            'rejected' => ['icon' => 'fa fa-times-circle',  'color' => '#dc3545', 'label' => 'Rejected'],
        ];

        $isOwner        = optional(auth()->user()->employee)->id === (int) $leave->employee_id;
        $activeRole     = session('active_role');
        $isApproverRole = in_array($activeRole, ['head-of-department','business-hr','business-admin','business-head'], true);

        $canApprove = $isApproverRole && method_exists($leave, 'canUserApprove')
            ? $leave->canUserApprove(auth()->user())
            : false;

        $levelsTotal   = (int) optional($leave->leaveType)->approval_levels ?: 0;
        $levelsCurrent = (int) ($leave->current_approval_level ?? 0);
        $progressPct   = $levelsTotal > 0 ? min(100, round(($levelsCurrent / $levelsTotal) * 100)) : ($statusName === 'approved' ? 100 : 0);

        // Back button fallbacks by role
        $roleFallbacks = [
            'head-of-department' => url('/dashboard'),
            'business-hr'        => url('/dashboard'),
            'business-admin'     => url('/dashboard'),
            'business-head'      => url('/dashboard'),
            'chief-of-staff'     => url('/dashboard'),
            'business-employee'  => url('/leave/requests'),
        ];
        $fallbackBackUrl = $roleFallbacks[$activeRole] ?? url('/');

        // Business slug for business.* routes
        $businessSlug = $currentBusiness->slug ?? session('active_business_slug');

        // Remaining days for this employee + leave type + current period
        $remainingDays = null;
        try {
            $refDate = $leave->start_date
                ? $leave->start_date->toDateString()
                : now()->toDateString();

            /** @var \App\Models\LeaveEntitlement|null $ent */
            $ent = \App\Models\LeaveEntitlement::query()
                ->where('business_id', $currentBusiness->id)
                ->where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->whereHas('leavePeriod', function ($q) use ($refDate) {
                    $q->whereDate('start_date', '<=', $refDate)
                      ->whereDate('end_date', '>=', $refDate);
                })
                ->with('leavePeriod')
                ->first();

            if ($ent) {
                $remainingDays = $ent->getRemainingDays();
            }
        } catch (\Throwable $e) {
            $remainingDays = null;
        }

        $employee      = optional($leave->employee);
        $employeeUser  = optional($employee->user);
        $employeeName  = $employeeUser->name ?? '—';
        $employeeNo    = $employee->employee_number ?? null;
        $employeeDept  = optional($employee->department)->name ?? null;
        $employeeJob   = optional($employee->jobCategory)->name ?? null;
        $businessName  = $currentBusiness->company_name ?? 'HR / Finance System';
    @endphp

    {{-- Layout + Print styles --}}
    <style>
        /* Center content on screen as well */
        .leave-print-area {
            max-width: 900px;
            margin: 0 auto;
        }

        .leave-doc-header h3 {
            font-size: 1.4rem;
            margin-bottom: .25rem;
        }

        .leave-doc-header small {
            font-size: 0.8rem;
        }

        .leave-meta-table td {
            padding: 2px 6px;
            font-size: 0.8rem;
        }

        .timeline-item-icon {
            width: 22px;
        }

        .timeline-item {
            margin-bottom: 0.5rem;
        }

        .timeline-item small {
            font-size: 0.75rem;
        }

        @page {
            margin: 15mm;
        }

        @media print {
            /* Hide global chrome / layout shell */
            .app-sidebar,
            .main-sidebar-header,
            .app-header,
            .header,
            .main-header,
            .page-header,
            .app-footer,
            .footer,
            .main-footer,
            .page-footer,
            .nav-sidebar,
            .sidebar,
            .navbar,
            .topbar,
            .preloader {
                display: none !important;
            }

            /* Hide any semantic header/footer tags in layout */
            header,
            footer {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 11pt;
            }

            .page__full-wrapper {
                padding: 0 !important;
                margin: 0 !important;
            }

            .leave-print-area {
                max-width: 900px;
                margin: 0 auto !important;
            }

            /* Make cards look like a document, not app UI */
            .card {
                box-shadow: none !important;
                border: 1px solid #cccccc !important;
            }

            .card-header {
                border-bottom: 1px solid #cccccc !important;
                background: #f5f5f5 !important;
                padding-top: 4px;
                padding-bottom: 4px;
            }

            .card-body {
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .d-print-none {
                display: none !important;
            }

            .d-print-block {
                display: block !important;
            }

            .leave-doc-header h3 {
                font-size: 16pt;
            }

            .leave-doc-header small {
                font-size: 9pt;
            }

            .leave-meta-table td {
                font-size: 8.5pt;
            }

            .timeline-item-icon i {
                font-size: 10pt !important;
            }

            .timeline-item small {
                font-size: 8pt !important;
            }
        }
    </style>

    <div class="leave-print-area">
        {{-- Top Controls (screen only) --}}
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary" id="smartBackBtn">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </button>
                <button type="button" class="btn btn-outline-primary" id="printBtn">
                    <i class="fa-solid fa-print me-1"></i> Print
                </button>
                <span class="text-muted small">
                    @if($activeRole === 'head-of-department' || $activeRole === 'chief-of-staff')
                        Back takes you to your dashboard.
                    @elseif($activeRole === 'business-employee')
                        Back takes you to your leave requests.
                    @endif
                </span>
            </div>

            <div>
                <span class="badge"
                      style="background-color: {{ $statusColors[$statusName]['color'] ?? '#6c757d' }}">
                    <i class="{{ $statusColors[$statusName]['icon'] ?? 'fa fa-info-circle' }} me-1"></i>
                    {{ $statusColors[$statusName]['label'] ?? ucfirst($statusName) }}
                </span>
                @if($leave->is_tentative)
                    <span class="badge bg-secondary ms-1">Tentative</span>
                @endif
                @if($leave->requires_documentation && !$leave->attachment)
                    <span class="badge bg-warning text-dark ms-1">Documentation Required</span>
                @endif
            </div>
        </div>

        {{-- Document-style header (printed & screen) --}}
        <div class="card mb-3">
            <div class="card-body py-3 leave-doc-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h3 class="mb-0">{{ $businessName }}</h3>
                        <small class="text-muted d-block">Leave Request Summary & Timeline</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold">{{ $employeeName }}</div>
                        @if($employeeNo)
                            <small class="text-muted d-block">Employee No: {{ $employeeNo }}</small>
                        @endif
                        @if($employeeDept)
                            <small class="text-muted d-block">Department: {{ $employeeDept }}</small>
                        @endif
                        @if($employeeJob)
                            <small class="text-muted d-block">Job Category: {{ $employeeJob }}</small>
                        @endif
                    </div>
                </div>

                <hr class="my-2">

                <table class="leave-meta-table w-100">
                    <tr>
                        <td><strong>Request #:</strong> {{ $leave->reference_number }}</td>
                        <td><strong>Leave Type:</strong> {{ optional($leave->leaveType)->name ?? '—' }}</td>
                        <td><strong>Status:</strong> {{ $statusColors[$statusName]['label'] ?? ucfirst($statusName) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Start Date:</strong> {{ optional($leave->start_date)->format('Y-m-d') }}</td>
                        <td><strong>End Date:</strong> {{ optional($leave->end_date)->format('Y-m-d') }}</td>
                        <td><strong>Total Days:</strong> {{ number_format((float)$leave->total_days, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Remaining Days:</strong>
                            @if(!is_null($remainingDays))
                                {{ number_format((float)$remainingDays, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td><strong>Created:</strong> {{ optional($leave->created_at)->format('Y-m-d H:i') }}</td>
                        <td><strong>Updated:</strong> {{ optional($leave->updated_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row g-3">
            {{-- Approve / Reject (screen only, not printed) --}}
            @if($isApproverRole && $canApprove && $statusName === 'pending')
                <div class="col-md-4 d-print-none">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="mb-3">Manage Request</h6>
                            <div class="d-grid gap-2">
                                <button type="button"
                                        onclick="manageLeave(this)"
                                        data-action="approve"
                                        data-leave="{{ $leave->reference_number }}"
                                        class="btn btn-success">
                                    <i class="fa-solid fa-check me-1"></i> Approve Leave
                                </button>

                                <button type="button"
                                        onclick="manageLeave(this)"
                                        data-action="reject"
                                        data-leave="{{ $leave->reference_number }}"
                                        class="btn btn-danger">
                                    <i class="fa-solid fa-ban me-1"></i> Deny Leave
                                </button>
                            </div>

                            @if($levelsTotal > 0)
                                <div class="mt-3">
                                    <small class="text-muted">Approval Progress</small>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: {{ $progressPct }}%;"
                                             aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small>Level {{ $levelsCurrent }} / {{ $levelsTotal }}</small>
                                        <small>{{ $progressPct }}%</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Revoke / Shorten (screen only) --}}
            @if($isApproverRole && method_exists($leave,'canUserRevoke') && $leave->status === 'approved' && $leave->canUserRevoke(auth()->user()))
                <div class="col-md-4 d-print-none">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="mb-3">Revoke / Shorten Leave</h6>
                            @php
                                $leaveStarted = $leave->start_date->startOfDay()->lte(now()->startOfDay());
                            @endphp

                            <form id="revokeForm"
                                action="{{ route('business.leave.revoke', ['business' => $businessSlug]) }}"
                                method="post">
                                @csrf
                                <input type="hidden" name="reference_number" value="{{ $leave->reference_number }}">

                                <div class="mb-2">
                                    <label class="form-label">Action</label>
                                    <select name="action" id="revokeAction" class="form-select" required>
                                        @if(!$leaveStarted)
                                            <option value="full">Full Revoke (Cancel Leave)</option>
                                        @endif
                                        <option value="shorten">Shorten Leave</option>
                                    </select>
                                </div>

                                <div class="mb-2" id="returnDateWrapper">
                                    <label class="form-label">Return To Work Date</label>
                                    <input type="date" name="return_to_work_date"
                                        class="form-control"
                                        min="{{ now()->toDateString() }}">
                                    <small class="text-muted">
                                        Employee resumes on this date. Last leave day becomes the previous day.
                                    </small>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Reason (optional)</label>
                                    <input type="text" name="reason" class="form-control" maxlength="500">
                                </div>

                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Proceed
                                </button>
                            </form>


                            @if(is_array($leave->revocation_history ?? null) && count($leave->revocation_history))
                                <hr>
                                <small class="text-muted d-block mb-1">Revocation History</small>
                                @foreach(array_reverse($leave->revocation_history) as $rev)
                                    <div class="small mb-1">
                                        <div>On {{ \Carbon\Carbon::parse($rev['revoked_at'])->format('Y-m-d H:i') }} by {{ $rev['revoked_by_name'] ?? ('User #'.$rev['revoked_by']) }}</div>
                                        <div>New End: {{ $rev['new_end_date'] ?? '—' }} • RTW: {{ $rev['return_to_work_date'] ?? '—' }} • Refunded: {{ $rev['refund_days'] ?? 0 }}</div>
                                        @if(!empty($rev['reason'])) <div>Reason: {{ $rev['reason'] }}</div> @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main content column --}}
            <div class="{{ ($isApproverRole && $canApprove && $statusName === 'pending') ? 'col-md-8' : 'col-md-12' }}">
                {{-- Summary + Attachments --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Summary --}}
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <h6 class="text-muted mb-2">Request Summary</h6>
                                    <dl class="row mb-0 small">
                                        <dt class="col-5">Employee</dt>
                                        <dd class="col-7">{{ $employeeName }}</dd>

                                        <dt class="col-5">Leave Type</dt>
                                        <dd class="col-7">{{ optional($leave->leaveType)->name ?? '—' }}</dd>

                                        <dt class="col-5">Start Date</dt>
                                        <dd class="col-7">{{ optional($leave->start_date)->format('Y-m-d') }}</dd>

                                        <dt class="col-5">End Date</dt>
                                        <dd class="col-7">{{ optional($leave->end_date)->format('Y-m-d') }}</dd>

                                        <dt class="col-5">Total Days</dt>
                                        <dd class="col-7">{{ number_format((float)$leave->total_days, 2) }}</dd>

                                        <dt class="col-5">Remaining Days</dt>
                                        <dd class="col-7">
                                            @if(!is_null($remainingDays))
                                                {{ number_format((float)$remainingDays, 2) }}
                                            @else
                                                —
                                            @endif
                                        </dd>

                                        <dt class="col-5">Half Day</dt>
                                        <dd class="col-7">
                                            @if($leave->half_day)
                                                Yes ({{ $leave->half_day_type ? ucfirst($leave->half_day_type) : 'N/A' }})
                                            @else
                                                No
                                            @endif
                                        </dd>

                                        <dt class="col-5">Reason</dt>
                                        <dd class="col-7">{{ $leave->reason ?? '—' }}</dd>
                                    </dl>
                                </div>
                            </div>

                            {{-- Attachments & final approval --}}
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <h6 class="text-muted mb-2">Attachments & Approvals</h6>

                                    {{-- Attachment --}}
                                    @if($leave->attachment)
                                        <div class="mb-2">
                                            <a class="btn btn-outline-primary btn-sm d-print-none"
                                               href="{{ asset('storage/' . $leave->attachment) }}"
                                               target="_blank" download>
                                                <i class="fa-solid fa-download me-1"></i> Download Attachment
                                            </a>
                                            <div class="small text-muted d-none d-print-block">
                                                Attachment available in system: {{ basename($leave->attachment) }}
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted mb-2 small">No attachment uploaded.</p>
                                    @endif

                                    {{-- Upload later (screen only) --}}
                                    @if($isOwner && $leave->requires_documentation && !$leave->attachment && $statusName !== 'rejected')
                                        <form id="inlineUploadForm"
                                              action="{{ route('leave.upload-document') }}"
                                              method="post" enctype="multipart/form-data" class="mt-2 d-print-none">
                                            @csrf
                                            <input type="hidden" name="reference_number" value="{{ $leave->reference_number }}">
                                            <div class="mb-2">
                                                <label class="form-label small">Upload Required Document</label>
                                                <input class="form-control form-control-sm" type="file" name="attachment"
                                                       accept=".pdf,.jpg,.png,.doc,.docx" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-upload me-1"></i> Upload
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Final approval details --}}
                                    @if($leave->approved_by && $leave->approved_at)
                                        @php
                                            $finalApprover = $leave->approvedBy;
                                            $finalHist = collect((array)($leave->approval_history ?? []))
                                                ->filter(fn($h) => (int)($h['approver_id'] ?? 0) === (int)$leave->approved_by)
                                                ->sortByDesc(fn($h) => (int)($h['level'] ?? 0))
                                                ->first();
                                            $finalRole = $finalHist['approver_role'] ?? null;
                                            $finalRoleLabel = $finalRole ? ucfirst(str_replace('-', ' ', $finalRole)) : null;
                                        @endphp
                                        <hr class="my-2">
                                        <div class="small">
                                            <span class="text-muted d-block">Final Approval</span>
                                            <span>
                                                By {{ $finalApprover?->name ?? ('User ID: '.$leave->approved_by) }}
                                                @if($finalRoleLabel) ({{ $finalRoleLabel }}) @endif
                                                at {{ optional($leave->approved_at)->format('Y-m-d H:i') }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Rejection reason --}}
                                    @if($statusName === 'rejected' && $leave->rejection_reason)
                                        <hr class="my-2">
                                        <div class="small">
                                            <span class="text-muted d-block">Rejection Reason</span>
                                            <div class="text-danger">{{ $leave->rejection_reason }}</div>
                                        </div>
                                    @endif

                                    {{-- Download PDF (screen only) --}}
                                    @if($statusName === 'approved' && ($isOwner || $isApproverRole))
                                        <a href="{{ route('business.leave.download', [
                                            'business'  => $businessSlug,
                                            'reference' => $leave->reference_number
                                        ]) }}" class="btn btn-outline-dark btn-sm mt-2 d-print-none">
                                            <i class="fa-solid fa-file-arrow-down me-1"></i> Download PDF
                                        </a>
                                        <div class="small text-muted d-none d-print-block mt-1">
                                            A signed PDF copy is available in the system for this request.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Approval progress bar (screen + print, compact) --}}
                        @if($levelsTotal > 0)
                            <div class="mt-2">
                                <small class="text-muted d-block mb-1">
                                    Approval Progress ({{ $levelsCurrent }} / {{ $levelsTotal }})
                                </small>
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: {{ $progressPct }}%;"
                                         aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <h6 class="mb-0">Timeline</h6>
                    </div>
                    <div class="card-body py-2">
                        @php
                            $timeline = [];
                            $timeline[] = [
                                'name'       => 'submitted',
                                'title'      => 'Leave Submitted',
                                'at'         => optional($leave->created_at)->format('Y-m-d H:i'),
                                'reason'     => null,
                                'icon'       => 'fa fa-paper-plane',
                                'colorClass' => 'text-primary',
                            ];

                            foreach ((array) ($leave->approval_history ?? []) as $hist) {
                                $roleLabel = !empty($hist['approver_role'])
                                    ? ucfirst(str_replace('-', ' ', (string)$hist['approver_role']))
                                    : null;

                                $who = trim(
                                    ($hist['approver_name'] ?? 'Approver')
                                    .' '
                                    .($roleLabel ? "({$roleLabel})" : '')
                                );

                                $timeline[] = [
                                    'name'       => 'approval_level_' . ($hist['level'] ?? '?'),
                                    'title'      => 'Approval Level ' . ($hist['level'] ?? '?'),
                                    'at'         => isset($hist['approved_at']) ? \Carbon\Carbon::parse($hist['approved_at'])->format('Y-m-d H:i') : null,
                                    'reason'     => null,
                                    'icon'       => 'fa fa-check',
                                    'colorClass' => 'text-success',
                                    'meta'       => $who . ' — ID: ' . ($hist['approver_id'] ?? '—'),
                                ];
                            }

                            if ($statusName === 'rejected') {
                                $timeline[] = [
                                    'name'       => 'rejected',
                                    'title'      => 'Rejected',
                                    'at'         => optional($leave->updated_at)->format('Y-m-d H:i'),
                                    'reason'     => $leave->rejection_reason,
                                    'icon'       => 'fa fa-times-circle',
                                    'colorClass' => 'text-danger',
                                ];
                            } elseif ($statusName === 'approved') {
                                $timeline[] = [
                                    'name'       => 'approved',
                                    'title'      => 'Approved',
                                    'at'         => optional($leave->approved_at)->format('Y-m-d H:i'),
                                    'reason'     => null,
                                    'icon'       => 'fa fa-check-circle',
                                    'colorClass' => 'text-success',
                                ];
                            }
                        @endphp

                        @forelse($timeline as $item)
                            <div class="d-flex align-items-start timeline-item">
                                <div class="timeline-item-icon me-2 text-center">
                                    <i class="{{ $item['icon'] }} {{ $item['colorClass'] }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $item['title'] }}</div>
                                    <small class="text-muted d-block">{{ $item['at'] ?? '—' }}</small>
                                    @if(!empty($item['meta']))
                                        <small class="text-muted d-block">{{ $item['meta'] }}</small>
                                    @endif
                                    @if(!empty($item['reason']))
                                        <div class="mt-1 small">Reason: {{ $item['reason'] }}</div>
                                    @endif
                                </div>
                            </div>
                            @if(!$loop->last)
                                <hr class="my-1">
                            @endif
                        @empty
                            <p class="text-muted mb-0 small">No timeline data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div> {{-- end .leave-print-area --}}

    @push('scripts')
        <script src="{{ asset('js/main/leave.js') }}" type="module"></script>

        <script>
        (function() {
            const btn = document.getElementById('smartBackBtn');
            const fallbackUrl = @json($fallbackBackUrl);
            btn?.addEventListener('click', function() {
                try {
                    const ref = document.referrer || '';
                    const sameOrigin = ref && new URL(ref).origin === window.location.origin;
                    if (sameOrigin && window.history.length > 1) {
                        window.history.back();
                        return;
                    }
                } catch (e) {}
                window.location.href = fallbackUrl;
            });

            const printBtn = document.getElementById('printBtn');
            printBtn?.addEventListener('click', function () {
                window.print();
            });

            const actionSelect = document.getElementById('revokeAction');
            const returnWrapper = document.getElementById('returnDateWrapper');
            const returnInput = returnWrapper?.querySelector('input');

            function toggleReturnDate() {
                const isShorten = actionSelect.value === 'shorten';
                returnWrapper.style.display = isShorten ? 'block' : 'none';
                returnInput.required = isShorten;
            }

            actionSelect?.addEventListener('change', toggleReturnDate);
            toggleReturnDate(); // init


            const revokeForm = document.getElementById('revokeForm');
            if (revokeForm) {
                revokeForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(revokeForm);
                    try {
                        const res = await fetch(revokeForm.getAttribute('action'), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': @json(csrf_token()) },
                            body: fd
                        });
                        const json = await res.json().catch(() => ({}));
                        if (!res.ok || json?.status !== 'success') {
                            throw new Error(json?.message || 'Failed to revoke leave.');
                        }
                        if (window.Swal) {
                            await Swal.fire('Updated', json?.message || 'Leave shortened.', 'success');
                        } else {
                            alert(json?.message || 'Leave shortened.');
                        }
                        window.location.reload();
                    } catch (err) {
                        if (window.Swal) {
                            Swal.fire('Error', err?.message || 'Failed to revoke leave.', 'error');
                        } else {
                            alert(err?.message || 'Failed to revoke leave.');
                        }
                    }
                });
            }

            const form = document.getElementById('inlineUploadForm');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const fd = new FormData(form);
                    try {
                        const res  = await fetch(form.getAttribute('action'), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: fd
                        });
                        const json = await res.json().catch(() => ({}));

                        if (!res.ok || json?.status !== 'success') {
                            throw new Error(json?.message || 'Upload failed.');
                        }

                        if (window.Swal) {
                            await Swal.fire('Uploaded', 'Document uploaded successfully.', 'success');
                        } else {
                            alert('Document uploaded successfully.');
                        }
                        window.location.reload();
                    } catch (err) {
                        if (window.Swal) {
                            Swal.fire('Error', err?.message || 'Failed to upload attachment.', 'error');
                        } else {
                            alert(err?.message || 'Failed to upload attachment.');
                        }
                    }
                });
            }
        })();
        </script>
    @endpush
</x-app-layout>
