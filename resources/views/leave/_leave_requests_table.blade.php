{{-- resources/views/leave/_leave_requests_table.blade.php --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">Leave Requests</h5>

            @php
                $activeRole = session('active_role');
                $isEmployee = $activeRole === 'business-employee';

                // Derive departments & job categories from the loaded requests (for admin side filters)
                $departments   = $leaveRequests->pluck('employee')->filter()
                    ->pluck('department')->filter()->unique('id');
                $jobCategories = $leaveRequests->pluck('employee')->filter()
                    ->pluck('jobCategory')->filter()->unique('id');
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
        {{-- Filter Section --}}
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="fa-solid fa-filter"></i> Filters
                </h6>
                <div class="btn-group">
                    <button type="button" class="btn btn-success btn-sm" onclick="exportLeaveRequests_{{ $status }}('excel')">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="exportLeaveRequests_{{ $status }}('pdf')">
                        <i class="fa-solid fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>

            <div class="row g-3" id="filterSection_{{ $status }}">
                {{-- Leave Type – visible to everyone --}}
                <div class="col-md-3">
                    <label class="form-label small">Leave Type</label>
                    <select class="form-select form-select-sm" id="filterLeaveType_{{ $status }}">
                        <option value="">All Types</option>
                        @foreach($leaveRequests->pluck('leaveType')->unique('id')->filter() as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Employee filter – ONLY for admins / approvers, NOT employees --}}
                @unless($isEmployee)
                    <div class="col-md-3">
                        <label class="form-label small">Employee</label>
                        <select class="form-select form-select-sm" id="filterEmployee_{{ $status }}">
                            <option value="">All Employees</option>
                            @foreach($leaveRequests->pluck('employee')->unique('id')->filter() as $emp)
                                <option value="{{ $emp->id }}">{{ optional($emp->user)->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                {{-- Department filter – ONLY admin/HR/HOD etc. --}}
                @unless($isEmployee)
                    <div class="col-md-3">
                        <label class="form-label small">Department</label>
                        <select class="form-select form-select-sm" id="filterDepartment_{{ $status }}">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Job Category</label>
                        <select class="form-select form-select-sm" id="filterJobCategory_{{ $status }}">
                            <option value="">All Job Categories</option>
                            @foreach($jobCategories as $jc)
                                <option value="{{ $jc->id }}">{{ $jc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div class="col-md-2">
                    <label class="form-label small">Start Date From</label>
                    <input type="date" class="form-control form-control-sm" id="filterStartDate_{{ $status }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Start Date To</label>
                    <input type="date" class="form-control form-control-sm" id="filterEndDate_{{ $status }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Days Range</label>
                    <select class="form-select form-select-sm" id="filterDaysRange_{{ $status }}">
                        <option value="">All</option>
                        <option value="0-3">0-3 days</option>
                        <option value="4-7">4-7 days</option>
                        <option value="8-14">8-14 days</option>
                        <option value="15+">15+ days</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Approval Status</label>
                    <select class="form-select form-select-sm" id="filterApprovalStatus_{{ $status }}">
                        <option value="">All</option>
                        <option value="awaiting">Awaiting Approval</option>
                        <option value="under-review">Under Review</option>
                        <option value="complete">Complete</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Documentation</label>
                    <select class="form-select form-select-sm" id="filterDocumentation_{{ $status }}">
                        <option value="">All</option>
                        <option value="required">Required</option>
                        <option value="attached">Attached</option>
                        <option value="missing">Missing</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Tentative</label>
                    <select class="form-select form-select-sm" id="filterTentative_{{ $status }}">
                        <option value="">All</option>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>

                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm me-2" onclick="applyFilters_{{ $status }}()">
                        <i class="fa-solid fa-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters_{{ $status }}()">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Active Filters Display --}}
        <div id="activeFilters_{{ $status }}" class="mb-3" style="display: none;">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-secondary">Active Filters:</span>
                <div id="activeFiltersList_{{ $status }}" class="d-flex gap-2 flex-wrap"></div>
            </div>
        </div>

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

                    $employee    = $request->employee;
                    $department  = $employee->department ?? null;
                    $jobCategory = $employee->jobCategory ?? null;
                @endphp

                <tr data-leave-type="{{ optional($request->leaveType)->id }}"
                    data-employee="{{ $request->employee_id }}"
                    data-department="{{ $department->id ?? '' }}"
                    data-job-category="{{ $jobCategory->id ?? '' }}"
                    data-start-date="{{ optional($request->start_date)->format('Y-m-d') }}"
                    data-days="{{ $request->total_days }}"
                    data-has-attachment="{{ $request->attachment ? 'yes' : 'no' }}"
                    data-requires-docs="{{ $request->requires_documentation ? 'yes' : 'no' }}"
                    data-tentative="{{ $request->is_tentative ? 'yes' : 'no' }}"
                    data-can-approve="{{ $canApprove ? 'yes' : 'no' }}"
                    data-approval-status="{{ $request->status === 'approved' ? 'complete' : ($canApprove ? 'awaiting' : 'under-review') }}">

                    <td class="fw-bold">
                        {{ $request->reference_number }}
                        @if($request->is_tentative)
                            <small class="badge bg-warning text-dark ms-1">Tentative</small>
                        @endif
                    </td>

                    <td>{{ optional(optional($employee)->user)->name ?? 'N/A' }}</td>

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
    let modal_{{ $status }} = null;
    let dataTable_{{ $status }} = null;

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

            if (modal_{{ $status }}) modal_{{ $status }}.hide();
            form.reset();

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

    // DataTable init
    const tableId = '#{{ $status }}LeaveRequestsTable';
    if (window.jQuery && $(tableId).length > 0 && $.fn.DataTable) {
        try {
            if ($.fn.dataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            dataTable_{{ $status }} = $(tableId).DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [-1, -2] },
                    { orderable: false, targets: [6] }
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

    // Filter Functions
    window.applyFilters_{{ $status }} = function() {
        if (!dataTable_{{ $status }}) return;

        const leaveTypeEl      = document.getElementById('filterLeaveType_{{ $status }}');
        const employeeEl       = document.getElementById('filterEmployee_{{ $status }}');
        const departmentEl     = document.getElementById('filterDepartment_{{ $status }}');
        const jobCategoryEl    = document.getElementById('filterJobCategory_{{ $status }}');
        const startDateEl      = document.getElementById('filterStartDate_{{ $status }}');
        const endDateEl        = document.getElementById('filterEndDate_{{ $status }}');
        const daysRangeEl      = document.getElementById('filterDaysRange_{{ $status }}');
        const approvalStatusEl = document.getElementById('filterApprovalStatus_{{ $status }}');
        const documentationEl  = document.getElementById('filterDocumentation_{{ $status }}');
        const tentativeEl      = document.getElementById('filterTentative_{{ $status }}');

        const leaveType      = leaveTypeEl      ? leaveTypeEl.value      : '';
        const employee       = employeeEl       ? employeeEl.value       : '';
        const department     = departmentEl     ? departmentEl.value     : '';
        const jobCategory    = jobCategoryEl    ? jobCategoryEl.value    : '';
        const startDate      = startDateEl      ? startDateEl.value      : '';
        const endDate        = endDateEl        ? endDateEl.value        : '';
        const daysRange      = daysRangeEl      ? daysRangeEl.value      : '';
        const approvalStatus = approvalStatusEl ? approvalStatusEl.value : '';
        const documentation  = documentationEl  ? documentationEl.value  : '';
        const tentative      = tentativeEl      ? tentativeEl.value      : '';

        // Clear previous custom filters for this table
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
            return !fn._isLeaveFilter_{{ $status }};
        });

        const filterFn = function(settings, data, dataIndex) {
            if (settings.nTable.id !== '{{ $status }}LeaveRequestsTable') return true;

            const row  = $(tableId).DataTable().row(dataIndex).node();
            const $row = $(row);

            // Leave Type filter
            if (leaveType && String($row.data('leave-type')) !== String(leaveType)) return false;

            // Employee filter (admin side only)
            if (employee && String($row.data('employee')) !== String(employee)) return false;

            // Department filter
            if (department && String($row.data('department')) !== String(department)) return false;

            // Job Category filter
            if (jobCategory && String($row.data('job-category')) !== String(jobCategory)) return false;

            // Date range filter
            if (startDate || endDate) {
                const rowDate = $row.data('start-date');
                if (startDate && rowDate < startDate) return false;
                if (endDate && rowDate > endDate) return false;
            }

            // Days range filter
            if (daysRange) {
                const days = parseFloat($row.data('days'));
                const [min, max] = daysRange.split('-');
                if (max === undefined) {
                    if (days < parseInt(min)) return false;
                } else {
                    if (days < parseInt(min) || days > parseInt(max)) return false;
                }
            }

            // Approval status filter
            if (approvalStatus && $row.data('approval-status') !== approvalStatus) return false;

            // Documentation filter
            if (documentation) {
                const hasAttachment = $row.data('has-attachment') === 'yes';
                const requiresDocs  = $row.data('requires-docs') === 'yes';

                if (documentation === 'required' && !requiresDocs) return false;
                if (documentation === 'attached' && !hasAttachment) return false;
                if (documentation === 'missing' && (!requiresDocs || hasAttachment)) return false;
            }

            // Tentative filter
            if (tentative && $row.data('tentative') !== tentative) return false;

            return true;
        };

        filterFn._isLeaveFilter_{{ $status }} = true;
        $.fn.dataTable.ext.search.push(filterFn);

        dataTable_{{ $status }}.draw();
        updateActiveFilters_{{ $status }}();
    };

    window.resetFilters_{{ $status }} = function() {
        const ids = [
            'filterLeaveType_{{ $status }}',
            'filterEmployee_{{ $status }}',
            'filterDepartment_{{ $status }}',
            'filterJobCategory_{{ $status }}',
            'filterStartDate_{{ $status }}',
            'filterEndDate_{{ $status }}',
            'filterDaysRange_{{ $status }}',
            'filterApprovalStatus_{{ $status }}',
            'filterDocumentation_{{ $status }}',
            'filterTentative_{{ $status }}',
        ];

        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
            return !fn._isLeaveFilter_{{ $status }};
        });

        if (dataTable_{{ $status }}) {
            dataTable_{{ $status }}.draw();
        }
        document.getElementById('activeFilters_{{ $status }}').style.display = 'none';
    };

    function updateActiveFilters_{{ $status }}() {
        const container = document.getElementById('activeFiltersList_{{ $status }}');
        const items     = [];

        function pushIf(elId, labelPrefix) {
            const el = document.getElementById(elId);
            if (el && el.value) {
                const text = el.tagName === 'SELECT'
                    ? el.options[el.selectedIndex].text
                    : el.value;
                items.push(`${labelPrefix}: ${text}`);
            }
        }

        pushIf('filterLeaveType_{{ $status }}',      'Type');
        pushIf('filterEmployee_{{ $status }}',       'Employee');
        pushIf('filterDepartment_{{ $status }}',     'Department');
        pushIf('filterJobCategory_{{ $status }}',    'Job Category');
        pushIf('filterStartDate_{{ $status }}',      'From');
        pushIf('filterEndDate_{{ $status }}',        'To');
        pushIf('filterDaysRange_{{ $status }}',      'Days');
        pushIf('filterApprovalStatus_{{ $status }}', 'Approval');
        pushIf('filterDocumentation_{{ $status }}',  'Docs');
        pushIf('filterTentative_{{ $status }}',      'Tentative');

        if (items.length > 0) {
            container.innerHTML = items
                .map(f => `<span class="badge bg-info">${f}</span>`)
                .join('');
            document.getElementById('activeFilters_{{ $status }}').style.display = 'block';
        } else {
            document.getElementById('activeFilters_{{ $status }}').style.display = 'none';
        }
    }

    // Export Functions
    window.exportLeaveRequests_{{ $status }} = async function(format) {
        const filteredData = [];

        if (dataTable_{{ $status }}) {
            dataTable_{{ $status }}.rows({ search: 'applied' }).every(function() {
                const row  = this.node();
                const data = this.data();
                filteredData.push({
                    ref:        data[0],
                    employee:   data[1],
                    leave_type: data[2],
                    start_date: data[3],
                    days:       data[4],
                    end_date:   data[5],
                    status:     $(row).find('td:eq(7)').text().trim()
                });
            });
        }

        const getVal = id => {
            const el = document.getElementById(id);
            return el ? el.value : '';
        };

        const payload = {
            format: format,
            status: '{{ $status }}',
            data: filteredData,
            filters: {
                leave_type:      getVal('filterLeaveType_{{ $status }}'),
                employee:        getVal('filterEmployee_{{ $status }}'),
                department:      getVal('filterDepartment_{{ $status }}'),
                job_category:    getVal('filterJobCategory_{{ $status }}'),
                start_date:      getVal('filterStartDate_{{ $status }}'),
                end_date:        getVal('filterEndDate_{{ $status }}'),
                days_range:      getVal('filterDaysRange_{{ $status }}'),
                approval_status: getVal('filterApprovalStatus_{{ $status }}'),
                documentation:   getVal('filterDocumentation_{{ $status }}'),
                tentative:       getVal('filterTentative_{{ $status }}'),
            }
        };

        try {
            const response = await fetch(
                @json(route('business.leave.requests.export', $currentBusiness->slug)),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token())
                    },
                    body: JSON.stringify(payload)
                }
            );

            if (!response.ok) throw new Error('Export failed');

            const blob = await response.blob();
            const url  = window.URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = `leave_requests_{{ $status }}_${new Date().getTime()}.${format === 'excel' ? 'xlsx' : 'pdf'}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            if (typeof Swal !== 'undefined') {
                Swal.fire('Success', 'Export completed successfully', 'success');
            }
        } catch (error) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Failed to export data', 'error');
            } else {
                alert('Failed to export data');
            }
        }
    };
})();
</script>
