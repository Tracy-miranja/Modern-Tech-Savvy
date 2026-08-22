<x-app-layout title='{{ $page }}'>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold text-dark mb-0">{{ $page }}</h2>
                        <p class="text-muted small mb-0">{{ $description ?? '' }}</p>
                    </div>
                    <span id="warningCount" class="badge bg-primary-soft text-primary px-3 py-2">{{ $warnings->count() }} Warnings</span>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="disciplinaryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-configure-btn" data-bs-toggle="tab" data-bs-target="#tab-configure" type="button" role="tab">
                            <i class="fa fa-sliders-h me-1"></i> Configure
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-cases-btn" data-bs-toggle="tab" data-bs-target="#tab-cases" type="button" role="tab">
                            <i class="fa fa-folder-open me-1"></i> Cases
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-warnings-btn" data-bs-toggle="tab" data-bs-target="#tab-warnings" type="button" role="tab">
                            <i class="fa fa-exclamation-triangle me-1"></i> Warnings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-reports-btn" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab">
                            <i class="fa fa-chart-bar me-1"></i> Reports
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="disciplinaryTabsContent">

                    {{-- Configure: business-configurable disciplinary stages --}}
                    <div class="tab-pane fade" id="tab-configure" role="tabpanel">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body p-4">
                                <h5 class="fw-semibold mb-1">Disciplinary Stages</h5>
                                <p class="text-muted small">
                                    These are your business's own disciplinary stages, in order. Use the arrows to reorder,
                                    toggle <strong>Terminal</strong> for a stage that ends the process (e.g. Termination - you
                                    must always keep at least one), and <strong>Requires Response</strong> for a stage where the
                                    employee must submit a written response (Show Cause).
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width:70px;">Order</th>
                                                <th>Name</th>
                                                <th class="text-center">Terminal</th>
                                                <th class="text-center">Requires Response</th>
                                                <th class="text-center">Disciplinary Case</th>
                                                <th class="text-center">Warnings</th>
                                                <th class="text-center">Active</th>
                                                <th style="width:70px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stageTypesTableBody">
                                            <tr><td colspan="8" class="text-center text-muted">Loading…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <h6 class="fw-semibold">Add a Stage</h6>
                                <form id="addStageForm" class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small">Name</label>
                                        <input type="text" class="form-control form-control-sm" id="newStageName" required>
                                    </div>
                                    <div class="col-md-3 form-check ms-2 mt-4">
                                        <input class="form-check-input" type="checkbox" id="newStageTerminal">
                                        <label class="form-check-label small" for="newStageTerminal">Terminal</label>
                                    </div>
                                    <div class="col-md-3 form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="newStageRequiresResponse">
                                        <label class="form-check-label small" for="newStageRequiresResponse">Requires Response</label>
                                    </div>
                                    <div class="col-md-3 form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="newStageIsDisciplinaryCase">
                                        <label class="form-check-label small" for="newStageIsDisciplinaryCase">Counts as a Disciplinary Case (not just a warning)</label>
                                    </div>
                                    <div class="col-md-2 mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Cases: capture a new disciplinary case and manage it through to resolution --}}
                    <div class="tab-pane fade show active" id="tab-cases" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-semibold mb-0">Disciplinary Cases</h5>
                                <p class="text-muted small mb-0">Not every warning is a case - only those that have reached a stage flagged "Disciplinary Case" on the Configure tab. Capture a new one and manage it - show cause, investigation, minutes, escalation - through to resolution.</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-sm" role="group" aria-label="View">
                                    <button type="button" class="btn btn-outline-secondary active" data-list="cases" data-view="grid" onclick="setWarningListView('cases', 'grid')"><i class="bi bi-grid-3x3-gap"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-list="cases" data-view="list" onclick="setWarningListView('cases', 'list')"><i class="bi bi-list-ul"></i></button>
                                </div>
                                <button type="button" class="btn btn-primary btn-modern" onclick="newWarningRecord()">
                                    <i class="fa fa-plus me-1"></i> Capture New Case
                                </button>
                            </div>
                        </div>
                        <div class="row g-4 warnings-list-container" id="casesListContainer">
                            @include('employees.warning._cards')
                        </div>
                        <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0" id="casesListPagination"></ul></nav>
                    </div>

                    {{-- Warnings: every warning on record, cases or not (a warning can emanate from a recorded case) --}}
                    <div class="tab-pane fade" id="tab-warnings" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-semibold mb-0">Warnings Issued</h5>
                                <p class="text-muted small mb-0">Every warning on record, whether or not it has escalated into a full disciplinary case.</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-sm" role="group" aria-label="View">
                                    <button type="button" class="btn btn-outline-secondary active" data-list="warnings" data-view="grid" onclick="setWarningListView('warnings', 'grid')"><i class="bi bi-grid-3x3-gap"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-list="warnings" data-view="list" onclick="setWarningListView('warnings', 'list')"><i class="bi bi-list-ul"></i></button>
                                </div>
                                <button type="button" class="btn btn-primary btn-modern" onclick="newWarningRecord()">
                                    <i class="fa fa-plus me-1"></i> Issue New Warning
                                </button>
                            </div>
                        </div>
                        <div class="row g-4 warnings-list-container" id="warningsListContainer">
                            @include('employees.warning._cards')
                        </div>
                        <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0" id="warningsListPagination"></ul></nav>
                    </div>

                    {{-- Reports --}}
                    <div class="tab-pane fade" id="tab-reports" role="tabpanel">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body p-4">
                                <h5 class="fw-semibold mb-1">Disciplinary Reports</h5>
                                <p class="text-muted small mb-3">Pick a report below to filter, preview, print, or download it.</p>
                                <div class="row g-3" id="disciplinaryReportsGallery"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Shared warning form modal - used to capture a new case (Cases tab) or issue a warning (Warnings tab) --}}
    <div class="modal fade" id="warningFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warningFormModalTitle">Issue Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="warningFormContainer">
                    @include('employees.warning._form')
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? []])

    @push('styles')
    <style>
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .bg-primary-soft {
        background-color: #e7f1ff;
    }

    .btn-modern {
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        box-shadow: none;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .warning-card {
        width: 100%;
        box-sizing: border-box;
    }

    .row.g-4 > .col-lg-3,
    .row.g-4 > .col-md-4,
    .row.g-4 > .col-sm-6 {
        padding: 0 15px;
    }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/warnings.js') }}" type="module"></script>
    <script src="{{ asset('js/main/disciplinary-configure.js') }}"></script>
    <script src="{{ asset('js/main/report-modal.js') }}"></script>
    <script>
        ReportModal.init({
            employeeOptionsUrl: @json(route('business.organogram.employee-options', $currentBusiness->slug)),
        });

        ReportModal.renderGallery('disciplinaryReportsGallery', [
            {
                key: 'cases',
                label: 'Disciplinary Cases Report',
                icon: 'bi-folder2-open',
                description: 'Every disciplinary case in the selected period, its stage, and current status.',
                filters: ['date_range', 'department', 'job_category', 'employee'],
                previewUrl: @json(route('business.disciplinary.reports.cases.preview', $currentBusiness->slug)),
                downloadUrl: @json(route('business.disciplinary.reports.cases.download', $currentBusiness->slug)),
            },
        ]);
    </script>
    @endpush
</x-app-layout>
