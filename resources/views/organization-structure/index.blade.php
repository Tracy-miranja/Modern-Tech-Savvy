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
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkAssignRoleModal">
                                <i class="bi bi-people me-1"></i> Assign Role to Department
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="syncAllBtn">
                                <i class="bi bi-arrow-repeat me-1"></i> Sync Employees to Structure
                            </button>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="orgStructureTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-tree" type="button">Organization Tree</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-roles" type="button">Roles</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-teams" type="button">Teams</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="tab-tree">
                            @if ($departments->isEmpty())
                                <p class="text-muted">No departments have been created yet. Create departments first, then come back here to assign roles.</p>
                            @else
                                <div class="org-tree-legend d-flex align-items-center gap-3 mb-3 small text-muted">
                                    <span class="d-flex align-items-center gap-1"><span class="org-legend-swatch org-legend-dept"></span> Department</span>
                                    <span class="d-flex align-items-center gap-1"><span class="org-legend-swatch org-legend-team"></span> Team</span>
                                    <span class="d-flex align-items-center gap-1"><span class="org-legend-swatch org-legend-vacant"></span> Vacant slot</span>
                                </div>
                                <div id="orgTreeContainer" class="org-tree-grid">
                                    <p class="text-muted">Loading…</p>
                                </div>
                            @endif
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
                                            <th style="width:160px;">Action</th>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .org-legend-swatch { display: inline-block; width: 10px; height: 10px; border-radius: 3px; }
        .org-legend-dept { background: #0d6efd; }
        .org-legend-team { background: #6f42c1; }
        .org-legend-vacant { border: 1.5px dashed #adb5bd; }

        .org-tree-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .org-dept-card {
            border: 1px solid #e9ecef;
            border-top: 3px solid #0d6efd;
            border-radius: 0.5rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            transition: box-shadow .15s ease;
            overflow: hidden;
        }
        .org-dept-card:hover { box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08); }

        .org-dept-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 0.9rem;
            background: #f8f9fb;
            border-bottom: 1px solid #eef0f2;
        }
        .org-dept-title { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.92rem; }
        .org-dept-title i { color: #0d6efd; }

        .org-add-btn {
            border: none; background: transparent; color: #6c757d; line-height: 1;
            padding: 0.15rem 0.35rem; border-radius: 0.35rem; font-size: 0.78rem;
            display: inline-flex; align-items: center; gap: 0.25rem;
        }
        .org-add-btn:hover { background: #e7f1ff; color: #0d6efd; }

        .org-dept-body { padding: 0.75rem 0.9rem; }

        .org-position-list { display: flex; flex-direction: column; gap: 0.4rem; }

        .org-position-chip {
            display: flex; align-items: center; gap: 0.5rem;
            background: #f8f9fb; border: 1px solid #eef0f2; border-radius: 2rem;
            padding: 0.25rem 0.5rem 0.25rem 0.25rem;
        }
        .org-position-avatar {
            flex: 0 0 auto; width: 26px; height: 26px; border-radius: 50%;
            background: #0d6efd; color: #fff; font-size: 0.72rem; font-weight: 600;
            display: flex; align-items: center; justify-content: center; text-transform: uppercase;
        }
        .org-position-meta { flex: 1 1 auto; min-width: 0; line-height: 1.15; }
        .org-position-role { display: block; font-size: 0.68rem; text-transform: uppercase; letter-spacing: .02em; color: #6c757d; }
        .org-position-name { display: block; font-size: 0.83rem; font-weight: 500; color: #212529; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .org-position-remove {
            flex: 0 0 auto; opacity: 0; cursor: pointer; color: #dc3545; font-size: 0.95rem;
            transition: opacity .1s ease;
        }
        .org-position-chip:hover .org-position-remove { opacity: 1; }

        .org-vacant-slot {
            display: flex; align-items: center; gap: 0.4rem; cursor: pointer;
            border: 1.5px dashed #ced4da; border-radius: 2rem; padding: 0.35rem 0.75rem;
            color: #6c757d; font-size: 0.8rem; background: transparent; width: fit-content;
            transition: border-color .15s ease, color .15s ease;
        }
        .org-vacant-slot:hover { border-color: #0d6efd; color: #0d6efd; }

        .org-team-block {
            margin-top: 0.65rem; padding: 0.6rem 0.65rem 0.6rem 0.75rem;
            border-left: 3px solid #6f42c1; background: #faf8ff; border-radius: 0 0.4rem 0.4rem 0;
        }
        .org-team-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem; }
        .org-team-title { display: flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: #6f42c1; }
    </style>
    @endpush

    <!-- New/Edit Role Modal -->
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

    <!-- New Team Modal -->
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

    <!-- Assign Position Modal -->
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

    <!-- Bulk Assign Role Modal -->
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
        const assignmentsUrl = @json(route('business.organization-structure.assignments.fetch', ['business' => $business->slug]));
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
                        <td>${r.positions_count}</td>
                        <td>${r.employees_count}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-warning edit-role-btn"
                                data-id="${r.id}" data-name="${r.name}" data-level="${r.level}"
                                data-reports-to="${r.reports_to_role_id ?? ''}" data-spatie="${r.spatie_role_name ?? ''}">Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-role-btn" data-id="${r.id}">Delete</button>
                        </td>
                    </tr>
                `).join('');

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
                        if (!confirm('Delete this role? Positions and reporting edges using it will be removed too.')) return;
                        try {
                            await deleteRequest(destroyRoleUrlTemplate.replace('__ID__', this.dataset.id));
                            loadRoles();
                            loadTree();
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
                        if (!confirm('Delete this team? Employees in it keep their department, just lose the team grouping.')) return;
                        try {
                            await deleteRequest(destroyTeamUrlTemplate.replace('__ID__', this.dataset.id));
                            loadTeams();
                            loadTree();
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

        function positionChip(position) {
            return `
                <div class="org-position-chip">
                    <div class="org-position-avatar">${initials(position.name)}</div>
                    <div class="org-position-meta">
                        <span class="org-position-role">${position.role_name}</span>
                        <span class="org-position-name" title="${position.name}">${position.name}</span>
                    </div>
                    <i class="bi bi-x-circle-fill org-position-remove remove-position-btn" role="button" data-position-id="${position.position_id}" title="Remove position"></i>
                </div>`;
        }

        function vacantSlot(departmentId, teamId) {
            return `
                <div class="org-vacant-slot assign-position-btn" data-department-id="${departmentId}" ${teamId ? `data-team-id="${teamId}"` : ''}>
                    <i class="bi bi-plus-circle"></i> Vacant - assign
                </div>`;
        }

        async function loadTree() {
            const container = document.getElementById('orgTreeContainer');
            if (!container) return;

            try {
                const resp = await fetch(assignmentsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const departments = payload.data?.tree ?? [];

                if (!departments.length) {
                    container.innerHTML = '<p class="text-muted">No departments to show.</p>';
                    return;
                }

                container.innerHTML = departments.map(dept => `
                    <div class="org-dept-card">
                        <div class="org-dept-header">
                            <span class="org-dept-title"><i class="bi bi-diagram-3-fill"></i> ${dept.name}</span>
                            <button type="button" class="org-add-btn assign-position-btn" data-department-id="${dept.id}" title="Assign a position to this department">
                                <i class="bi bi-plus-lg"></i> Assign
                            </button>
                        </div>
                        <div class="org-dept-body">
                            <div class="org-position-list">
                                ${dept.positions.length ? dept.positions.map(positionChip).join('') : vacantSlot(dept.id, null)}
                            </div>
                            ${dept.teams.map(team => `
                                <div class="org-team-block">
                                    <div class="org-team-header">
                                        <span class="org-team-title"><i class="bi bi-people-fill"></i> ${team.name}</span>
                                        <button type="button" class="org-add-btn assign-position-btn" data-department-id="${dept.id}" data-team-id="${team.id}" title="Assign a position to this team">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    <div class="org-position-list">
                                        ${team.positions.length ? team.positions.map(positionChip).join('') : vacantSlot(dept.id, team.id)}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `).join('');

                container.querySelectorAll('.assign-position-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        openAssignPositionModal(this.dataset.departmentId, this.dataset.teamId ?? null);
                    });
                });
                container.querySelectorAll('.remove-position-btn').forEach(icon => {
                    icon.addEventListener('click', async function (e) {
                        e.stopPropagation();
                        if (!confirm('Remove this position? Anyone reporting through it falls back to whoever else covers their area, if anyone.')) return;
                        try {
                            await deleteRequest(destroyPositionUrlTemplate.replace('__ID__', this.dataset.positionId));
                            toastr.success('Position removed.');
                            loadTree();
                            loadRoles();
                        } catch (e) { toastr.error(e.message); }
                    });
                });
            } catch (e) {
                console.error(e);
                container.innerHTML = '<p class="text-danger">Could not load the organization tree.</p>';
            }
        }

        async function openAssignPositionModal(departmentId, teamId) {
            const form = document.getElementById('assignPositionForm');
            form.reset();
            document.querySelectorAll('.position-department-check').forEach(c => c.checked = false);

            document.getElementById('positionRoleSelect').innerHTML = '<option value="">Select role</option>'
                + cachedRoles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');

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
                loadTree();
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
                loadTree();
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
                loadTree();
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
                loadTree();
            } catch (e) {
                toastr.error(e.message || 'Could not assign role.');
            }
        });

        document.getElementById('syncAllBtn').addEventListener('click', async function () {
            try {
                const payload = await postJson(syncUrl, {});
                toastr.success(payload.message || 'Synced.');
                loadRoles();
                loadTree();
            } catch (e) {
                console.error(e);
                toastr.error('Sync failed.');
            }
        });

        (async function init() {
            await loadRoles();
            await loadTeams();
            loadTree();
        })();
    })();
    </script>
</x-app-layout>
