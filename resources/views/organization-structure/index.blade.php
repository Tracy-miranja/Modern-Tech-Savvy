<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Organization Structure</h5>
                            <small class="text-muted">
                                Define roles and who they report to, group departments into teams if useful, then assign employees to
                                roles below - each assignment can cover one or more departments/teams (e.g. one manager covering several
                                departments). Employees are then arranged automatically in the
                                <a href="{{ route('business.organogram.index', $business->slug) }}">Employee Organogram</a>.
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkAssignRoleModal"
                                title="Assign a role to every employee in a department">
                                <i class="bi bi-people me-1"></i> Assign Role
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="syncAllBtn"
                                title="Sync employees to the organization structure">
                                <i class="bi bi-arrow-repeat me-1"></i> Sync Employees
                            </button>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="orgStructureTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" id="overviewTabBtn">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-roles" type="button">Roles</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-teams" type="button">Teams</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-canvas" type="button" id="canvasTabBtn">Structure Canvas</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="tab-overview">
                            <div class="org-stats" id="orgStatsGrid">
                                <div class="org-stat-card">
                                    <div class="org-stat-icon"><i class="bi bi-people-fill"></i></div>
                                    <div><strong id="statEmployees">0</strong><span>Employees / positions</span></div>
                                </div>
                                <div class="org-stat-card">
                                    <div class="org-stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
                                    <div><strong id="statDepartments">0</strong><span>Departments</span></div>
                                </div>
                                <div class="org-stat-card">
                                    <div class="org-stat-icon"><i class="bi bi-award-fill"></i></div>
                                    <div><strong id="statExecutives">0</strong><span>Executive owners</span></div>
                                </div>
                                <div class="org-stat-card">
                                    <div class="org-stat-icon"><i class="bi bi-shuffle"></i></div>
                                    <div><strong id="statCross">0</strong><span>Cross-functional assignments</span></div>
                                </div>
                            </div>

                            <div class="org-overview-canvas" id="orgOverviewCanvas">
                                <p class="text-muted">Loading…</p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-roles">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRoleModal">
                                    <i class="bi bi-plus-circle me-1"></i> New Role
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Reports To</th>
                                            <th>Permission Role</th>
                                            <th>Positions</th>
                                            <th>Employees on this rung</th>
                                            <th style="width:220px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rolesTableBody">
                                        <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">"Reports to" defines the chain used to work out each employee's default manager - it doesn't have to be a strict ladder; two roles can be peers by simply not reporting to each other.</small>
                        </div>

                        <div class="tab-pane fade" id="tab-teams">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTeamModal">
                                    <i class="bi bi-plus-circle me-1"></i> New Team
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Team</th>
                                            <th>Department</th>
                                            <th>Employees</th>
                                            <th style="width:100px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="teamsTableBody">
                                        <tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">Teams are optional - a department with no teams works exactly as before. Assign employees to a team from their profile.</small>
                        </div>

                        <div class="tab-pane fade" id="tab-canvas">
                            <div class="d-flex justify-content-end gap-2 mb-2">
                                <span class="small text-muted align-self-center" id="canvasStatus"></span>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="canvasAutoLayoutBtn">
                                    <i class="bi bi-diagram-3 me-1"></i> Auto-arrange
                                </button>
                            </div>
                            <div id="structureCanvas" class="structure-canvas">
                                <p class="text-muted p-3">Loading…</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/org-chart.css') }}">
    @endpush

    <div class="modal fade" id="newRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">New Organogram Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roleForm">
                        <input type="hidden" name="role_id">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Head of Department" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reports To</label>
                            <select name="reports_to_role_id" id="reportsToRoleSelect" class="form-select">
                                <option value="">— Top of the chain (no one) —</option>
                            </select>
                            <small class="text-muted">Leave as "no one" for peer/top-level roles (e.g. MD, or an ED who's a peer of the MD).</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grants Permission Role (optional)</label>
                            <select name="spatie_role_name" id="spatieRoleSelect" class="form-select">
                                <option value="">— None —</option>
                            </select>
                            <small class="text-muted">Whoever is assigned this organogram role automatically gets this system permission role too.</small>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="level" class="form-control" min="1" value="1" required>
                            <small class="text-muted">Only affects sort order in lists - not used to work out who reports to whom.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitRoleBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newTeamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="teamForm">
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Team Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitTeamBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rolePositionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rolePositionsModalTitle">Positions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="rolePositionsList">
                    <p class="text-muted mb-0">Loading…</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignPositionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignPositionForm">
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select id="positionRoleSelect" class="form-select" required>
                                <option value="">Select role</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select id="positionEmployeeSelect" class="form-select" required>
                                <option value="">Loading employees…</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Covers Department(s)</label>
                            <div id="positionDepartmentChecks" class="border rounded p-2" style="max-height:140px;overflow-y:auto;">
                                @foreach ($departments as $department)
                                    <div class="form-check">
                                        <input class="form-check-input position-department-check" type="checkbox" value="{{ $department->id }}" id="pos-dept-{{ $department->id }}">
                                        <label class="form-check-label" for="pos-dept-{{ $department->id }}">{{ $department->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted">Tick every department this position is responsible for (e.g. one manager covering 5 departments).</small>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Covers Team(s) (optional, more specific than a department)</label>
                            <div id="positionTeamChecks" class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
                                <p class="text-muted small mb-0">No teams yet.</p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAssignPositionBtn">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkAssignRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Role to Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Gives every employee in the chosen department (or team) their own rung on the ladder,
                        e.g. "Staff", so their default manager can be worked out and "Sync Employees to Structure" has
                        something to do for them. This does not touch anyone who already holds a position themselves.
                    </p>
                    <form id="bulkAssignRoleForm">
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select id="bulkRoleSelect" class="form-select" required>
                                <option value="">Select role</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select id="bulkDepartmentSelect" class="form-select" required>
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team (optional, narrows to just that team)</label>
                            <select id="bulkTeamSelect" class="form-select">
                                <option value="">Whole department</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bulkOverwriteExisting">
                            <label class="form-check-label" for="bulkOverwriteExisting">
                                Overwrite employees who already have a role assigned
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitBulkAssignRoleBtn">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const overviewUrl = @json(route('business.organization-structure.overview.fetch', ['business' => $business->slug]));
        const fetchRolesUrl = @json(route('business.organization-structure.roles.fetch', ['business' => $business->slug]));
        const storeRoleUrl = @json(route('business.organization-structure.roles.store', ['business' => $business->slug]));
        const updateRoleUrlTemplate = @json(route('business.organization-structure.roles.update', ['business' => $business->slug, 'role' => '__ID__']));
        const destroyRoleUrlTemplate = @json(route('business.organization-structure.roles.destroy', ['business' => $business->slug, 'role' => '__ID__']));
        const spatieRolesUrl = @json(route('business.organization-structure.spatie-roles.fetch', ['business' => $business->slug]));
        const fetchTeamsUrl = @json(route('business.organization-structure.teams.fetch', ['business' => $business->slug]));
        const storeTeamUrl = @json(route('business.organization-structure.teams.store', ['business' => $business->slug]));
        const destroyTeamUrlTemplate = @json(route('business.organization-structure.teams.destroy', ['business' => $business->slug, 'team' => '__ID__']));
        const syncUrl = @json(route('business.organization-structure.sync', ['business' => $business->slug]));
        const bulkAssignRoleUrl = @json(route('business.organization-structure.bulk-assign-role', ['business' => $business->slug]));
        const roleTreeUrl = @json(route('business.organization-structure.role-tree.fetch', ['business' => $business->slug]));
        const storePositionUrl = @json(route('business.organization-structure.positions.store', ['business' => $business->slug]));
        const destroyPositionUrlTemplate = @json(route('business.organization-structure.positions.destroy', ['business' => $business->slug, 'position' => '__ID__']));
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', ['business' => $business->slug]));

        let cachedRoles = [];
        let cachedTeams = [];

        async function postJson(url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        async function deleteRequest(url) {
            const resp = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        function populateReportsToSelect(selectedId, excludeRoleId) {
            const select = document.getElementById('reportsToRoleSelect');
            select.innerHTML = '<option value="">— Top of the chain (no one) —</option>'
                + cachedRoles
                    .filter(r => String(r.id) !== String(excludeRoleId))
                    .map(r => `<option value="${r.id}" ${String(r.id) === String(selectedId) ? 'selected' : ''}>${r.name}</option>`)
                    .join('');
        }

        async function populateSpatieRoleSelect(selectedName) {
            const select = document.getElementById('spatieRoleSelect');
            try {
                const resp = await fetch(spatieRolesUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const roles = payload.data ?? [];
                select.innerHTML = '<option value="">— None —</option>'
                    + roles.map(name => `<option value="${name}" ${name === selectedName ? 'selected' : ''}>${name}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">Could not load permission roles</option>';
            }
        }

        async function loadRoles() {
            const tbody = document.getElementById('rolesTableBody');
            try {
                const resp = await fetch(fetchRolesUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const roles = payload.data ?? [];
                cachedRoles = roles;

                if (!roles.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No roles defined yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = roles.map(r => `
                    <tr>
                        <td>${r.name}</td>
                        <td>${r.reports_to_role ? r.reports_to_role.name : '<span class="text-muted">Top of chain</span>'}</td>
                        <td>${r.spatie_role_name ? `<span class="badge bg-info text-dark">${r.spatie_role_name}</span>` : '<span class="text-muted">—</span>'}</td>
                        <td>
                            <button type="button" class="btn btn-link btn-sm p-0 view-positions-btn" data-role-id="${r.id}" data-role-name="${r.name}">
                                ${r.positions_count} <i class="bi bi-box-arrow-up-right small"></i>
                            </button>
                        </td>
                        <td>${r.employees_count}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary assign-role-position-btn" data-role-id="${r.id}" title="Assign an employee to a position under this role">
                                <i class="bi bi-person-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning edit-role-btn"
                                data-id="${r.id}" data-name="${r.name}" data-level="${r.level}"
                                data-reports-to="${r.reports_to_role_id ?? ''}" data-spatie="${r.spatie_role_name ?? ''}">Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-role-btn" data-id="${r.id}">Delete</button>
                        </td>
                    </tr>
                `).join('');

                document.querySelectorAll('.assign-role-position-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        openAssignPositionModal(null, null, this.dataset.roleId);
                    });
                });

                document.querySelectorAll('.view-positions-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        openRolePositionsModal(this.dataset.roleId, this.dataset.roleName);
                    });
                });

                document.querySelectorAll('.edit-role-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        document.getElementById('roleModalTitle').textContent = 'Edit Organogram Role';
                        const form = document.getElementById('roleForm');
                        form.querySelector('[name=role_id]').value = this.dataset.id;
                        form.querySelector('[name=name]').value = this.dataset.name;
                        form.querySelector('[name=level]').value = this.dataset.level;
                        populateReportsToSelect(this.dataset.reportsTo, this.dataset.id);
                        await populateSpatieRoleSelect(this.dataset.spatie);
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('newRoleModal')).show();
                    });
                });

                document.querySelectorAll('.delete-role-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Delete this role? Positions and reporting edges using it will be removed too.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await deleteRequest(destroyRoleUrlTemplate.replace('__ID__', this.dataset.id));
                            loadRoles();
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load roles.</td></tr>';
            }
        }

        async function loadTeams() {
            const tbody = document.getElementById('teamsTableBody');
            try {
                const resp = await fetch(fetchTeamsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const teams = payload.data ?? [];
                cachedTeams = teams;

                if (!teams.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No teams defined yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = teams.map(t => `
                    <tr>
                        <td>${t.name}</td>
                        <td>${t.department ? t.department.name : '—'}</td>
                        <td>${t.employees_count}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger delete-team-btn" data-id="${t.id}">Delete</button></td>
                    </tr>
                `).join('');

                document.querySelectorAll('.delete-team-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Delete this team? Employees in it keep their department, just lose the team grouping.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await deleteRequest(destroyTeamUrlTemplate.replace('__ID__', this.dataset.id));
                            loadTeams();
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Could not load teams.</td></tr>';
            }
        }

        function initials(name) {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return '?';
            return parts.length === 1 ? parts[0].slice(0, 2) : (parts[0][0] + parts[parts.length - 1][0]);
        }

        /**
         * Finds one role's node (with its positions array) inside the
         * nested role-tree response - reused here just to power the
         * Positions modal below, now that the full visual tree (redundant
         * with the Organogram page's own tree) has been removed in favor
         * of it.
         */
        function findRoleNode(nodes, roleId) {
            for (const node of nodes) {
                if (String(node.id) === String(roleId)) return node;
                const found = findRoleNode(node.children || [], roleId);
                if (found) return found;
            }
            return null;
        }

        async function openRolePositionsModal(roleId, roleName) {
            document.getElementById('rolePositionsModalTitle').textContent = `Positions - ${roleName}`;
            const list = document.getElementById('rolePositionsList');
            list.innerHTML = '<p class="text-muted mb-0">Loading…</p>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rolePositionsModal')).show();

            try {
                const resp = await fetch(roleTreeUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const role = findRoleNode(payload.data ?? [], roleId);
                const positions = role ? role.positions : [];

                if (!positions.length) {
                    list.innerHTML = '<p class="text-muted mb-0">No one is assigned to this role yet.</p>';
                    return;
                }

                list.innerHTML = positions.map(p => `
                    <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                        <div>
                            <div class="fw-medium small">${p.name}</div>
                            ${p.coverage && p.coverage.length ? `<div class="text-muted" style="font-size:.75rem;">${p.coverage.join(', ')}</div>` : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-position-btn" data-position-id="${p.position_id}" title="Remove position">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>`).join('');

                list.querySelectorAll('.remove-position-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Remove this position? Anyone reporting through it falls back to whoever else covers their area, if anyone.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await deleteRequest(destroyPositionUrlTemplate.replace('__ID__', this.dataset.positionId));
                            toastr.success('Position removed.');
                            loadRoles();
                            openRolePositionsModal(roleId, roleName);
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                list.innerHTML = '<p class="text-danger mb-0">Could not load positions.</p>';
            }
        }

        async function openAssignPositionModal(departmentId, teamId, preselectedRoleId = null) {
            const form = document.getElementById('assignPositionForm');
            form.reset();
            document.querySelectorAll('.position-department-check').forEach(c => c.checked = false);

            document.getElementById('positionRoleSelect').innerHTML = '<option value="">Select role</option>'
                + cachedRoles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
            if (preselectedRoleId) {
                document.getElementById('positionRoleSelect').value = preselectedRoleId;
            }

            const teamContainer = document.getElementById('positionTeamChecks');
            teamContainer.innerHTML = cachedTeams.length
                ? cachedTeams.map(t => `
                    <div class="form-check">
                        <input class="form-check-input position-team-check" type="checkbox" value="${t.id}" id="pos-team-${t.id}">
                        <label class="form-check-label" for="pos-team-${t.id}">${t.name} (${t.department ? t.department.name : ''})</label>
                    </div>`).join('')
                : '<p class="text-muted small mb-0">No teams yet.</p>';

            if (departmentId) {
                const deptCheck = document.getElementById('pos-dept-' + departmentId);
                if (deptCheck) deptCheck.checked = true;
            }
            if (teamId) {
                const teamCheck = document.getElementById('pos-team-' + teamId);
                if (teamCheck) teamCheck.checked = true;
            }

            const employeeSelect = document.getElementById('positionEmployeeSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                employeeSelect.innerHTML = '<option value="">Select employee</option>'
                    + employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            } catch (e) {
                employeeSelect.innerHTML = '<option value="">Could not load employees</option>';
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('assignPositionModal')).show();
        }

        document.getElementById('newRoleModal').addEventListener('show.bs.modal', function (e) {
            if (e.relatedTarget) {
                document.getElementById('roleModalTitle').textContent = 'New Organogram Role';
                const form = document.getElementById('roleForm');
                form.reset();
                form.querySelector('[name=role_id]').value = '';
                populateReportsToSelect('', '');
                populateSpatieRoleSelect('');
            }
        });

        document.getElementById('submitRoleBtn').addEventListener('click', async function () {
            const form = document.getElementById('roleForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            const roleId = data.role_id;
            delete data.role_id;

            try {
                const url = roleId ? updateRoleUrlTemplate.replace('__ID__', roleId) : storeRoleUrl;
                await postJson(url, data);
                toastr.success('Role saved.');
                bootstrap.Modal.getInstance(document.getElementById('newRoleModal')).hide();
                loadRoles();
            } catch (e) {
                console.error(e);
                toastr.error(e.message || 'Could not save role.');
            }
        });

        document.getElementById('submitTeamBtn').addEventListener('click', async function () {
            const form = document.getElementById('teamForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            try {
                await postJson(storeTeamUrl, data);
                toastr.success('Team saved.');
                bootstrap.Modal.getInstance(document.getElementById('newTeamModal')).hide();
                form.reset();
                loadTeams();
            } catch (e) { toastr.error(e.message); }
        });

        document.getElementById('submitAssignPositionBtn').addEventListener('click', async function () {
            const roleId = document.getElementById('positionRoleSelect').value;
            const employeeId = document.getElementById('positionEmployeeSelect').value;
            const departmentIds = Array.from(document.querySelectorAll('.position-department-check:checked')).map(c => c.value);
            const teamIds = Array.from(document.querySelectorAll('.position-team-check:checked')).map(c => c.value);

            if (!roleId || !employeeId || !departmentIds.length) {
                toastr.error('Pick a role, an employee, and at least one department.');
                return;
            }

            try {
                await postJson(storePositionUrl, {
                    organogram_role_id: roleId,
                    employee_id: employeeId,
                    department_ids: departmentIds,
                    team_ids: teamIds,
                });
                toastr.success('Position assigned.');
                bootstrap.Modal.getInstance(document.getElementById('assignPositionModal')).hide();
                loadRoles();
            } catch (e) { toastr.error(e.message); }
        });

        function populateBulkTeamSelect(departmentId) {
            const select = document.getElementById('bulkTeamSelect');
            const teams = cachedTeams.filter(t => String(t.department_id ?? t.department?.id) === String(departmentId));
            select.innerHTML = '<option value="">Whole department</option>'
                + teams.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
        }

        document.getElementById('bulkAssignRoleModal').addEventListener('show.bs.modal', function () {
            document.getElementById('bulkAssignRoleForm').reset();
            document.getElementById('bulkRoleSelect').innerHTML = '<option value="">Select role</option>'
                + cachedRoles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
            populateBulkTeamSelect(document.getElementById('bulkDepartmentSelect').value);
        });

        document.getElementById('bulkDepartmentSelect').addEventListener('change', function () {
            populateBulkTeamSelect(this.value);
        });

        document.getElementById('submitBulkAssignRoleBtn').addEventListener('click', async function () {
            const form = document.getElementById('bulkAssignRoleForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const organogramRoleId = document.getElementById('bulkRoleSelect').value;
            const departmentId = document.getElementById('bulkDepartmentSelect').value;
            const teamId = document.getElementById('bulkTeamSelect').value;
            const overwriteExisting = document.getElementById('bulkOverwriteExisting').checked;

            if (!organogramRoleId || !departmentId) {
                toastr.error('Pick a role and a department.');
                return;
            }

            try {
                const payload = await postJson(bulkAssignRoleUrl, {
                    organogram_role_id: organogramRoleId,
                    department_id: departmentId,
                    team_id: teamId || undefined,
                    overwrite_existing: overwriteExisting,
                });
                toastr.success(payload.message || 'Employees assigned.');
                bootstrap.Modal.getInstance(document.getElementById('bulkAssignRoleModal')).hide();
                loadRoles();
            } catch (e) {
                toastr.error(e.message || 'Could not assign role.');
            }
        });

        document.getElementById('syncAllBtn').addEventListener('click', async function () {
            try {
                const payload = await postJson(syncUrl, {});
                toastr.success(payload.message || 'Synced.');
                loadRoles();
            } catch (e) {
                console.error(e);
                toastr.error('Sync failed.');
            }
        });

        function holdersLabel(holders) {
            if (!holders || !holders.length) return '<span class="text-muted">Vacant</span>';
            return holders.join(', ');
        }

        async function loadOverview() {
            const canvas = document.getElementById('orgOverviewCanvas');
            try {
                const resp = await fetch(overviewUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const data = payload.data ?? {};
                const stats = data.stats ?? {};
                const roots = data.roots ?? [];
                const executives = data.executives ?? [];

                document.getElementById('statEmployees').textContent = stats.employees ?? 0;
                document.getElementById('statDepartments').textContent = stats.departments ?? 0;
                document.getElementById('statExecutives').textContent = stats.executives ?? 0;
                document.getElementById('statCross').textContent = stats.cross_functional ?? 0;

                if (!roots.length) {
                    canvas.innerHTML = '<p class="text-muted p-3">No roles defined yet - set up roles on the Roles tab first, then come back here to see the ownership overview.</p>';
                    return;
                }

                const rootsHtml = roots.map(r => `
                    <div class="org-overview-node">
                        <strong>${r.role_name}</strong>
                        <small>${holdersLabel(r.holders)}</small>
                    </div>
                `).join('');

                const execCardsHtml = executives.length
                    ? executives.map(ex => `
                        <div class="org-exec-card">
                            <div class="org-exec-head">
                                ${ex.role_name}
                                <small>${holdersLabel(ex.holders)}</small>
                            </div>
                            ${ex.departments.map(d => `
                                <div class="org-dept-row">
                                    <b>${d.name}</b>
                                    <small>${d.people_count} people/position(s)</small>
                                </div>
                            `).join('')}
                        </div>
                    `).join('')
                    : '<p class="text-muted">No department is covered by a top-level role yet - assign a position for a root-level role to a department on the Roles tab.</p>';

                canvas.innerHTML = `
                    <div class="org-overview-center">
                        <div class="org-overview-roots">${rootsHtml}</div>
                        ${executives.length ? '<div class="org-overview-vline"></div>' : ''}
                        <div class="org-exec-grid">${execCardsHtml}</div>
                    </div>
                `;
            } catch (e) {
                console.error(e);
                canvas.innerHTML = '<p class="text-danger">Could not load the overview.</p>';
            }
        }

        (async function init() {
            await loadRoles();
            await loadTeams();
            loadOverview();
        })();
    })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/jsplumb@2.15.6/dist/js/jsplumb.min.js"></script>
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const canvasGraphUrl = @json(route('business.organization-structure.canvas.graph', ['business' => $business->slug]));
        const canvasPositionUrlTemplate = @json(route('business.organization-structure.canvas.nodes.position', ['business' => $business->slug, 'node' => '__ID__']));
        const canvasEdgeStoreUrl = @json(route('business.organization-structure.canvas.edges.store', ['business' => $business->slug]));
        const canvasEdgeDestroyUrlTemplate = @json(route('business.organization-structure.canvas.edges.destroy', ['business' => $business->slug, 'edgeId' => '__ID__']));

        let jsPlumbInstance = null;
        let canvasLoaded = false;

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

        async function deleteRequest(url) {
            const resp = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        // Simple layered auto-layout for nodes that have never been dragged:
        // departments in a left column, job categories in a right column,
        // roles cascading top-to-bottom in the middle by their "level".
        function autoLayoutPositions(nodes) {
            const positions = {};
            const departments = nodes.filter(n => n.node_type === 'department');
            const jobCategories = nodes.filter(n => n.node_type === 'job_category');
            const roles = nodes.filter(n => n.node_type === 'role');

            departments.forEach((n, i) => { positions[n.id] = { x: 20, y: 20 + i * 90 }; });
            jobCategories.forEach((n, i) => { positions[n.id] = { x: 900, y: 20 + i * 90 }; });

            const byLevel = {};
            roles.forEach(n => {
                const lvl = n.level || 1;
                (byLevel[lvl] = byLevel[lvl] || []).push(n);
            });
            Object.keys(byLevel).sort((a, b) => a - b).forEach((lvl, rowIndex) => {
                byLevel[lvl].forEach((n, i) => {
                    positions[n.id] = { x: 260 + i * 190, y: 20 + rowIndex * 140 };
                });
            });

            return positions;
        }

        function nodeElementId(nodeId) { return `canvas-node-${nodeId}`; }

        function renderNode(node, pos) {
            const el = document.createElement('div');
            el.className = `canvas-node canvas-node-${node.node_type}`;
            el.id = nodeElementId(node.id);
            el.dataset.nodeId = node.id;
            if (node.level) el.dataset.level = node.level;
            el.style.left = `${pos.x}px`;
            el.style.top = `${pos.y}px`;
            el.innerHTML = `
                <div class="canvas-node-type">${node.node_type.replace('_', ' ')}</div>
                <div class="canvas-node-label">${node.label}</div>
            `;
            return el;
        }

        async function loadCanvas() {
            const container = document.getElementById('structureCanvas');
            const status = document.getElementById('canvasStatus');
            container.innerHTML = '';

            let payload;
            try {
                const resp = await fetch(canvasGraphUrl, { headers: { 'Accept': 'application/json' } });
                payload = (await resp.json()).data;
            } catch (e) {
                container.innerHTML = '<p class="text-danger p-3">Could not load the structure canvas.</p>';
                return;
            }

            const nodes = payload.nodes || [];
            const edges = payload.edges || [];

            if (!nodes.length) {
                container.innerHTML = '<p class="text-muted p-3">No departments, roles, or job categories yet - create some first, then come back here to lay them out.</p>';
                return;
            }

            const autoPositions = autoLayoutPositions(nodes);
            nodes.forEach(node => {
                const pos = (node.pos_x !== null && node.pos_y !== null)
                    ? { x: node.pos_x, y: node.pos_y }
                    : autoPositions[node.id];
                container.appendChild(renderNode(node, pos));
            });

            if (jsPlumbInstance) {
                jsPlumbInstance.reset();
            }

            jsPlumb.ready(function () {
                jsPlumbInstance = jsPlumb.getInstance({
                    Container: container,
                    Connector: ['Bezier', { curviness: 40 }],
                    PaintStyle: { stroke: '#adb5bd', strokeWidth: 2 },
                    HoverPaintStyle: { stroke: '#f89616', strokeWidth: 3 },
                    EndpointStyle: { fill: '#adb5bd', radius: 4 },
                    ConnectionOverlays: [['Arrow', { location: 1, width: 10, length: 10 }]],
                });

                nodes.forEach(node => {
                    const el = document.getElementById(nodeElementId(node.id));
                    jsPlumbInstance.draggable(el, {
                        grid: [10, 10],
                        stop: function (event) {
                            const nodeId = event.el.dataset.nodeId;
                            const rect = { x: parseInt(event.el.style.left, 10), y: parseInt(event.el.style.top, 10) };
                            postJson(canvasPositionUrlTemplate.replace('__ID__', nodeId), { pos_x: rect.x, pos_y: rect.y })
                                .catch(() => { if (window.toastr) toastr.error('Could not save position.'); });
                        },
                    });
                    jsPlumbInstance.makeSource(el, { filter: '.canvas-node-label, .canvas-node-type', anchor: 'Continuous', maxConnections: -1 });
                    jsPlumbInstance.makeTarget(el, { anchor: 'Continuous', maxConnections: -1 });
                });

                edges.forEach(edge => {
                    const conn = jsPlumbInstance.connect({
                        source: nodeElementId(edge.from_node_id),
                        target: nodeElementId(edge.to_node_id),
                    });
                    if (conn) {
                        conn.canvasEdgeId = edge.id;
                        if (edge.source === 'reports_to_role_id') conn.addClass('canvas-edge-role');
                    }
                });

                jsPlumbInstance.bind('connection', function (info, originalEvent) {
                    // Only react to connections the user just drew by hand -
                    // programmatic connect() calls above (restoring saved
                    // edges) don't carry an originalEvent.
                    if (!originalEvent) return;

                    const fromId = info.source.dataset.nodeId;
                    const toId = info.target.dataset.nodeId;
                    postJson(canvasEdgeStoreUrl, { from_node_id: fromId, to_node_id: toId })
                        .then(payload => {
                            info.connection.canvasEdgeId = payload.data.id;
                            if (payload.data.source === 'reports_to_role_id') info.connection.addClass('canvas-edge-role');
                            if (window.toastr) toastr.success('Connection saved.');
                        })
                        .catch(e => {
                            jsPlumbInstance.deleteConnection(info.connection);
                            if (window.toastr) toastr.error(e.message || 'Could not save connection.');
                        });
                });

                jsPlumbInstance.bind('click', async function (conn) {
                    if (!conn.canvasEdgeId) return;
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Remove this connection?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    deleteRequest(canvasEdgeDestroyUrlTemplate.replace('__ID__', conn.canvasEdgeId))
                        .then(() => jsPlumbInstance.deleteConnection(conn))
                        .catch(e => { if (window.toastr) toastr.error(e.message || 'Could not remove connection.'); });
                });

                status.textContent = `${nodes.length} node(s), ${edges.length} connection(s).`;
            });
        }

        document.getElementById('canvasTabBtn').addEventListener('shown.bs.tab', function () {
            if (!canvasLoaded) {
                canvasLoaded = true;
                loadCanvas();
            }
        });

        document.getElementById('canvasAutoLayoutBtn').addEventListener('click', async function () {
            const container = document.getElementById('structureCanvas');
            const nodeEls = Array.from(container.querySelectorAll('.canvas-node'));
            const nodes = nodeEls.map(el => ({
                id: el.dataset.nodeId,
                node_type: el.className.match(/canvas-node-(department|role|job_category)/)[1],
                level: el.dataset.level ? parseInt(el.dataset.level, 10) : null,
            }));
            const positions = autoLayoutPositions(nodes);
            nodeEls.forEach(el => {
                const pos = positions[el.dataset.nodeId];
                el.style.left = `${pos.x}px`;
                el.style.top = `${pos.y}px`;
                postJson(canvasPositionUrlTemplate.replace('__ID__', el.dataset.nodeId), { pos_x: pos.x, pos_y: pos.y }).catch(() => {});
            });
            if (jsPlumbInstance) jsPlumbInstance.repaintEverything();
        });
    })();
    </script>
</x-app-layout>
