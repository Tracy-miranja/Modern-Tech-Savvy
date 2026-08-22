<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#projectModal" id="addProjectBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Project
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="projectsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects-pane" type="button" role="tab">Projects</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-pane" type="button" role="tab">Settings</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="projects-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Manager</th>
                                            <th>Status</th>
                                            <th>Dates</th>
                                            <th>Tasks</th>
                                            <th style="width:220px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="projectsTableBody">
                                        <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings-pane" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Kanban Columns</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr><th style="width:60px;">Order</th><th>Name</th><th>Tasks</th><th>Marks Done</th><th>Active</th><th style="width:110px;">Action</th></tr>
                                                </thead>
                                                <tbody id="statusesTableBody">
                                                    <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">Task Categories</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#taskCategoryModal">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr><th style="width:60px;">Order</th><th>Name</th><th>Tasks</th><th>Active</th><th style="width:110px;">Action</th></tr>
                                                </thead>
                                                <tbody id="taskCategoriesTableBody">
                                                    <tr><td colspan="5" class="text-center text-muted">Loading…</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Reminder Settings</h6>
                                        </div>
                                        <div class="card-body">
                                            <form id="projectSettingsForm">
                                                <label class="form-label small">Task Due Reminder (days before)</label>
                                                <input type="number" min="0" max="60" name="project_task_due_reminder_days" id="settingDueReminderDays" class="form-control" required>
                                                <div class="form-text">Overdue reminders are sent automatically once a task passes its due date.</div>
                                                <button type="button" class="btn btn-primary btn-sm mt-3" id="saveProjectSettingsBtn">Save Settings</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Project Modal -->
    <div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalTitle">Add Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="projectForm">
                        <input type="hidden" id="projectEditingId">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Name</label>
                                <input type="text" name="name" id="projectName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Department</label>
                                <select name="department_id" id="projectDepartment" class="form-select">
                                    <option value="">No department</option>
                                    @foreach(($departments ?? []) as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Manager</label>
                                <select id="projectManagerSelect" class="form-select">
                                    <option value="">No manager</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Status</label>
                                <select name="status" id="projectStatus" class="form-select">
                                    <option value="planning">Planning</option>
                                    <option value="active">Active</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Start Date</label>
                                <input type="date" name="start_date" id="projectStartDate" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">End Date</label>
                                <input type="date" name="end_date" id="projectEndDate" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Budget</label>
                                <input type="number" step="0.01" min="0" name="budget" id="projectBudget" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Description</label>
                                <textarea name="description" id="projectDescription" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitProjectBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalTitle">Add Kanban Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="statusEditingId" value="">
                        <div class="mb-2">
                            <label class="form-label small">Name</label>
                            <input type="text" name="name" id="statusName" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Color</label>
                            <input type="color" name="color" id="statusColor" class="form-control form-control-color" value="#6c757d">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Level (position, 1 = first column)</label>
                            <input type="number" min="1" name="sequence_order" id="statusOrder" class="form-control">
                            <div class="form-text">Leave blank to add at the end - the up/down arrows in the table reorder these too.</div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_done" id="statusIsDone">
                            <label class="form-check-label small" for="statusIsDone">Moving a task here marks it complete</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitStatusBtn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Task Category Modal -->
    <div class="modal fade" id="taskCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskCategoryModalTitle">Add Task Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="taskCategoryForm">
                        <input type="hidden" id="taskCategoryEditingId" value="">
                        <div class="mb-2">
                            <label class="form-label small">Name</label>
                            <input type="text" name="name" id="taskCategoryName" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Color</label>
                            <input type="color" name="color" id="taskCategoryColor" class="form-control form-control-color" value="#0d6efd">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Level (position, 1 = first)</label>
                            <input type="number" min="1" name="sequence_order" id="taskCategoryOrder" class="form-control">
                            <div class="form-text">Leave blank to add at the end - the up/down arrows in the table reorder these too.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitTaskCategoryBtn">Add</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const projectsFetchUrl = @json(route('business.projects.fetch', $business->slug));
        const projectsStoreUrl = @json(route('business.projects.store', $business->slug));
        const projectUpdateUrlTemplate = @json(route('business.projects.update', ['business' => $business->slug, 'project' => '__ID__']));
        const projectDestroyUrlTemplate = @json(route('business.projects.destroy', ['business' => $business->slug, 'project' => '__ID__']));
        const projectBoardUrlTemplate = @json(route('business.projects.board', ['business' => $business->slug, 'project' => '__ID__']));
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', $business->slug));
        const settingsUpdateUrl = @json(route('business.projects.settings.update', $business->slug));
        const statusesFetchUrl = @json(route('business.projects.statuses.fetch', $business->slug));
        const statusesStoreUrl = @json(route('business.projects.statuses.store', $business->slug));
        const statusUpdateUrlTemplate = @json(route('business.projects.statuses.update', ['business' => $business->slug, 'status' => '__ID__']));
        const statusDestroyUrlTemplate = @json(route('business.projects.statuses.destroy', ['business' => $business->slug, 'status' => '__ID__']));
        const statusesReorderUrl = @json(route('business.projects.statuses.reorder', $business->slug));
        const taskCategoriesFetchUrl = @json(route('business.projects.task-categories.fetch', $business->slug));
        const taskCategoriesStoreUrl = @json(route('business.projects.task-categories.store', $business->slug));
        const taskCategoryUpdateUrlTemplate = @json(route('business.projects.task-categories.update', ['business' => $business->slug, 'category' => '__ID__']));
        const taskCategoryDestroyUrlTemplate = @json(route('business.projects.task-categories.destroy', ['business' => $business->slug, 'category' => '__ID__']));
        const taskCategoriesReorderUrl = @json(route('business.projects.task-categories.reorder', $business->slug));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        }

        async function postJson(url, data, method = 'POST') {
            const resp = await fetch(url, {
                method,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data || {}),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        function projectStatusBadge(status) {
            const map = { planning: 'secondary', active: 'success', on_hold: 'warning text-dark', completed: 'primary', cancelled: 'dark' };
            return `<span class="badge bg-${(map[status] ?? 'secondary')}">${(status || '').replace('_', ' ')}</span>`;
        }

        // ---- Projects ------------------------------------------------------

        async function loadProjects() {
            const tbody = document.getElementById('projectsTableBody');
            try {
                const resp = await fetch(projectsFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const projects = payload.data ?? [];

                if (!projects.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No projects yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = projects.map(p => `
                    <tr>
                        <td>${escapeHtml(p.name)}</td>
                        <td>${escapeHtml(p.department?.name ?? '—')}</td>
                        <td>${escapeHtml(p.manager?.user?.name ?? '—')}</td>
                        <td>${projectStatusBadge(p.status)}</td>
                        <td>${p.start_date ? formatDate(p.start_date) : '—'} → ${p.end_date ? formatDate(p.end_date) : '—'}</td>
                        <td>${p.tasks_count ?? 0}</td>
                        <td>
                            <a href="${projectBoardUrlTemplate.replace('__ID__', p.id)}" class="btn btn-sm btn-outline-primary">Board</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary edit-project-btn" data-id="${p.id}">Edit</button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-project-btn" data-id="${p.id}">Delete</button>
                        </td>
                    </tr>`).join('');

                window._projectsCache = projects;
                bindProjectRowHandlers();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load projects.</td></tr>';
            }
        }

        function bindProjectRowHandlers() {
            document.querySelectorAll('.edit-project-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const project = (window._projectsCache || []).find(p => String(p.id) === this.dataset.id);
                    if (!project) return;
                    document.getElementById('projectModalTitle').textContent = 'Edit Project';
                    document.getElementById('projectEditingId').value = project.id;
                    document.getElementById('projectName').value = project.name;
                    document.getElementById('projectDepartment').value = project.department_id ?? '';
                    document.getElementById('projectStatus').value = project.status;
                    document.getElementById('projectStartDate').value = toDateInputValue(project.start_date);
                    document.getElementById('projectEndDate').value = toDateInputValue(project.end_date);
                    document.getElementById('projectBudget').value = project.budget ?? '';
                    document.getElementById('projectDescription').value = project.description ?? '';
                    await loadManagerOptions(project.manager_employee_id);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('projectModal')).show();
                });
            });
            document.querySelectorAll('.delete-project-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete this project?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    try {
                        await postJson(projectDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                        toastr.success('Project deleted.');
                        loadProjects();
                    } catch (e) {
                        toastr.error(e.message || 'Could not delete project.');
                    }
                });
            });
        }

        async function loadManagerOptions(selectedId) {
            const select = document.getElementById('projectManagerSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = '<option value="">No manager</option>'
                    + employees.map(e => `<option value="${e.id}">${escapeHtml(e.name)}</option>`).join('');
                select.value = selectedId ?? '';
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        }

        document.getElementById('addProjectBtn').addEventListener('click', async function () {
            document.getElementById('projectModalTitle').textContent = 'Add Project';
            document.getElementById('projectForm').reset();
            document.getElementById('projectEditingId').value = '';
            await loadManagerOptions(null);
        });

        document.getElementById('submitProjectBtn').addEventListener('click', async function () {
            const form = document.getElementById('projectForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            data.manager_employee_id = document.getElementById('projectManagerSelect').value || null;
            const editingId = document.getElementById('projectEditingId').value;

            try {
                if (editingId) {
                    await postJson(projectUpdateUrlTemplate.replace('__ID__', editingId), data);
                    toastr.success('Project updated.');
                } else {
                    await postJson(projectsStoreUrl, data);
                    toastr.success('Project added.');
                }
                bootstrap.Modal.getInstance(document.getElementById('projectModal'))?.hide();
                loadProjects();
            } catch (e) {
                toastr.error(e.message || 'Could not save project.');
            }
        });

        // ---- Settings: Kanban Columns ---------------------------------------

        let cachedStatuses = [];

        function resetStatusForm() {
            const form = document.getElementById('statusForm');
            form.reset();
            document.getElementById('statusEditingId').value = '';
            document.getElementById('statusColor').value = '#6c757d';
            document.getElementById('statusOrder').value = '';
            document.getElementById('statusModalTitle').textContent = 'Add Kanban Column';
            document.getElementById('submitStatusBtn').textContent = 'Add';
        }

        window.editStatus = function (id) {
            const status = cachedStatuses.find(s => s.id === id);
            if (!status) return;
            document.getElementById('statusEditingId').value = status.id;
            document.getElementById('statusName').value = status.name;
            document.getElementById('statusColor').value = status.color || '#6c757d';
            document.getElementById('statusOrder').value = status.sequence_order ?? '';
            document.getElementById('statusIsDone').checked = !!status.is_done;
            document.getElementById('statusModalTitle').textContent = 'Edit Kanban Column';
            document.getElementById('submitStatusBtn').textContent = 'Save';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('statusModal')).show();
        };

        window.moveStatus = async function (id, direction) {
            const ids = cachedStatuses.map(s => s.id);
            const index = ids.indexOf(id);
            const swapWith = index + direction;
            if (swapWith < 0 || swapWith >= ids.length) return;
            [ids[index], ids[swapWith]] = [ids[swapWith], ids[index]];
            try {
                await postJson(statusesReorderUrl, { ordered_ids: ids });
                loadStatuses();
            } catch (e) {
                toastr.error(e.message || 'Could not reorder columns.');
            }
        };

        async function loadStatuses() {
            const tbody = document.getElementById('statusesTableBody');
            try {
                const resp = await fetch(statusesFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                cachedStatuses = payload.data ?? [];

                tbody.innerHTML = cachedStatuses.map((s, i) => `
                    <tr>
                        <td>
                            <button type="button" class="btn btn-sm btn-link p-0 me-1" ${i === 0 ? 'disabled' : ''} onclick="moveStatus(${s.id}, -1)" title="Move up"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-link p-0" ${i === cachedStatuses.length - 1 ? 'disabled' : ''} onclick="moveStatus(${s.id}, 1)" title="Move down"><i class="bi bi-arrow-down"></i></button>
                        </td>
                        <td><span class="badge" style="background-color:${s.color};">${escapeHtml(s.name)}</span></td>
                        <td>${s.tasks_count ?? 0}</td>
                        <td>${s.is_done ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                        <td>${s.is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editStatus(${s.id})"><i class="bi bi-pencil"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-status-btn" data-id="${s.id}"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>`).join('');

                document.querySelectorAll('.delete-status-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Delete this column?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(statusDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Column deleted.');
                            loadStatuses();
                        } catch (e) {
                            toastr.error(e.message || 'Could not delete column.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load columns.</td></tr>';
            }
        }

        document.getElementById('statusModal').addEventListener('hidden.bs.modal', resetStatusForm);

        document.getElementById('submitStatusBtn').addEventListener('click', async function () {
            const form = document.getElementById('statusForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const editingId = document.getElementById('statusEditingId').value;
            const orderValue = document.getElementById('statusOrder').value;
            const data = {
                name: document.getElementById('statusName').value,
                color: document.getElementById('statusColor').value,
                is_done: document.getElementById('statusIsDone').checked,
            };
            if (orderValue) data.sequence_order = parseInt(orderValue, 10);

            try {
                if (editingId) {
                    await postJson(statusUpdateUrlTemplate.replace('__ID__', editingId), data);
                    toastr.success('Column updated.');
                } else {
                    await postJson(statusesStoreUrl, data);
                    toastr.success('Column added.');
                }
                bootstrap.Modal.getInstance(document.getElementById('statusModal'))?.hide();
                loadStatuses();
            } catch (e) {
                toastr.error(e.message || 'Could not save column.');
            }
        });

        // ---- Settings: Task Categories ---------------------------------------

        let cachedTaskCategories = [];

        function resetTaskCategoryForm() {
            const form = document.getElementById('taskCategoryForm');
            form.reset();
            document.getElementById('taskCategoryEditingId').value = '';
            document.getElementById('taskCategoryColor').value = '#0d6efd';
            document.getElementById('taskCategoryOrder').value = '';
            document.getElementById('taskCategoryModalTitle').textContent = 'Add Task Category';
            document.getElementById('submitTaskCategoryBtn').textContent = 'Add';
        }

        window.editTaskCategory = function (id) {
            const category = cachedTaskCategories.find(c => c.id === id);
            if (!category) return;
            document.getElementById('taskCategoryEditingId').value = category.id;
            document.getElementById('taskCategoryName').value = category.name;
            document.getElementById('taskCategoryColor').value = category.color || '#0d6efd';
            document.getElementById('taskCategoryOrder').value = category.sequence_order ?? '';
            document.getElementById('taskCategoryModalTitle').textContent = 'Edit Task Category';
            document.getElementById('submitTaskCategoryBtn').textContent = 'Save';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('taskCategoryModal')).show();
        };

        window.moveTaskCategory = async function (id, direction) {
            const ids = cachedTaskCategories.map(c => c.id);
            const index = ids.indexOf(id);
            const swapWith = index + direction;
            if (swapWith < 0 || swapWith >= ids.length) return;
            [ids[index], ids[swapWith]] = [ids[swapWith], ids[index]];
            try {
                await postJson(taskCategoriesReorderUrl, { ordered_ids: ids });
                loadTaskCategories();
            } catch (e) {
                toastr.error(e.message || 'Could not reorder categories.');
            }
        };

        async function loadTaskCategories() {
            const tbody = document.getElementById('taskCategoriesTableBody');
            try {
                const resp = await fetch(taskCategoriesFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                cachedTaskCategories = payload.data ?? [];

                tbody.innerHTML = cachedTaskCategories.map((c, i) => `
                    <tr>
                        <td>
                            <button type="button" class="btn btn-sm btn-link p-0 me-1" ${i === 0 ? 'disabled' : ''} onclick="moveTaskCategory(${c.id}, -1)" title="Move up"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-link p-0" ${i === cachedTaskCategories.length - 1 ? 'disabled' : ''} onclick="moveTaskCategory(${c.id}, 1)" title="Move down"><i class="bi bi-arrow-down"></i></button>
                        </td>
                        <td><span class="badge" style="background-color:${c.color};">${escapeHtml(c.name)}</span></td>
                        <td>${c.tasks_count ?? 0}</td>
                        <td>${c.is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editTaskCategory(${c.id})"><i class="bi bi-pencil"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-task-category-btn" data-id="${c.id}"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>`).join('');

                document.querySelectorAll('.delete-task-category-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Delete this category?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(taskCategoryDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Category deleted.');
                            loadTaskCategories();
                        } catch (e) {
                            toastr.error(e.message || 'Could not delete category.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Could not load categories.</td></tr>';
            }
        }

        document.getElementById('taskCategoryModal').addEventListener('hidden.bs.modal', resetTaskCategoryForm);

        document.getElementById('submitTaskCategoryBtn').addEventListener('click', async function () {
            const form = document.getElementById('taskCategoryForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const editingId = document.getElementById('taskCategoryEditingId').value;
            const orderValue = document.getElementById('taskCategoryOrder').value;
            const data = {
                name: document.getElementById('taskCategoryName').value,
                color: document.getElementById('taskCategoryColor').value,
            };
            if (orderValue) data.sequence_order = parseInt(orderValue, 10);

            try {
                if (editingId) {
                    await postJson(taskCategoryUpdateUrlTemplate.replace('__ID__', editingId), data);
                    toastr.success('Category updated.');
                } else {
                    await postJson(taskCategoriesStoreUrl, data);
                    toastr.success('Category added.');
                }
                bootstrap.Modal.getInstance(document.getElementById('taskCategoryModal'))?.hide();
                loadTaskCategories();
            } catch (e) {
                toastr.error(e.message || 'Could not save category.');
            }
        });

        // ---- Settings: Reminder Days -----------------------------------------

        document.getElementById('settingDueReminderDays').value = @json($business->project_task_due_reminder_days);

        document.getElementById('saveProjectSettingsBtn').addEventListener('click', async function () {
            const form = document.getElementById('projectSettingsForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());

            try {
                await postJson(settingsUpdateUrl, data);
                toastr.success('Settings updated.');
            } catch (e) {
                toastr.error(e.message || 'Could not update settings.');
            }
        });

        loadProjects();
        loadStatuses();
        loadTaskCategories();
    })();
    </script>
    @endpush
</x-app-layout>
