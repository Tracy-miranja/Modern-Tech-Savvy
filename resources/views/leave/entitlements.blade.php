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
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#exportEntitlementsPdfModal">
                            <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF
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

   @push('scripts')
<script src="{{ asset('js/main/leave-entitlement.js') }}" type="module"></script>
<script>
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

    window.businessSlug = '{{ request()->route('business') }}'; // Should be 'krest'
    console.log('Business Slug set to:', window.businessSlug);
</script>
@endpush
</x-app-layout>
