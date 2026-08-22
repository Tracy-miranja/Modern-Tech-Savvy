<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">{{ $page }}</h5>
                        <small class="text-muted">A checklist is created automatically whenever an employee is terminated.</small>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm" id="openOffboardingReportsBtn">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports
                    </button>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="offboardingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-active" type="button">
                                Currently Offboarding <span class="badge bg-warning text-dark ms-1">{{ $active->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-completed" type="button">
                                Completed <span class="badge bg-success ms-1">{{ $completed->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        @foreach (['tab-active' => $active, 'tab-completed' => $completed] as $tabId => $list)
                            <div class="tab-pane fade {{ $tabId === 'tab-active' ? 'show active' : '' }}" id="{{ $tabId }}">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Department</th>
                                                <th>Initiated</th>
                                                <th>Progress</th>
                                                <th style="width:160px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($list as $checklist)
                                                <tr>
                                                    <td>{{ optional($checklist->employee->user)->name ?? 'N/A' }}</td>
                                                    <td>{{ optional($checklist->employee->department)->name ?? '—' }}</td>
                                                    <td>{{ $checklist->initiated_at->format('M d, Y') }}</td>
                                                    <td style="min-width:140px;">
                                                        <div class="progress" style="height:8px;">
                                                            <div class="progress-bar bg-success" style="width:{{ $checklist->progressPercent() }}%"></div>
                                                        </div>
                                                        <small class="text-muted">{{ $checklist->progressPercent() }}%</small>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#checklistModal{{ $checklist->id }}">
                                                            <i class="bi bi-list-check me-1"></i> Checklist
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center text-muted">None.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($active->concat($completed) as $checklist)
        <div class="modal fade" id="checklistModal{{ $checklist->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Offboarding Checklist - {{ optional($checklist->employee->user)->name ?? 'N/A' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="assignedAssets{{ $checklist->id }}" class="alert alert-light border small d-none mb-3" data-employee-id="{{ $checklist->employee_id }}"></div>
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>Task</th>
                                    <th>Notes</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tasksBody{{ $checklist->id }}">
                                @foreach ($checklist->tasks as $task)
                                    <tr data-task-id="{{ $task->id }}">
                                        <td><input type="checkbox" class="task-done-check" {{ $task->is_done ? 'checked' : '' }}></td>
                                        <td>{{ $task->name }}</td>
                                        <td><input type="text" class="form-control form-control-sm task-notes-input" value="{{ $task->notes }}" placeholder="Notes…"></td>
                                        <td><button type="button" class="btn btn-sm btn-link text-danger delete-task-btn" title="Remove task"><i class="bi bi-x-lg"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <form class="row g-2 add-task-form" data-checklist-id="{{ $checklist->id }}">
                            <div class="col-8">
                                <input type="text" class="form-control form-control-sm new-task-name" placeholder="Add a task…">
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Add</button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-outline-secondary btn-sm" target="_blank"
                            href="{{ route('business.offboarding.reports.clearance-summary.download', [$business->slug, $checklist->id]) }}">
                            <i class="bi bi-printer me-1"></i> Print Clearance Summary
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @include('components.reports.modal')

    @push('scripts')
    <script src="{{ asset('js/main/report-modal.js') }}"></script>
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

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

        function checklistIdFor(el) {
            return el.closest('[id^="tasksBody"]')?.id.replace('tasksBody', '')
                ?? el.closest('.add-task-form')?.dataset.checklistId;
        }

        // Assigned assets panel - only shows if the Asset Management module
        // is active for this business (the endpoint 404s/403s otherwise,
        // in which case the panel just stays hidden - no hard error).
        document.querySelectorAll('[id^="checklistModal"]').forEach(modalEl => {
            modalEl.addEventListener('show.bs.modal', async function () {
                const panel = modalEl.querySelector('[id^="assignedAssets"]');
                if (!panel || panel.dataset.loaded) return;
                panel.dataset.loaded = '1';

                try {
                    const resp = await fetch(`/business/{{ $business->slug }}/assets/employees/${panel.dataset.employeeId}/assigned`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!resp.ok) return;
                    const payload = await resp.json();
                    const assignments = payload.data ?? [];
                    if (!assignments.length) return;

                    panel.classList.remove('d-none');
                    panel.innerHTML = '<strong>Assigned Assets Still Outstanding:</strong> '
                        + assignments.map(a => a.asset?.name).filter(Boolean).join(', ');
                } catch (e) {
                    // Module not active or request failed - leave the panel hidden.
                }
            });
        });

        document.querySelectorAll('.task-done-check').forEach(el => {
            el.addEventListener('change', async function () {
                const row = this.closest('tr');
                const taskId = row.dataset.taskId;
                const checklistId = checklistIdFor(row);
                try {
                    await postJson(`/business/{{ $business->slug }}/offboarding/${checklistId}/tasks/${taskId}`, {
                        is_done: this.checked,
                        notes: row.querySelector('.task-notes-input').value,
                    });
                } catch (e) {
                    this.checked = !this.checked;
                    if (window.toastr) toastr.error(e.message || 'Could not update task.');
                }
            });
        });

        document.querySelectorAll('.task-notes-input').forEach(el => {
            el.addEventListener('blur', async function () {
                const row = this.closest('tr');
                const taskId = row.dataset.taskId;
                const checklistId = checklistIdFor(row);
                try {
                    await postJson(`/business/{{ $business->slug }}/offboarding/${checklistId}/tasks/${taskId}`, {
                        is_done: row.querySelector('.task-done-check').checked,
                        notes: this.value,
                    });
                } catch (e) {
                    if (window.toastr) toastr.error(e.message || 'Could not save notes.');
                }
            });
        });

        document.querySelectorAll('.delete-task-btn').forEach(el => {
            el.addEventListener('click', async function () {
                const { isConfirmed } = await Swal.fire({
                    title: 'Are you sure?',
                    text: 'Remove this task?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                });
                if (!isConfirmed) return;
                const row = this.closest('tr');
                const taskId = row.dataset.taskId;
                const checklistId = checklistIdFor(row);
                try {
                    await postJson(`/business/{{ $business->slug }}/offboarding/${checklistId}/tasks/${taskId}`, {}, 'DELETE');
                    row.remove();
                } catch (e) {
                    if (window.toastr) toastr.error(e.message || 'Could not remove task.');
                }
            });
        });

        document.querySelectorAll('.add-task-form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const input = this.querySelector('.new-task-name');
                const name = input.value.trim();
                if (!name) return;
                const checklistId = this.dataset.checklistId;

                try {
                    const payload = await postJson(`/business/{{ $business->slug }}/offboarding/${checklistId}/tasks`, { name });
                    const tbody = document.getElementById(`tasksBody${checklistId}`);
                    const tr = document.createElement('tr');
                    tr.dataset.taskId = payload.data.id;
                    tr.innerHTML = `
                        <td><input type="checkbox" class="task-done-check"></td>
                        <td>${name.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}</td>
                        <td><input type="text" class="form-control form-control-sm task-notes-input" placeholder="Notes…"></td>
                        <td><button type="button" class="btn btn-sm btn-link text-danger delete-task-btn" title="Remove task"><i class="bi bi-x-lg"></i></button></td>`;
                    tbody.appendChild(tr);
                    input.value = '';
                } catch (e) {
                    if (window.toastr) toastr.error(e.message || 'Could not add task.');
                }
            });
        });

        ReportModal.init({ employeeOptionsUrl: @json(route('business.organogram.employee-options', $business->slug)) });

        document.getElementById('openOffboardingReportsBtn').addEventListener('click', function () {
            ReportModal.open([
                {
                    key: 'status',
                    label: 'Offboarding Status Report',
                    filters: ['date_range', 'department', 'employee'],
                    previewUrl: @json(route('business.offboarding.reports.status.preview', $business->slug)),
                    downloadUrl: @json(route('business.offboarding.reports.status.download', $business->slug)),
                },
            ]);
        });
    })();
    </script>
    @endpush
</x-app-layout>
