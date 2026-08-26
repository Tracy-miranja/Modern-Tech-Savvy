<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Organogram</h5>
                            <small class="text-muted">
                                Extends the <a href="{{ route('business.organization-structure.index', $business->slug) }}">Organization Structure</a> -
                                employees default to reporting per their department + role, but can be
                                individually moved to a different line without affecting anyone else.
                            </small>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="org-view-switch">
                                <button type="button" id="treeViewBtn">Tree View</button>
                                <button type="button" class="active" id="tableViewBtn">Table View</button>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignManagerModal">
                                <i class="bi bi-diagram-3 me-1"></i> Assign Manager
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-2 px-3 rounded-3" style="background:#f8fafc; border:1px solid var(--org-border, #e1e5ea);">
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <div class="position-relative">
                                <div class="input-group input-group-sm" style="max-width:280px;">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="search" class="form-control border-start-0" id="orgSearchInput" placeholder="Search by employee name…">
                                </div>
                                <div id="orgSearchResults" class="list-group mb-2 d-none" style="max-width:280px; position:absolute; z-index:5;"></div>
                            </div>
                            <select id="orgExecutiveFilter" class="form-select form-select-sm" style="max-width:220px;">
                                <option value="">Filter: all executive owners</option>
                            </select>
                            <select id="orgDepartmentFilter" class="form-select form-select-sm" style="max-width:220px;">
                                <option value="">Filter: all departments</option>
                            </select>
                            <span class="d-flex align-items-center gap-1 small text-muted ms-1">
                                <span class="org-legend-swatch org-legend-manual"></span> Manually pinned
                            </span>
                        </div>
                        <div class="d-flex gap-2 d-none" id="orgTreeToolbar">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="orgExpandAllBtn"><i class="bi bi-arrows-expand me-1"></i>Expand all</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="orgCollapseAllBtn"><i class="bi bi-arrows-collapse me-1"></i>Collapse all</button>
                        </div>
                    </div>

                    <div id="orgChartContainer" class="org-chart-scroll d-none">
                        <div id="orgChartFitBox" class="org-chart-fit-box">
                            <div id="orgChartScale" class="org-chart-scale">
                                <div class="text-center text-muted py-4">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Loading organogram…
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="orgTableContainer" class="table-responsive">
                        <table class="table table-hover table-sm align-middle" id="organogramTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Employee / Position</th>
                                    <th>Job Category</th>
                                    <th>Department</th>
                                    <th>Line Manager</th>
                                    <th>HOD</th>
                                    <th>Executive Owner</th>
                                    <th>Functional Manager</th>
                                    <th>Relationship</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/org-chart.css') }}">
    @endpush

    <div class="modal fade" id="assignManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignManagerForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" id="assignEmployeeSelect" class="form-select" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reports To (Manager)</label>
                            <select name="manager_id" id="assignManagerSelect" class="form-select">
                                <option value="">— None (top of the chart) —</option>
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Functional Manager (optional, dotted-line)</label>
                            <select name="functional_manager_id" id="assignFunctionalManagerSelect" class="form-select">
                                <option value="">— None —</option>
                            </select>
                            <small class="text-muted">A secondary/matrix manager for professional or technical oversight - doesn't affect the org chart's reporting line.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAssignManagerBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="orgDetailPanel" id="orgDetailPanel">
        <div class="orgDetailPanelHeader">
            <h2 id="orgDetailPanelTitle">Details</h2>
            <button type="button" class="orgDetailPanelClose" id="orgDetailPanelClose">Close</button>
        </div>
        <div id="orgDetailPanelBody"></div>
    </div>
    <div class="orgDetailPanelOverlay" id="orgDetailPanelOverlay"></div>

    <script>
    (function () {
        const businessSlug = @json($business->slug);
        const fetchUrl = @json(route('business.organogram.fetch', ['business' => $business->slug]));
        const tableUrl = @json(route('business.organogram.table', ['business' => $business->slug]));
        const searchUrl = @json(route('business.organogram.search', ['business' => $business->slug]));
        const filterOptionsUrl = @json(route('business.organogram.filter-options', ['business' => $business->slug]));
        const optionsUrl = @json(route('business.organogram.employee-options', ['business' => $business->slug]));
        const assignUrl = @json(route('business.organogram.assign-manager', ['business' => $business->slug]));
        const resetUrl = @json(route('business.organogram.reset-manager', ['business' => $business->slug]));
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        // Children fetched from the server are cached per node id so
        // collapsing/re-expanding a branch (or landing on it again via
        // search) doesn't re-hit the server every time.
        const childrenCache = new Map();
        const nodeDataCache = new Map();
        let tableDataTable = null;

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        }

        /* -----------------------------------------------------------
           Detail side panel - populated from data already fetched for
           the tree node / table row that was clicked, no extra request.
        ----------------------------------------------------------- */
        function openDetailPanel(node) {
            document.getElementById('orgDetailPanelTitle').textContent = node.name;

            const relation = (label, value) => `
                <div class="orgDetailRelation">
                    <span>${label}</span>
                    <b>${value ? escapeHtml(value) : '—'}</b>
                </div>`;

            let logic = `<p>This employee is classified under <b>${escapeHtml(node.job_category || 'no job category')}</b> as a job category`;
            logic += node.department ? ` but is assigned to <b>${escapeHtml(node.department)}</b> as the operational department.</p>` : '.</p>';
            if (node.functional_manager_name) {
                logic += `<p>Functional/technical oversight is provided by <b>${escapeHtml(node.functional_manager_name)}</b>.</p>`;
            }

            document.getElementById('orgDetailPanelBody').innerHTML = `
                <div class="orgDetail">
                    <label>Position</label>
                    <strong>${escapeHtml(node.organogram_role || '—')}</strong>
                </div>
                <div class="orgDetail">
                    <label>Job Category</label>
                    <strong>${escapeHtml(node.job_category || '—')}</strong>
                </div>
                <div class="orgDetail">
                    <label>Department</label>
                    <strong>${escapeHtml(node.department || '—')}</strong>
                </div>
                <div class="orgDetail">
                    <label>Reporting Structure</label>
                    ${relation('Line Manager', node.manager_name)}
                    ${relation('Department HOD', node.hod)}
                    ${relation('Executive Owner', node.executive_owner)}
                    ${relation('Functional Manager', node.functional_manager_name)}
                </div>
                <div class="orgDetail">
                    <label>Organization Logic</label>
                    ${logic}
                </div>
            `;

            document.getElementById('orgDetailPanel').classList.add('open');
            document.getElementById('orgDetailPanelOverlay').classList.add('open');
        }

        function closeDetailPanel() {
            document.getElementById('orgDetailPanel').classList.remove('open');
            document.getElementById('orgDetailPanelOverlay').classList.remove('open');
        }

        document.getElementById('orgDetailPanelClose').addEventListener('click', closeDetailPanel);
        document.getElementById('orgDetailPanelOverlay').addEventListener('click', closeDetailPanel);

        /* -----------------------------------------------------------
           Tree view
        ----------------------------------------------------------- */
        function nodeBoxHtml(node) {
            nodeDataCache.set(String(node.id), node);

            const name = escapeHtml(node.name);
            const code = escapeHtml(node.employee_code ?? '');
            const department = node.department ? escapeHtml(node.department) : null;
            const role = node.organogram_role ? escapeHtml(node.organogram_role) : null;
            const jobCategory = node.job_category ? escapeHtml(node.job_category) : null;
            const toggle = node.has_children
                ? `<i class="bi bi-caret-right-fill org-node-toggle" data-employee-id="${node.id}" title="${node.direct_reports_count} direct report(s) - click to expand"></i>`
                : '';

            return `
                <div class="org-node-box" data-employee-id="${node.id}">
                    <div class="org-node-header">
                        <span class="org-node-title">${toggle}<i class="bi bi-person-circle me-1"></i>${name}</span>
                        ${node.manager_override ? `<span class="badge bg-warning text-dark reset-manager-btn" style="cursor:pointer" data-employee-id="${node.id}" title="Click to reset to structure default">Manual</span>` : ''}
                    </div>
                    ${code ? `<div class="org-position-coverage mb-1">${code}</div>` : ''}
                    <div class="d-flex flex-wrap gap-1">
                        ${department ? `<span class="badge bg-light text-dark border">${department}</span>` : ''}
                        ${role ? `<span class="badge bg-info text-dark">${role}</span>` : ''}
                        ${jobCategory ? `<span class="badge org-tag-blue">${jobCategory}</span>` : ''}
                        ${node.is_cross_functional ? `<span class="badge org-tag-purple">Cross-functional</span>` : ''}
                    </div>
                </div>`;
        }

        function renderNode(node) {
            return `<li id="org-li-${node.id}">${nodeBoxHtml(node)}</li>`;
        }

        function bindNodeHandlers(scope) {
            scope.querySelectorAll('.org-node-toggle').forEach(el => {
                el.addEventListener('click', function (e) {
                    e.stopPropagation();
                    toggleNode(this.dataset.employeeId, this);
                });
            });

            scope.querySelectorAll('.reset-manager-btn').forEach(el => {
                el.addEventListener('click', async function (e) {
                    e.stopPropagation();
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Reset this employee to the organization structure default?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    try {
                        const resp = await fetch(resetUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ employee_id: this.dataset.employeeId }),
                        });
                        const payload = await resp.json();
                        if (!resp.ok) { toastr.error(payload.message || 'Could not reset.'); return; }
                        toastr.success('Reset to structure default.');
                        childrenCache.clear();
                        loadChart();
                    } catch (e) {
                        console.error(e);
                        toastr.error('Could not reset.');
                    }
                });
            });

            scope.querySelectorAll('.org-node-box').forEach(el => {
                el.addEventListener('click', function (e) {
                    if (e.target.closest('.org-node-toggle') || e.target.closest('.reset-manager-btn')) return;
                    const node = nodeDataCache.get(this.dataset.employeeId);
                    if (node) openDetailPanel(node);
                });
            });
        }

        async function fetchChildren(employeeId) {
            if (childrenCache.has(employeeId)) return childrenCache.get(employeeId);
            const resp = await fetch(`${fetchUrl}?parent_id=${employeeId}`, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const children = payload.data ?? [];
            childrenCache.set(employeeId, children);
            return children;
        }

        /**
         * Expands a node in place: fetches (or reuses cached) direct
         * reports and inserts them as a nested <ul>, exactly what the old
         * "load everything up front" version pre-built for every node -
         * just deferred until the user actually asks for that branch.
         */
        async function expandNode(employeeId) {
            const li = document.getElementById(`org-li-${employeeId}`);
            if (!li || li.querySelector(':scope > ul')) return []; // already expanded

            const children = await fetchChildren(employeeId);
            if (!children.length) return [];

            const ul = document.createElement('ul');
            ul.innerHTML = children.map(renderNode).join('');
            li.appendChild(ul);
            bindNodeHandlers(ul);
            return children;
        }

        function collapseNode(employeeId) {
            const li = document.getElementById(`org-li-${employeeId}`);
            const ul = li && li.querySelector(':scope > ul');
            if (ul) ul.remove();
        }

        async function toggleNode(employeeId, toggleEl) {
            const li = document.getElementById(`org-li-${employeeId}`);
            const isExpanded = li && li.querySelector(':scope > ul');

            if (isExpanded) {
                collapseNode(employeeId);
                toggleEl.classList.remove('bi-caret-down-fill');
                toggleEl.classList.add('bi-caret-right-fill');
            } else {
                await expandNode(employeeId);
                toggleEl.classList.remove('bi-caret-right-fill');
                toggleEl.classList.add('bi-caret-down-fill');
            }

            fitChartToContainer();
        }

        let treeRoots = [];

        function fitChartToContainer() {
            const viewport = document.getElementById('orgChartContainer');
            const fitBox = document.getElementById('orgChartFitBox');
            const scaleEl = document.getElementById('orgChartScale');
            if (!viewport || !fitBox || !scaleEl) return;

            scaleEl.style.transform = 'none';
            fitBox.style.width = 'auto';
            fitBox.style.height = 'auto';

            const contentWidth = scaleEl.scrollWidth;
            const contentHeight = scaleEl.scrollHeight;
            const availableWidth = viewport.clientWidth - 32;
            const availableHeight = viewport.clientHeight - 32;
            if (!contentWidth || !contentHeight || availableWidth <= 0 || availableHeight <= 0) return;

            const scale = Math.max(Math.min(1, availableWidth / contentWidth, availableHeight / contentHeight), 0.45);

            scaleEl.style.transform = `scale(${scale})`;
            fitBox.style.width = `${contentWidth * scale}px`;
            fitBox.style.height = `${contentHeight * scale}px`;
        }

        async function loadChart() {
            const container = document.getElementById('orgChartContainer');
            const scaleEl = document.getElementById('orgChartScale');
            try {
                const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const roots = payload.data ?? payload;
                treeRoots = roots ?? [];

                if (!roots || !roots.length) {
                    scaleEl.innerHTML = '<p class="text-muted">No employees found for this business yet.</p>';
                    return;
                }

                scaleEl.innerHTML = `<ul class="org-chart">${roots.map(renderNode).join('')}</ul>`;
                bindNodeHandlers(container);
                fitChartToContainer();
            } catch (e) {
                console.error(e);
                scaleEl.innerHTML = '<p class="text-danger">Could not load the organogram.</p>';
            }
        }

        /**
         * Expands every ancestor along the path to a search match (fetching
         * each branch on demand, same as a manual click would), then
         * scrolls to and briefly highlights the match.
         */
        async function revealEmployee(employeeId, ancestorIds) {
            for (const ancestorId of ancestorIds) {
                await expandNode(ancestorId);
                const toggle = document.querySelector(`.org-node-toggle[data-employee-id="${ancestorId}"]`);
                if (toggle) { toggle.classList.remove('bi-caret-right-fill'); toggle.classList.add('bi-caret-down-fill'); }
            }

            fitChartToContainer();

            const box = document.querySelector(`.org-node-box[data-employee-id="${employeeId}"]`);
            if (!box) return;
            box.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            box.classList.add('org-node-highlight');
            setTimeout(() => box.classList.remove('org-node-highlight'), 2500);
        }

        async function runQuery(params) {
            const resultsBox = document.getElementById('orgSearchResults');
            if (!params.toString()) { resultsBox.classList.add('d-none'); return; }

            const resp = await fetch(`${searchUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const results = payload.data ?? [];

            if (!results.length) {
                resultsBox.classList.remove('d-none');
                resultsBox.innerHTML = '<span class="list-group-item text-muted small">No matches.</span>';
                return;
            }

            resultsBox.classList.remove('d-none');
            resultsBox.innerHTML = results.map(r => `
                <button type="button" class="list-group-item list-group-item-action small" data-employee-id="${r.id}" data-ancestor-ids="${r.ancestor_ids.join(',')}">
                    ${escapeHtml(r.name)}
                </button>`).join('');

            resultsBox.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function () {
                    resultsBox.classList.add('d-none');
                    const ancestorIds = this.dataset.ancestorIds ? this.dataset.ancestorIds.split(',') : [];
                    revealEmployee(this.dataset.employeeId, ancestorIds);
                });
            });
        }

        function currentFilterParams(q) {
            const params = new URLSearchParams();
            if (q) params.set('q', q);
            const dept = document.getElementById('orgDepartmentFilter').value;
            const exec = document.getElementById('orgExecutiveFilter').value;
            if (dept) params.set('department_id', dept);
            if (exec) params.set('executive_role_id', exec);
            return params;
        }

        function runSearch() {
            const q = document.getElementById('orgSearchInput').value.trim();
            runQuery(currentFilterParams(q));
        }

        document.getElementById('orgSearchInput').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); runSearch(); }
        });
        document.getElementById('orgExecutiveFilter').addEventListener('change', function () {
            runQuery(currentFilterParams(document.getElementById('orgSearchInput').value.trim()));
            if (!document.getElementById('orgTableContainer').classList.contains('d-none')) loadTable();
        });
        document.getElementById('orgDepartmentFilter').addEventListener('change', function () {
            runQuery(currentFilterParams(document.getElementById('orgSearchInput').value.trim()));
            if (!document.getElementById('orgTableContainer').classList.contains('d-none')) loadTable();
        });

        async function loadFilterOptions() {
            try {
                const resp = await fetch(filterOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const data = payload.data ?? {};

                const execSelect = document.getElementById('orgExecutiveFilter');
                execSelect.innerHTML = '<option value="">All executive owners</option>'
                    + (data.executives ?? []).map(e => `<option value="${e.role_id}">${escapeHtml(e.role_name)}</option>`).join('');

                const deptSelect = document.getElementById('orgDepartmentFilter');
                deptSelect.innerHTML = '<option value="">All departments</option>'
                    + (data.departments ?? []).map(d => `<option value="${d.id}">${escapeHtml(d.name)}</option>`).join('');
            } catch (e) {
                console.error(e);
            }
        }

        /* -----------------------------------------------------------
           Expand all / Collapse all - recursive, user-initiated only
           (the default page load stays lazy/cheap for large businesses).
        ----------------------------------------------------------- */
        async function expandAllFrom(employeeId) {
            const children = await expandNode(employeeId);
            const toggle = document.querySelector(`.org-node-toggle[data-employee-id="${employeeId}"]`);
            if (toggle) { toggle.classList.remove('bi-caret-right-fill'); toggle.classList.add('bi-caret-down-fill'); }
            for (const child of children) {
                if (child.has_children) await expandAllFrom(child.id);
            }
        }

        document.getElementById('orgExpandAllBtn').addEventListener('click', async function () {
            const btn = this;
            btn.disabled = true;
            try {
                for (const root of treeRoots) {
                    if (root.has_children) await expandAllFrom(root.id);
                }
            } finally {
                btn.disabled = false;
                fitChartToContainer();
            }
        });

        document.getElementById('orgCollapseAllBtn').addEventListener('click', function () {
            document.querySelectorAll('#orgChartContainer ul.org-chart > li').forEach(li => {
                const ul = li.querySelector(':scope > ul');
                if (ul) ul.remove();
            });
            document.querySelectorAll('#orgChartContainer .org-node-toggle').forEach(t => {
                t.classList.remove('bi-caret-down-fill');
                t.classList.add('bi-caret-right-fill');
            });
            fitChartToContainer();
        });

        /* -----------------------------------------------------------
           Table view
        ----------------------------------------------------------- */
        function relationshipCellHtml(row) {
            let html = `<span class="badge org-tag-green">Primary: ${escapeHtml(row.department || '—')}</span>`;
            if (row.is_cross_functional) {
                html += ` <span class="badge org-tag-purple">Functional: ${escapeHtml(row.functional_manager_name || '')}</span>`;
            }
            return html;
        }

        async function loadTable() {
            const tbody = document.querySelector('#organogramTable tbody');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading…</td></tr>';

            try {
                const params = currentFilterParams(document.getElementById('orgSearchInput').value.trim());
                const resp = await fetch(`${tableUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const rows = payload.data ?? [];

                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No employees found.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map(row => `
                    <tr class="org-table-row" data-employee-id="${row.id}">
                        <td>
                            <b>${escapeHtml(row.name)}</b>
                            <span class="d-block text-muted small">${escapeHtml(row.position || '—')}</span>
                        </td>
                        <td>${row.job_category ? `<span class="badge org-tag-blue">${escapeHtml(row.job_category)}</span>` : '—'}</td>
                        <td>${escapeHtml(row.department || '—')}</td>
                        <td>${escapeHtml(row.line_manager || '—')}</td>
                        <td>${escapeHtml(row.hod || '—')}</td>
                        <td>${escapeHtml(row.executive_owner || '—')}</td>
                        <td>${row.functional_manager_name ? `<span class="badge org-tag-purple">${escapeHtml(row.functional_manager_name)}</span>` : '—'}</td>
                        <td>${relationshipCellHtml(row)}</td>
                    </tr>
                `).join('');

                tbody.querySelectorAll('tr').forEach((tr, idx) => {
                    tr.addEventListener('click', function () {
                        const row = rows[idx];
                        openDetailPanel({
                            name: row.name,
                            organogram_role: row.position,
                            job_category: row.job_category,
                            department: row.department,
                            manager_name: row.line_manager,
                            hod: row.hod,
                            executive_owner: row.executive_owner,
                            functional_manager_name: row.functional_manager_name,
                        });
                    });
                });

                if (window.jQuery && $.fn.DataTable) {
                    if (tableDataTable) tableDataTable.destroy();
                    tableDataTable = new DataTable('#organogramTable', { pageLength: 25 });
                }
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Could not load the table.</td></tr>';
            }
        }

        let tableLoaded = false;

        document.getElementById('treeViewBtn').addEventListener('click', function () {
            document.getElementById('treeViewBtn').classList.add('active');
            document.getElementById('tableViewBtn').classList.remove('active');
            document.getElementById('orgChartContainer').classList.remove('d-none');
            document.getElementById('orgTreeToolbar').classList.remove('d-none');
            document.getElementById('orgTableContainer').classList.add('d-none');
            // The section was d-none (0 width/height) while Table View was
            // active, so any earlier fit computed against it was a no-op -
            // redo it now that it's actually measurable.
            fitChartToContainer();
        });

        document.getElementById('tableViewBtn').addEventListener('click', function () {
            document.getElementById('tableViewBtn').classList.add('active');
            document.getElementById('treeViewBtn').classList.remove('active');
            document.getElementById('orgTableContainer').classList.remove('d-none');
            document.getElementById('orgChartContainer').classList.add('d-none');
            document.getElementById('orgTreeToolbar').classList.add('d-none');
            if (!tableLoaded) { tableLoaded = true; loadTable(); }
        });

        /* -----------------------------------------------------------
           Assign Manager modal (line manager + functional manager)
        ----------------------------------------------------------- */
        async function loadEmployeeOptions() {
            const resp = await fetch(optionsUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const employees = payload.data ?? payload;

            const employeeSelect = document.getElementById('assignEmployeeSelect');
            const managerSelect = document.getElementById('assignManagerSelect');
            const functionalManagerSelect = document.getElementById('assignFunctionalManagerSelect');

            employeeSelect.innerHTML = employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            managerSelect.innerHTML = '<option value="">— None (top of the chart) —</option>'
                + employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            functionalManagerSelect.innerHTML = '<option value="">— None —</option>'
                + employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
        }

        document.getElementById('assignManagerModal').addEventListener('show.bs.modal', loadEmployeeOptions);

        document.getElementById('submitAssignManagerBtn').addEventListener('click', async function () {
            const form = document.getElementById('assignManagerForm');
            const formData = new FormData(form);

            try {
                const resp = await fetch(assignUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: formData,
                });
                const payload = await resp.json();

                if (!resp.ok) {
                    toastr.error(payload.message || 'Could not assign manager.');
                    return;
                }

                toastr.success(payload.message || 'Manager assignment updated.');
                bootstrap.Modal.getInstance(document.getElementById('assignManagerModal')).hide();
                childrenCache.clear();
                loadChart();
                if (tableLoaded) loadTable();
            } catch (e) {
                console.error(e);
                toastr.error('Could not assign manager.');
            }
        });

        let resizeFitTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeFitTimer);
            resizeFitTimer = setTimeout(fitChartToContainer, 150);
        });

        loadChart();
        loadFilterOptions();
        tableLoaded = true;
        loadTable();
    })();
    </script>
</x-app-layout>
