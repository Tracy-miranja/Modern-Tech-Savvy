<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                @foreach ($leave_periods as $leave_period)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $leave_period->slug }}-tab"
                        data-bs-toggle="tab" data-bs-target="#{{ $leave_period->slug }}" type="button" role="tab"
                        aria-controls="{{ $leave_period->slug }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-leave-period-slug="{{ $leave_period->slug }}">
                        {{ $leave_period->name }}
                    </button>
                </li>
                @endforeach
            </ul>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" id="openLeaveReportsBtn">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#exportEntitlementsPdfModal">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#encashmentModal" id="openEncashmentBtn">
                            <i class="bi bi-cash-coin me-2"></i> Encashment
                        </button>
                        <a href="{{ route('business.leave.entitlements.create', $currentBusiness->slug) }}"
                            class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-square-dotted me-2"></i> Leave Entitlements
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive" id="leaveEntitlementsContainer">
    {{ loader() }}
</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exportEntitlementsPdfModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Leave Entitlements PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Leave Period</label>
                        <select id="exportLeavePeriodId" class="form-select" required>
                            @foreach ($leave_periods as $leavePeriod)
                                <option value="{{ $leavePeriod->id }}" @selected($leavePeriod->slug === $initialLeavePeriodSlug)>{{ $leavePeriod->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department (optional)</label>
                        <select id="exportDepartmentId" class="form-select">
                            <option value="">All Departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Leave Type(s) (optional)</span>
                            <span>
                                <button type="button" class="btn btn-link btn-sm p-0 me-2" onclick="toggleAllExportChecks('exportLeaveTypeChecks', true)">All</button>
                                <button type="button" class="btn btn-link btn-sm p-0" onclick="toggleAllExportChecks('exportLeaveTypeChecks', false)">None</button>
                            </span>
                        </label>
                        <div id="exportLeaveTypeChecks" class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
                            @foreach ($leaveTypes as $leaveType)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="leave_type_ids[]" value="{{ $leaveType->id }}" id="exportLeaveType{{ $leaveType->id }}">
                                    <label class="form-check-label" for="exportLeaveType{{ $leaveType->id }}">{{ $leaveType->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Leave all unchecked to include every leave type.</small>
                    </div>
                    <div class="mb-1">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Employee(s) (optional)</span>
                            <span>
                                <button type="button" class="btn btn-link btn-sm p-0 me-2" onclick="toggleAllExportChecks('exportEmployeeChecks', true)">All</button>
                                <button type="button" class="btn btn-link btn-sm p-0" onclick="toggleAllExportChecks('exportEmployeeChecks', false)">None</button>
                            </span>
                        </label>
                        <div id="exportEmployeeChecks" class="border rounded p-2" style="max-height: 160px; overflow-y: auto;">
                            @foreach ($employees as $employee)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" id="exportEmployee{{ $employee->id }}">
                                    <label class="form-check-label" for="exportEmployee{{ $employee->id }}">{{ optional($employee->user)->name ?? 'Employee #' . $employee->id }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Leave all unchecked to include every employee.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="downloadLeaveEntitlementsPdf()">
                        <i class="bi bi-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Encashment -->
    <div class="modal fade" id="encashmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Encashment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="encashmentRequestForm" class="border-bottom pb-3 mb-3">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Employee</label>
                                <select id="encashmentEmployeeSelect" class="form-select form-select-sm">
                                    <option value="">Loading employees…</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Leave Type</label>
                                <select id="encashmentLeaveTypeSelect" class="form-select form-select-sm">
                                    @foreach (($leaveTypes ?? []) as $leaveType)
                                        <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Days</label>
                                <input type="number" step="0.5" min="0.5" id="encashmentDaysInput" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-success btn-sm w-100" id="submitEncashmentBtn">Request</button>
                            </div>
                        </div>
                    </form>

                    <h6 class="small text-muted">Pending / Recent Requests</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr><th>Employee</th><th>Leave Type</th><th>Days</th><th>Amount</th><th>Status</th><th style="width:200px;">Action</th></tr>
                            </thead>
                            <tbody id="encashmentsTableBody">
                                <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? [], 'leavePeriods' => $leave_periods ?? [], 'leaveTypes' => $leaveTypes ?? []])

   @push('scripts')
<script src="{{ asset('js/main/leave-entitlement.js') }}" type="module"></script>
<script src="{{ asset('js/main/report-modal.js') }}"></script>
<script>
    ReportModal.init({
        employeeOptionsUrl: @json(route('business.organogram.employee-options', $currentBusiness->slug)),
    });

    document.getElementById('openLeaveReportsBtn').addEventListener('click', function () {
        ReportModal.open([
            {
                key: 'balance',
                label: 'Leave Balance Report',
                filters: ['leave_period', 'leave_type', 'department', 'job_category', 'employee'],
                previewUrl: @json(route('business.leave.reports.balance.preview', $currentBusiness->slug)),
                downloadUrl: @json(route('business.leave.reports.balance.download', $currentBusiness->slug)),
            },
            {
                key: 'full',
                label: 'Full Leave Report',
                filters: ['date_range', 'leave_type', 'department', 'job_category', 'employee'],
                previewUrl: @json(route('business.leave.reports.full.preview', $currentBusiness->slug)),
                downloadUrl: @json(route('business.leave.reports.full.download', $currentBusiness->slug)),
            },
            {
                key: 'types',
                label: 'Leave Types Usage Report',
                filters: ['date_range', 'department', 'job_category'],
                previewUrl: @json(route('business.leave.reports.types.preview', $currentBusiness->slug)),
                downloadUrl: @json(route('business.leave.reports.types.download', $currentBusiness->slug)),
            },
            {
                key: 'per-member',
                label: 'Per-Member Leave Report',
                filters: ['date_range', 'employee'],
                previewUrl: @json(route('business.leave.reports.per-member.preview', $currentBusiness->slug)),
                downloadUrl: @json(route('business.leave.reports.per-member.download', $currentBusiness->slug)),
            },
            {
                key: 'master',
                label: 'Leave Master Report',
                filters: ['leave_period', 'leave_type', 'department', 'job_category', 'employee'],
                previewUrl: @json(route('business.leave.reports.master.preview', $currentBusiness->slug)),
                downloadUrl: @json(route('business.leave.reports.master.download', $currentBusiness->slug)),
            },
        ]);
    });

    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        const firstTab = document.querySelector('#myTab .nav-link.active');
        if (firstTab) {
            const firstLeavePeriodSlug = firstTab.dataset.leavePeriodSlug;
            console.log('First tab found, calling getLeaveEntitlements with:', firstLeavePeriodSlug);
            getLeaveEntitlements(1, firstLeavePeriodSlug);
        } else {
            // No leave periods exist yet for this business - nothing to
            // fetch entitlements for, so replace the loader with a plain
            // empty-state message instead of leaving it spinning forever.
            document.getElementById('leaveEntitlementsContainer').innerHTML =
                '<p class="text-muted mb-0">No leave periods set.</p>';
        }
    });

    document.getElementById('myTab').addEventListener('click', function(event) {
        const clickedTab = event.target.closest('.nav-link');
        if (clickedTab) {
            const leavePeriodSlug = clickedTab.dataset.leavePeriodSlug;
            console.log('Tab clicked, calling getLeaveEntitlements with:', leavePeriodSlug);
            getLeaveEntitlements(1, leavePeriodSlug);
        }
    });

    window.businessSlug = '{{ request()->route('business') }}'; // business slug from the route
    console.log('Business Slug set to:', window.businessSlug);

    // ---- Leave Encashment ---------------------------------------------
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', $currentBusiness->slug));
        const encashmentsFetchUrl = @json(route('business.leave.encashments.fetch', $currentBusiness->slug));
        const encashmentsStoreUrl = @json(route('business.leave.encashments.store', $currentBusiness->slug));
        const encashmentApproveUrlTemplate = @json(route('business.leave.encashments.approve', ['business' => $currentBusiness->slug, 'encashment' => '__ID__']));
        const encashmentRejectUrlTemplate = @json(route('business.leave.encashments.reject', ['business' => $currentBusiness->slug, 'encashment' => '__ID__']));
        const encashmentDisburseUrlTemplate = @json(route('business.leave.encashments.mark-disbursed', ['business' => $currentBusiness->slug, 'encashment' => '__ID__']));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        }

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

        function statusBadge(status) {
            const map = { pending: 'secondary', approved: 'primary', rejected: 'danger', disbursed: 'success' };
            return `<span class="badge bg-${map[status] ?? 'secondary'}">${status}</span>`;
        }

        async function loadEncashmentEmployeeOptions() {
            const select = document.getElementById('encashmentEmployeeSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = employees.map(e => `<option value="${e.id}">${escapeHtml(e.name)}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        }

        async function loadEncashments() {
            const tbody = document.getElementById('encashmentsTableBody');
            try {
                const resp = await fetch(encashmentsFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const rows = payload.data ?? [];

                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No encashment requests yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map(r => `
                    <tr>
                        <td>${escapeHtml(r.employee?.user?.name ?? 'N/A')}</td>
                        <td>${escapeHtml(r.leave_type?.name ?? '—')}</td>
                        <td class="center">${r.days_requested}</td>
                        <td class="center">${r.amount}</td>
                        <td>${statusBadge(r.status)}</td>
                        <td>
                            ${r.status === 'pending' ? `
                                <button type="button" class="btn btn-sm btn-outline-success approve-encashment-btn" data-id="${r.id}">Approve</button>
                                <button type="button" class="btn btn-sm btn-outline-danger reject-encashment-btn" data-id="${r.id}">Reject</button>
                            ` : ''}
                            ${r.status === 'approved' ? `<button type="button" class="btn btn-sm btn-outline-primary disburse-encashment-btn" data-id="${r.id}">Mark Disbursed</button>` : ''}
                        </td>
                    </tr>`).join('');

                document.querySelectorAll('.approve-encashment-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        try {
                            await postJson(encashmentApproveUrlTemplate.replace('__ID__', this.dataset.id), {});
                            toastr.success('Encashment approved.');
                            loadEncashments();
                        } catch (e) { toastr.error(e.message || 'Could not approve.'); }
                    });
                });
                document.querySelectorAll('.reject-encashment-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const reason = prompt('Reason for rejection:');
                        if (!reason) return;
                        try {
                            await postJson(encashmentRejectUrlTemplate.replace('__ID__', this.dataset.id), { rejection_reason: reason });
                            toastr.success('Encashment rejected.');
                            loadEncashments();
                        } catch (e) { toastr.error(e.message || 'Could not reject.'); }
                    });
                });
                document.querySelectorAll('.disburse-encashment-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        try {
                            await postJson(encashmentDisburseUrlTemplate.replace('__ID__', this.dataset.id), {});
                            toastr.success('Marked disbursed.');
                            loadEncashments();
                        } catch (e) { toastr.error(e.message || 'Could not update.'); }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load encashments.</td></tr>';
            }
        }

        document.getElementById('openEncashmentBtn').addEventListener('click', function () {
            loadEncashmentEmployeeOptions();
            loadEncashments();
        });

        document.getElementById('submitEncashmentBtn').addEventListener('click', async function () {
            const employeeId = document.getElementById('encashmentEmployeeSelect').value;
            const leaveTypeId = document.getElementById('encashmentLeaveTypeSelect').value;
            const days = document.getElementById('encashmentDaysInput').value;
            if (!employeeId || !leaveTypeId || !days) { toastr.error('Select an employee, leave type, and number of days.'); return; }

            try {
                const payload = await postJson(encashmentsStoreUrl, {
                    employee_id: employeeId, leave_type_id: leaveTypeId, days_requested: days,
                });
                toastr.success(`Encashment requested for ${payload.data.amount}.`);
                document.getElementById('encashmentDaysInput').value = '';
                loadEncashments();
            } catch (e) {
                toastr.error(e.message || 'Could not request encashment.');
            }
        });
    })();
</script>
@endpush
</x-app-layout>
