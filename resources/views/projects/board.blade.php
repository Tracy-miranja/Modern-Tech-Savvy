@php($routePrefix = $routePrefix ?? 'business.')
<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <a href="{{ route($routePrefix.'projects.index', $business->slug) }}" class="text-decoration-none small d-block mb-1">
                            <i class="bi bi-arrow-left"></i> Back to Projects
                        </a>
                        <h5 class="mb-0">{{ $project->name }}</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#membersModal" id="openMembersBtn">
                            <i class="bi bi-people me-1"></i> Members
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#timeLogModal" id="openTimeLogBtn">
                            <i class="bi bi-clock-history me-1"></i> Time Log
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="addTaskBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Task
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="boardContainer" class="d-flex gap-3" style="overflow-x:auto; align-items:flex-start;">
                        <p class="text-muted">Loading board…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <input type="hidden" id="taskEditingId">
                        <input type="hidden" id="taskStatusId">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Title</label>
                                <input type="text" name="title" id="taskTitle" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Assignee</label>
                                <select id="taskAssigneeSelect" class="form-select">
                                    <option value="">Unassigned</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Category</label>
                                <select id="taskCategorySelect" class="form-select">
                                    <option value="">No category</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Priority</label>
                                <select name="priority" id="taskPriority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Due Date</label>
                                <input type="date" name="due_date" id="taskDueDate" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Estimated Hours</label>
                                <input type="number" step="0.5" min="0" name="estimated_hours" id="taskEstimatedHours" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Description</label>
                                <textarea name="description" id="taskDescription" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </form>

                    <div id="taskCommentsSection" class="border-top mt-3 pt-3 d-none">
                        <h6>Comments</h6>
                        <div id="taskCommentsList" class="mb-2" style="max-height:180px; overflow-y:auto;"></div>
                        <div class="d-flex gap-2">
                            <input type="text" id="newCommentText" class="form-control form-control-sm" placeholder="Add a comment…">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="submitCommentBtn">Post</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto d-none" id="deleteTaskBtn">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitTaskBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="membersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resource Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr><th>Employee</th><th>Role</th><th>Allocation</th><th style="width:90px;">Action</th></tr>
                            </thead>
                            <tbody id="membersTableBody">
                                <tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <form id="memberForm" class="border-top pt-3">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small">Employee</label>
                                <select id="memberEmployeeSelect" class="form-select">
                                    <option value="">Loading employees…</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Role</label>
                                <input type="text" id="memberRole" class="form-control" placeholder="e.g. Developer">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Allocation %</label>
                                <input type="number" min="1" max="100" id="memberAllocation" class="form-control">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="submitMemberBtn"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="timeLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Time Tracking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr><th>Date</th><th>Employee</th><th>Task</th><th>Hours</th><th style="width:60px;"></th></tr>
                            </thead>
                            <tbody id="timeLogsTableBody">
                                <tr><td colspan="5" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <form id="timeLogForm" class="border-top pt-3">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Employee</label>
                                <select id="timeLogEmployeeSelect" class="form-select">
                                    <option value="">Loading employees…</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Task (optional)</label>
                                <select id="timeLogTaskSelect" class="form-select">
                                    <option value="">General</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Date</label>
                                <input type="date" id="timeLogDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Hours</label>
                                <input type="number" step="0.25" min="0.25" max="24" id="timeLogHours" class="form-control">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="submitTimeLogBtn">Log</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const boardFetchUrl = @json(route($routePrefix.'projects.board.fetch', ['business' => $business->slug, 'project' => $project->id]));
        const boardReorderUrl = @json(route($routePrefix.'projects.board.reorder', ['business' => $business->slug, 'project' => $project->id]));
        const taskStoreUrl = @json(route($routePrefix.'projects.tasks.store', ['business' => $business->slug, 'project' => $project->id]));
        const taskUpdateUrlTemplate = @json(route($routePrefix.'projects.tasks.update', ['business' => $business->slug, 'task' => '__ID__']));
        const taskDestroyUrlTemplate = @json(route($routePrefix.'projects.tasks.destroy', ['business' => $business->slug, 'task' => '__ID__']));
        const taskCommentsFetchUrlTemplate = @json(route($routePrefix.'projects.tasks.comments.fetch', ['business' => $business->slug, 'task' => '__ID__']));
        const taskCommentsStoreUrlTemplate = @json(route($routePrefix.'projects.tasks.comments.store', ['business' => $business->slug, 'task' => '__ID__']));
        const membersFetchUrl = @json(route($routePrefix.'projects.members.fetch', ['business' => $business->slug, 'project' => $project->id]));
        const membersStoreUrl = @json(route($routePrefix.'projects.members.store', ['business' => $business->slug, 'project' => $project->id]));
        const memberDestroyUrlTemplate = @json(route($routePrefix.'projects.members.destroy', ['business' => $business->slug, 'member' => '__ID__']));
        const timeLogsFetchUrl = @json(route($routePrefix.'projects.time-logs.fetch', ['business' => $business->slug, 'project' => $project->id]));
        const timeLogsStoreUrl = @json(route($routePrefix.'projects.time-logs.store', ['business' => $business->slug, 'project' => $project->id]));
        const timeLogDestroyUrlTemplate = @json(route($routePrefix.'projects.time-logs.destroy', ['business' => $business->slug, 'timeLog' => '__ID__']));
        const taskCategoriesFetchUrl = @json(route($routePrefix.'projects.task-categories.fetch', $business->slug));
        const employeeOptionsUrl = @json(route($routePrefix.'organogram.employee-options', $business->slug));

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

        const priorityColors = { low: '#6c757d', medium: '#0d6efd', high: '#fd7e14', urgent: '#dc3545' };

        function taskCardHtml(task) {
            const overdue = task.due_date && !task.completed_at && new Date(task.due_date) < new Date(new Date().toDateString());
            return `
                <div class="card mb-2 task-card" draggable="true" data-task-id="${task.id}" style="cursor:grab; border-left:4px solid ${priorityColors[task.priority] ?? '#6c757d'};">
                    <div class="card-body p-2">
                        ${task.category ? `<span class="badge mb-1" style="background-color:${task.category.color};">${escapeHtml(task.category.name)}</span>` : ''}
                        <div class="small fw-semibold">${escapeHtml(task.title)}</div>
                        <div class="small text-muted">${task.assignee ? escapeHtml(task.assignee.user?.name ?? '') : 'Unassigned'}</div>
                        ${task.due_date ? `<div class="small ${overdue ? 'text-danger fw-bold' : 'text-muted'}"><i class="bi bi-calendar-event"></i> ${formatDate(task.due_date)}</div>` : ''}
                    </div>
                </div>`;
        }

        function columnHtml(status) {
            const tasks = (status.tasks || []).map(taskCardHtml).join('');
            return `
                <div class="board-column" data-status-id="${status.id}" style="min-width:280px; max-width:280px; background:#f8f9fa; border-radius:.5rem; padding:.5rem;">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="fw-semibold small" style="border-bottom:3px solid ${status.color}; padding-bottom:2px;">${escapeHtml(status.name)}</span>
                        <span class="badge bg-secondary">${(status.tasks || []).length}</span>
                    </div>
                    <div class="column-body" data-status-id="${status.id}" style="min-height:80px;">${tasks}</div>
                </div>`;
        }

        window._statusesCache = [];
        window._taskCategoriesCache = [];

        async function loadBoard() {
            const container = document.getElementById('boardContainer');
            try {
                const resp = await fetch(boardFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const statuses = payload.data ?? [];
                window._statusesCache = statuses;

                container.innerHTML = statuses.map(columnHtml).join('') || '<p class="text-muted">No columns configured.</p>';
                bindDragAndDrop();
                bindCardClicks();
            } catch (e) {
                container.innerHTML = '<p class="text-danger">Could not load board.</p>';
            }
        }

        function bindCardClicks() {
            document.querySelectorAll('.task-card').forEach(card => {
                card.addEventListener('click', function (e) {
                    openTaskModal(this.dataset.taskId);
                });
            });
        }

        // ---- Drag & drop (native HTML5) ------------------------------------

        let draggedTaskId = null;

        function bindDragAndDrop() {
            document.querySelectorAll('.task-card').forEach(card => {
                card.addEventListener('dragstart', function () {
                    draggedTaskId = this.dataset.taskId;
                    this.classList.add('opacity-50');
                });
                card.addEventListener('dragend', function () {
                    this.classList.remove('opacity-50');
                });
            });

            document.querySelectorAll('.column-body').forEach(column => {
                column.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    const afterElement = getDragAfterElement(this, e.clientY);
                    const dragged = document.querySelector(`.task-card[data-task-id="${draggedTaskId}"]`);
                    if (!dragged) return;
                    if (afterElement == null) this.appendChild(dragged);
                    else this.insertBefore(dragged, afterElement);
                });
                column.addEventListener('drop', function (e) {
                    e.preventDefault();
                    persistBoardOrder();
                });
            });
        }

        function getDragAfterElement(container, y) {
            const cards = [...container.querySelectorAll('.task-card:not(.opacity-50)')];
            return cards.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) return { offset, element: child };
                return closest;
            }, { offset: -Infinity }).element;
        }

        async function persistBoardOrder() {
            const columns = [...document.querySelectorAll('.column-body')].map(col => ({
                status_id: parseInt(col.dataset.statusId, 10),
                task_ids: [...col.querySelectorAll('.task-card')].map(c => parseInt(c.dataset.taskId, 10)),
            }));

            try {
                await postJson(boardReorderUrl, { columns });
                loadBoard();
            } catch (e) {
                toastr.error(e.message || 'Could not save board order.');
                loadBoard();
            }
        }

        // ---- Add/Edit Task ---------------------------------------------------

        async function loadTaskFormOptions(selectedAssigneeId, selectedCategoryId) {
            const assigneeSelect = document.getElementById('taskAssigneeSelect');
            const categorySelect = document.getElementById('taskCategorySelect');

            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                assigneeSelect.innerHTML = '<option value="">Unassigned</option>'
                    + employees.map(e => `<option value="${e.id}">${escapeHtml(e.name)}</option>`).join('');
                assigneeSelect.value = selectedAssigneeId ?? '';
            } catch (e) {
                assigneeSelect.innerHTML = '<option value="">Unassigned</option>';
            }

            if (!window._taskCategoriesCache.length) {
                try {
                    const resp = await fetch(taskCategoriesFetchUrl, { headers: { 'Accept': 'application/json' } });
                    const payload = await resp.json();
                    window._taskCategoriesCache = payload.data ?? [];
                } catch (e) { /* leave empty */ }
            }
            categorySelect.innerHTML = '<option value="">No category</option>'
                + window._taskCategoriesCache.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            categorySelect.value = selectedCategoryId ?? '';
        }

        document.getElementById('addTaskBtn').addEventListener('click', async function () {
            document.getElementById('taskModalTitle').textContent = 'Add Task';
            document.getElementById('taskForm').reset();
            document.getElementById('taskEditingId').value = '';
            document.getElementById('taskStatusId').value = window._statusesCache[0]?.id ?? '';
            document.getElementById('taskCommentsSection').classList.add('d-none');
            document.getElementById('deleteTaskBtn').classList.add('d-none');
            await loadTaskFormOptions(null, null);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('taskModal')).show();
        });

        async function openTaskModal(taskId) {
            let task = null;
            for (const status of window._statusesCache) {
                task = (status.tasks || []).find(t => String(t.id) === String(taskId));
                if (task) break;
            }
            if (!task) return;

            document.getElementById('taskModalTitle').textContent = 'Edit Task';
            document.getElementById('taskEditingId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskDueDate').value = toDateInputValue(task.due_date);
            document.getElementById('taskEstimatedHours').value = task.estimated_hours ?? '';
            document.getElementById('taskDescription').value = task.description ?? '';
            document.getElementById('deleteTaskBtn').classList.remove('d-none');
            await loadTaskFormOptions(task.assignee_employee_id, task.project_task_category_id);
            await loadComments(task.id);
            document.getElementById('taskCommentsSection').classList.remove('d-none');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('taskModal')).show();
        }

        document.getElementById('submitTaskBtn').addEventListener('click', async function () {
            const form = document.getElementById('taskForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            data.assignee_employee_id = document.getElementById('taskAssigneeSelect').value || null;
            data.project_task_category_id = document.getElementById('taskCategorySelect').value || null;
            const editingId = document.getElementById('taskEditingId').value;

            try {
                if (editingId) {
                    await postJson(taskUpdateUrlTemplate.replace('__ID__', editingId), data);
                    toastr.success('Task updated.');
                } else {
                    data.project_task_status_id = document.getElementById('taskStatusId').value || null;
                    await postJson(taskStoreUrl, data);
                    toastr.success('Task added.');
                }
                bootstrap.Modal.getInstance(document.getElementById('taskModal'))?.hide();
                loadBoard();
            } catch (e) {
                toastr.error(e.message || 'Could not save task.');
            }
        });

        document.getElementById('deleteTaskBtn').addEventListener('click', async function () {
            const editingId = document.getElementById('taskEditingId').value;
            if (!editingId) return;
            const { isConfirmed } = await Swal.fire({
                title: 'Are you sure?',
                text: 'Delete this task?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes',
            });
            if (!isConfirmed) return;
            try {
                await postJson(taskDestroyUrlTemplate.replace('__ID__', editingId), {}, 'DELETE');
                toastr.success('Task deleted.');
                bootstrap.Modal.getInstance(document.getElementById('taskModal'))?.hide();
                loadBoard();
            } catch (e) {
                toastr.error(e.message || 'Could not delete task.');
            }
        });

        // ---- Comments --------------------------------------------------------

        async function loadComments(taskId) {
            const list = document.getElementById('taskCommentsList');
            try {
                const resp = await fetch(taskCommentsFetchUrlTemplate.replace('__ID__', taskId), { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const comments = payload.data ?? [];
                list.innerHTML = comments.length
                    ? comments.map(c => `<div class="small mb-1"><strong>${escapeHtml(c.employee?.user?.name ?? 'N/A')}:</strong> ${escapeHtml(c.comment)}</div>`).join('')
                    : '<div class="small text-muted">No comments yet.</div>';
            } catch (e) {
                list.innerHTML = '<div class="small text-danger">Could not load comments.</div>';
            }
        }

        document.getElementById('submitCommentBtn').addEventListener('click', async function () {
            const taskId = document.getElementById('taskEditingId').value;
            const text = document.getElementById('newCommentText').value.trim();
            if (!taskId || !text) return;

            try {
                await postJson(taskCommentsStoreUrlTemplate.replace('__ID__', taskId), { comment: text });
                document.getElementById('newCommentText').value = '';
                loadComments(taskId);
            } catch (e) {
                toastr.error(e.message || 'Could not post comment.');
            }
        });

        // ---- Members (Resource Allocation) ------------------------------------

        async function loadMembers() {
            const tbody = document.getElementById('membersTableBody');
            try {
                const resp = await fetch(membersFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const members = (payload.data ?? []).filter(m => !m.left_at);

                if (!members.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No members yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = members.map(m => `
                    <tr>
                        <td>${escapeHtml(m.employee?.user?.name ?? 'N/A')}</td>
                        <td>${escapeHtml(m.role_on_project ?? '—')}</td>
                        <td>${m.allocation_percentage ? m.allocation_percentage + '%' : '—'}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-id="${m.id}">Remove</button></td>
                    </tr>`).join('');

                document.querySelectorAll('.remove-member-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Remove this member from the project?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(memberDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Member removed.');
                            loadMembers();
                        } catch (e) {
                            toastr.error(e.message || 'Could not remove member.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Could not load members.</td></tr>';
            }
        }

        async function loadMemberEmployeeOptions() {
            const select = document.getElementById('memberEmployeeSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = employees.map(e => `<option value="${e.id}">${escapeHtml(e.name)}</option>`).join('');
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        }

        document.getElementById('openMembersBtn').addEventListener('click', function () {
            loadMembers();
            loadMemberEmployeeOptions();
        });

        document.getElementById('submitMemberBtn').addEventListener('click', async function () {
            const employeeId = document.getElementById('memberEmployeeSelect').value;
            if (!employeeId) { toastr.error('Select an employee.'); return; }

            try {
                await postJson(membersStoreUrl, {
                    employee_id: employeeId,
                    role_on_project: document.getElementById('memberRole').value || null,
                    allocation_percentage: document.getElementById('memberAllocation').value || null,
                });
                toastr.success('Member added.');
                document.getElementById('memberForm').reset();
                loadMembers();
            } catch (e) {
                toastr.error(e.message || 'Could not add member.');
            }
        });

        // ---- Time Log ----------------------------------------------------

        async function loadTimeLogs() {
            const tbody = document.getElementById('timeLogsTableBody');
            try {
                const resp = await fetch(timeLogsFetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const logs = payload.data ?? [];

                if (!logs.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No time logged yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = logs.map(l => `
                    <tr>
                        <td>${formatDate(l.date)}</td>
                        <td>${escapeHtml(l.employee?.user?.name ?? 'N/A')}</td>
                        <td>${escapeHtml(l.task?.title ?? 'General')}</td>
                        <td>${l.hours}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-timelog-btn" data-id="${l.id}">✕</button></td>
                    </tr>`).join('');

                document.querySelectorAll('.remove-timelog-btn').forEach(btn => {
                    btn.addEventListener('click', async function () {
                        const { isConfirmed } = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Remove this time entry?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Yes',
                        });
                        if (!isConfirmed) return;
                        try {
                            await postJson(timeLogDestroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                            toastr.success('Time entry removed.');
                            loadTimeLogs();
                        } catch (e) {
                            toastr.error(e.message || 'Could not remove entry.');
                        }
                    });
                });
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Could not load time logs.</td></tr>';
            }
        }

        async function loadTimeLogFormOptions() {
            const employeeSelect = document.getElementById('timeLogEmployeeSelect');
            const taskSelect = document.getElementById('timeLogTaskSelect');

            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                employeeSelect.innerHTML = employees.map(e => `<option value="${e.id}">${escapeHtml(e.name)}</option>`).join('');
            } catch (e) {
                employeeSelect.innerHTML = '<option value="">Could not load employees</option>';
            }

            const allTasks = window._statusesCache.flatMap(s => s.tasks || []);
            taskSelect.innerHTML = '<option value="">General</option>'
                + allTasks.map(t => `<option value="${t.id}">${escapeHtml(t.title)}</option>`).join('');
        }

        document.getElementById('openTimeLogBtn').addEventListener('click', function () {
            loadTimeLogs();
            loadTimeLogFormOptions();
        });

        document.getElementById('submitTimeLogBtn').addEventListener('click', async function () {
            const employeeId = document.getElementById('timeLogEmployeeSelect').value;
            const hours = document.getElementById('timeLogHours').value;
            if (!employeeId || !hours) { toastr.error('Select an employee and enter hours.'); return; }

            try {
                await postJson(timeLogsStoreUrl, {
                    employee_id: employeeId,
                    project_task_id: document.getElementById('timeLogTaskSelect').value || null,
                    date: document.getElementById('timeLogDate').value,
                    hours,
                });
                toastr.success('Time logged.');
                document.getElementById('timeLogHours').value = '';
                loadTimeLogs();
            } catch (e) {
                toastr.error(e.message || 'Could not log time.');
            }
        });

        loadBoard();
    })();
    </script>
    @endpush
</x-app-layout>
