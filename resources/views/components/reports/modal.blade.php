
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalTitle">Reports</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div id="reportModalFilters">
                    <div class="mb-3" id="reportTypeGroup">
                        <label class="form-label">Report</label>
                        <select id="reportTypeSelect" class="form-select"></select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 report-filter-field" data-field="date">
                            <label class="form-label small text-muted mb-1">Date</label>
                            <input type="date" class="form-control" id="reportFilterDate" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="date_range">
                            <label class="form-label small text-muted mb-1">Period</label>
                            <div class="d-flex gap-1">
                                <input type="date" class="form-control" id="reportFilterStartDate">
                                <input type="date" class="form-control" id="reportFilterEndDate">
                            </div>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="department">
                            <label class="form-label small text-muted mb-1">Department</label>
                            <select class="form-select" id="reportFilterDepartment">
                                <option value="">All departments</option>
                                @foreach(($departments ?? []) as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="asset_status">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select class="form-select" id="reportFilterAssetStatus">
                                <option value="">All statuses</option>
                                <option value="available">Available</option>
                                <option value="assigned">Assigned</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="job_category">
                            <label class="form-label small text-muted mb-1">Job Category</label>
                            <select class="form-select" id="reportFilterJobCategory">
                                <option value="">All job categories</option>
                                @foreach(($jobCategories ?? []) as $jobCategory)
                                    <option value="{{ $jobCategory->id }}">{{ $jobCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="leave_period">
                            <label class="form-label small text-muted mb-1">Leave Period</label>
                            <select class="form-select" id="reportFilterLeavePeriod">
                                <option value="">Most recent / active period</option>
                                @foreach(($leavePeriods ?? []) as $leavePeriod)
                                    <option value="{{ $leavePeriod->id }}">{{ $leavePeriod->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="leave_type">
                            <label class="form-label small text-muted mb-1">Leave Type(s)</label>

                            <select class="form-select" id="reportFilterLeaveTypes" multiple>
                                <option value="">All leave types</option>
                                @foreach(($leaveTypes ?? []) as $leaveType)
                                    <option value="{{ $leaveType->id }}">{{ $leaveType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="cycle">
                            <label class="form-label small text-muted mb-1">Performance Cycle</label>
                            <select class="form-select" id="reportFilterCycle">
                                <option value="">Most recent / active cycle</option>
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="course">
                            <label class="form-label small text-muted mb-1">Course</label>
                            <select class="form-select" id="reportFilterCourse">
                                <option value="">All courses</option>
                            </select>
                        </div>
                        <div class="col-md-6 report-filter-field" data-field="project">
                            <label class="form-label small text-muted mb-1">Project</label>
                            <select class="form-select" id="reportFilterProject">
                                <option value="">All projects</option>
                            </select>
                        </div>
                        <div class="col-12 report-filter-field" data-field="employee">
                            <label class="form-label small text-muted mb-1">Employee(s)</label>

                            <select class="form-select" id="reportFilterEmployees" multiple>
                                <option value="">Loading employees…</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="reportModalPreview" class="d-none">
                    <iframe id="reportPreviewFrame" style="width:100%; height:60vh; border:1px solid #dee2e6; border-radius:.375rem; background:#fff;"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-secondary d-none" id="reportBackToFiltersBtn">
                    <i class="bi bi-arrow-left me-1"></i> Back to Filters
                </button>
                <button type="button" class="btn btn-primary" id="reportGeneratePreviewBtn">
                    <i class="bi bi-eye me-1"></i> Generate Preview
                </button>
                <button type="button" class="btn btn-outline-primary d-none" id="reportPrintBtn">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
                <button type="button" class="btn btn-success d-none" id="reportDownloadBtn">
                    <i class="bi bi-download me-1"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>
