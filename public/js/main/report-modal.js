// Shared report modal - reused by every module's reports (Attendance,
// Leave, Disciplinary, Performance). One report-type selector -> filter
// bar (only the fields relevant to the selected type are shown) ->
// "Generate Preview" renders the SAME view the download uses into an
// iframe, then Print/Download act on that already-generated result.
window.ReportModal = {
    reportTypes: [],
    employeeOptionsUrl: null,
    cycleOptionsUrl: null,
    courseOptionsUrl: null,
    projectOptionsUrl: null,
    _lastDownloadUrl: null,
    _initialized: false,

    init({ employeeOptionsUrl, cycleOptionsUrl, courseOptionsUrl, projectOptionsUrl }) {
        this.employeeOptionsUrl = employeeOptionsUrl;
        this.cycleOptionsUrl = cycleOptionsUrl || null;
        this.courseOptionsUrl = courseOptionsUrl || null;
        this.projectOptionsUrl = projectOptionsUrl || null;
        if (this._initialized) return;
        this._initialized = true;

        document.getElementById('reportTypeSelect').addEventListener('change', () => this.onReportTypeChange());
        document.getElementById('reportGeneratePreviewBtn').addEventListener('click', () => this.generatePreview());
        document.getElementById('reportBackToFiltersBtn').addEventListener('click', () => this.backToFilters());
        document.getElementById('reportPrintBtn').addEventListener('click', () => this.print());
        document.getElementById('reportDownloadBtn').addEventListener('click', () => this.download());
        document.getElementById('reportModal').addEventListener('show.bs.modal', () => {
            this.loadEmployeeOptions();
            this.loadCycleOptions();
            this.loadCourseOptions();
            this.loadProjectOptions();
        });

        // The theme's global $(".select2-multiple").select2() (assets/js/
        // main.js) already turned this into a widget with no placeholder -
        // re-init with one so the search box isn't just a blank, unlabeled
        // box with no clue what it's for.
        const leaveTypesEl = document.getElementById('reportFilterLeaveTypes');
        if (window.jQuery && leaveTypesEl) {
            if ($(leaveTypesEl).data('select2')) $(leaveTypesEl).select2('destroy');
            $(leaveTypesEl).select2({
                placeholder: 'Search and select leave type(s)… (leave blank for all)',
                allowClear: true,
                width: '100%',
            });
        }
    },

    open(reportTypes, preselectKey) {
        this.reportTypes = reportTypes;
        const select = document.getElementById('reportTypeSelect');
        select.innerHTML = reportTypes.map(rt => `<option value="${rt.key}">${rt.label}</option>`).join('');
        if (preselectKey) select.value = preselectKey;
        document.getElementById('reportTypeGroup').style.display = reportTypes.length > 1 ? '' : 'none';
        this.onReportTypeChange();
        this.resetToFilters();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reportModal')).show();
    },

    /**
     * Renders a card per report type into containerId instead of the page
     * showing nothing but a single "Open Reports" button - each card's
     * button opens the shared modal pre-scoped to that one report, so the
     * available reports are visible (and their purpose is described) at a
     * glance instead of hidden inside a dropdown you only see after
     * already clicking through.
     */
    renderGallery(containerId, reportTypes) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = reportTypes.map(rt => `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border report-gallery-card" style="cursor:pointer;" data-key="${rt.key}">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width:44px; height:44px;">
                                <i class="bi ${rt.icon || 'bi-file-earmark-bar-graph'} fs-5"></i>
                            </span>
                        </div>
                        <h6 class="card-title mb-1">${rt.label}</h6>
                        <p class="card-text small text-muted flex-grow-1">${rt.description || 'Preview, print, or download this report.'}</p>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 align-self-start">
                            <i class="bi bi-eye me-1"></i> Generate
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        container.querySelectorAll('.report-gallery-card').forEach(card => {
            card.addEventListener('click', () => this.open(reportTypes, card.dataset.key));
        });
    },

    currentType() {
        const key = document.getElementById('reportTypeSelect').value;
        return this.reportTypes.find(rt => rt.key === key);
    },

    onReportTypeChange() {
        const type = this.currentType();
        if (!type) return;
        document.querySelectorAll('.report-filter-field').forEach(el => {
            el.style.display = type.filters.includes(el.dataset.field) ? '' : 'none';
        });
    },

    async loadEmployeeOptions() {
        const select = document.getElementById('reportFilterEmployees');
        if (!this.employeeOptionsUrl) return;
        try {
            const resp = await fetch(this.employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const employees = payload.data ?? [];
            select.innerHTML = employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            if (window.jQuery && $(select).data('select2')) $(select).trigger('change');
            else if (window.jQuery) {
                $(select).select2({
                    placeholder: 'Search and select employee(s)… (leave blank for all)',
                    allowClear: true,
                    width: '100%',
                });
            }
        } catch (e) {
            select.innerHTML = '<option value="">Could not load employees</option>';
        }
    },

    async loadCycleOptions() {
        const select = document.getElementById('reportFilterCycle');
        if (!select || !this.cycleOptionsUrl) return;
        try {
            const resp = await fetch(this.cycleOptionsUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const cycles = payload.data ?? [];
            select.innerHTML = '<option value="">Most recent / active cycle</option>'
                + cycles.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        } catch (e) {
            select.innerHTML = '<option value="">Could not load cycles</option>';
        }
    },

    async loadCourseOptions() {
        const select = document.getElementById('reportFilterCourse');
        if (!select || !this.courseOptionsUrl) return;
        try {
            const resp = await fetch(this.courseOptionsUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const courses = payload.data ?? [];
            select.innerHTML = '<option value="">All courses</option>'
                + courses.map(c => `<option value="${c.id}">${c.title}</option>`).join('');
        } catch (e) {
            select.innerHTML = '<option value="">Could not load courses</option>';
        }
    },

    async loadProjectOptions() {
        const select = document.getElementById('reportFilterProject');
        if (!select || !this.projectOptionsUrl) return;
        try {
            const resp = await fetch(this.projectOptionsUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const projects = payload.data ?? [];
            select.innerHTML = '<option value="">All projects</option>'
                + projects.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
        } catch (e) {
            select.innerHTML = '<option value="">Could not load projects</option>';
        }
    },

    collectFilters() {
        const params = new URLSearchParams();
        // Only ever read/send a field's value if it's actually one of the
        // CURRENT report type's declared filters - every field keeps
        // whatever value it last had even while hidden (e.g. the date
        // field defaults to today and is never cleared), so reading them
        // unconditionally meant a hidden 'date' silently overrode a
        // visible, user-picked date_range on every report that isn't a
        // single-day one: the server prioritizes 'date' over
        // start_date/end_date whenever it's present (see
        // ReportFilters::fromRequest()).
        const type = this.currentType();
        const activeFilters = type ? type.filters : [];
        const isActive = (field) => activeFilters.includes(field);

        const date = isActive('date') ? document.getElementById('reportFilterDate').value : '';
        const startDate = isActive('date_range') ? document.getElementById('reportFilterStartDate').value : '';
        const endDate = isActive('date_range') ? document.getElementById('reportFilterEndDate').value : '';
        const department = isActive('department') ? document.getElementById('reportFilterDepartment').value : '';
        const jobCategory = isActive('job_category') ? document.getElementById('reportFilterJobCategory').value : '';
        const assetStatusEl = document.getElementById('reportFilterAssetStatus');
        const assetStatus = (isActive('asset_status') && assetStatusEl) ? assetStatusEl.value : '';
        const employeeSelect = document.getElementById('reportFilterEmployees');
        const employees = isActive('employee')
            ? (window.jQuery ? ($(employeeSelect).val() || []) : Array.from(employeeSelect.selectedOptions).map(o => o.value))
            : [];
        const leavePeriodEl = document.getElementById('reportFilterLeavePeriod');
        const leaveTypesEl = document.getElementById('reportFilterLeaveTypes');
        const leavePeriod = (isActive('leave_period') && leavePeriodEl) ? leavePeriodEl.value : '';
        const leaveTypes = (isActive('leave_type') && leaveTypesEl)
            ? (window.jQuery ? ($(leaveTypesEl).val() || []) : Array.from(leaveTypesEl.selectedOptions).map(o => o.value))
            : [];
        const cycleEl = document.getElementById('reportFilterCycle');
        const cycle = (isActive('cycle') && cycleEl) ? cycleEl.value : '';
        const courseEl = document.getElementById('reportFilterCourse');
        const course = (isActive('course') && courseEl) ? courseEl.value : '';
        const projectEl = document.getElementById('reportFilterProject');
        const project = (isActive('project') && projectEl) ? projectEl.value : '';

        if (date) params.set('date', date);
        if (startDate) params.set('start_date', startDate);
        if (endDate) params.set('end_date', endDate);
        if (department) params.set('department_id', department);
        if (jobCategory) params.set('job_category_id', jobCategory);
        if (assetStatus) params.set('status', assetStatus);
        if (leavePeriod) params.set('leave_period_id', leavePeriod);
        if (cycle) params.set('cycle_id', cycle);
        if (course) params.set('course_id', course);
        if (project) params.set('project_id', project);
        leaveTypes.filter(Boolean).forEach(id => params.append('leave_type_ids[]', id));
        employees.filter(Boolean).forEach(id => params.append('employee_ids[]', id));

        return params;
    },

    async generatePreview() {
        const type = this.currentType();
        if (!type) return;
        const btn = document.getElementById('reportGeneratePreviewBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';

        try {
            const params = this.collectFilters();
            const resp = await fetch(`${type.previewUrl}?${params.toString()}`, { headers: { 'Accept': 'text/html' } });
            const html = await resp.text();
            if (!resp.ok) throw new Error('Could not generate preview.');

            document.getElementById('reportPreviewFrame').srcdoc = html;
            document.getElementById('reportModalFilters').classList.add('d-none');
            document.getElementById('reportModalPreview').classList.remove('d-none');
            btn.classList.add('d-none');
            document.getElementById('reportBackToFiltersBtn').classList.remove('d-none');
            document.getElementById('reportPrintBtn').classList.remove('d-none');
            document.getElementById('reportDownloadBtn').classList.remove('d-none');
            this._lastDownloadUrl = `${type.downloadUrl}?${params.toString()}`;
        } catch (e) {
            console.error(e);
            if (window.toastr) toastr.error(e.message || 'Could not generate preview.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-eye me-1"></i> Generate Preview';
        }
    },

    backToFilters() {
        document.getElementById('reportModalFilters').classList.remove('d-none');
        document.getElementById('reportModalPreview').classList.add('d-none');
        document.getElementById('reportGeneratePreviewBtn').classList.remove('d-none');
        document.getElementById('reportBackToFiltersBtn').classList.add('d-none');
        document.getElementById('reportPrintBtn').classList.add('d-none');
        document.getElementById('reportDownloadBtn').classList.add('d-none');
    },

    resetToFilters() {
        document.getElementById('reportPreviewFrame').srcdoc = '';
        this.backToFilters();
    },

    print() {
        const frame = document.getElementById('reportPreviewFrame');
        frame.contentWindow.focus();
        frame.contentWindow.print();
    },

    download() {
        if (this._lastDownloadUrl) window.location.href = this._lastDownloadUrl;
    },
};
