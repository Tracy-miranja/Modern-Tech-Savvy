<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Employee Objectives</h5>
                            <small class="text-muted">Every employee's objectives at a glance - progress, deliveries, and anything at risk. Click Manage to set or update an employee's own objectives, KPIs, and reviews.</small>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="openObjectivesReportBtn">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> Preview / Print / Download
                        </button>
                    </div>

                    <div class="row g-2 mb-3 p-2 px-3 rounded-3" style="background:#f8fafc; border:1px solid #e1e5ea;">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Search employee</label>
                            <input type="search" class="form-control form-control-sm" id="objSearchInput" placeholder="Search by employee name…">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Department</label>
                            <select class="form-select form-select-sm" id="objDepartmentFilter">
                                <option value="">All departments</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Job Category</label>
                            <select class="form-select form-select-sm" id="objJobCategoryFilter">
                                <option value="">All job categories</option>
                                @foreach ($jobCategories as $jobCategory)
                                    <option value="{{ $jobCategory->id }}">{{ $jobCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="objClearFiltersBtn">Clear</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Objectives</th>
                                    <th style="width:220px;">Average Progress</th>
                                    <th>Needs Attention</th>
                                    <th style="width:110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="objTableBody">
                                <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0" id="objPagination"></ul></nav>
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.modal', ['departments' => $departments ?? [], 'jobCategories' => $jobCategories ?? []])

    @push('scripts')
    <script src="{{ asset('js/main/report-modal.js') }}"></script>
    <script>
    (function () {
        const fetchUrl = @json(route('business.performance.objectives.overview-fetch', $business->slug));
        const employeeUrlTemplate = @json(route('business.performance.employee', ['business' => $business->slug, 'employee' => '__ID__']));

        let currentPage = 1;
        let searchDebounce = null;

        function progressBar(value) {
            if (value === null) return '<span class="text-muted small">No objectives</span>';
            const color = value >= 80 ? 'bg-success' : (value >= 50 ? 'bg-info' : (value >= 30 ? 'bg-warning' : 'bg-danger'));
            return `
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:8px;">
                        <div class="progress-bar ${color}" style="width:${value}%"></div>
                    </div>
                    <span class="small text-muted">${value}%</span>
                </div>`;
        }

        function paginationHtml(current, last) {
            if (!last || last <= 1) return '';
            return `
                <li class="page-item ${current <= 1 ? 'disabled' : ''}">
                    <button type="button" class="page-link" onclick="window.__objGoToPage(${current - 1})">Prev</button>
                </li>
                <li class="page-item disabled"><span class="page-link">${current} / ${last}</span></li>
                <li class="page-item ${current >= last ? 'disabled' : ''}">
                    <button type="button" class="page-link" onclick="window.__objGoToPage(${current + 1})">Next</button>
                </li>`;
        }

        window.__objGoToPage = function (page) {
            if (page < 1) return;
            currentPage = page;
            loadRows();
        };

        async function loadRows() {
            const tbody = document.getElementById('objTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>';

            const params = new URLSearchParams();
            params.set('page', currentPage);
            const search = document.getElementById('objSearchInput').value.trim();
            const department = document.getElementById('objDepartmentFilter').value;
            const jobCategory = document.getElementById('objJobCategoryFilter').value;
            if (search) params.set('search', search);
            if (department) params.set('department_id', department);
            if (jobCategory) params.set('job_category_id', jobCategory);

            try {
                const resp = await fetch(`${fetchUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const rows = payload.data?.rows ?? [];

                tbody.innerHTML = rows.length ? rows.map(r => `
                    <tr>
                        <td>${r.name}</td>
                        <td>${r.department}</td>
                        <td>${r.objectives_count}</td>
                        <td>${progressBar(r.avg_progress)}</td>
                        <td>${r.critical_count > 0 ? `<span class="badge bg-danger-subtle text-danger">${r.critical_count} at risk</span>` : '<span class="text-muted small">—</span>'}</td>
                        <td>
                            <a href="${employeeUrlTemplate.replace('__ID__', r.employee_id)}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-check me-1"></i> Manage
                            </a>
                        </td>
                    </tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted py-4">No employees found.</td></tr>';

                document.getElementById('objPagination').innerHTML = paginationHtml(payload.data?.current_page, payload.data?.last_page);
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load objectives.</td></tr>';
            }
        }

        document.getElementById('objSearchInput').addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => { currentPage = 1; loadRows(); }, 350);
        });
        document.getElementById('objDepartmentFilter').addEventListener('change', function () { currentPage = 1; loadRows(); });
        document.getElementById('objJobCategoryFilter').addEventListener('change', function () { currentPage = 1; loadRows(); });
        document.getElementById('objClearFiltersBtn').addEventListener('click', function () {
            document.getElementById('objSearchInput').value = '';
            document.getElementById('objDepartmentFilter').value = '';
            document.getElementById('objJobCategoryFilter').value = '';
            currentPage = 1;
            loadRows();
        });

        ReportModal.init({
            employeeOptionsUrl: @json(route('business.organogram.employee-options', $business->slug)),
            cycleOptionsUrl: @json(route('business.performance.cycles.fetch', $business->slug)),
        });
        document.getElementById('openObjectivesReportBtn').addEventListener('click', function () {
            ReportModal.open([
                {
                    key: 'cycle',
                    label: 'Performance Cycle Report',
                    filters: ['cycle', 'department', 'job_category', 'employee'],
                    previewUrl: @json(route('business.performance.reports.cycle.preview', $business->slug)),
                    downloadUrl: @json(route('business.performance.reports.cycle.download', $business->slug)),
                },
            ]);
        });

        loadRows();
    })();
    </script>
    @endpush
</x-app-layout>
