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
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignManagerModal">
                            <i class="bi bi-diagram-3 me-1"></i> Assign Manager
                        </button>
                    </div>
                    <div class="d-flex gap-3 small text-muted mb-2">
                        <span><span class="badge bg-warning text-dark">Manual</span> = manually pinned, won't follow structure changes</span>
                    </div>
                    <div id="orgChartContainer">
                        <div class="text-center text-muted py-4">
                            <span class="spinner-border spinner-border-sm me-1"></span> Loading organogram…
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Manager Modal -->
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAssignManagerBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const businessSlug = @json($business->slug);
        const fetchUrl = @json(route('business.organogram.fetch', ['business' => $business->slug]));
        const optionsUrl = @json(route('business.organogram.employee-options', ['business' => $business->slug]));
        const assignUrl = @json(route('business.organogram.assign-manager', ['business' => $business->slug]));
        const resetUrl = @json(route('business.organogram.reset-manager', ['business' => $business->slug]));
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function renderNode(node) {
            const childrenHtml = (node.children && node.children.length)
                ? `<ul class="list-unstyled ms-4 border-start ps-3">${node.children.map(renderNode).join('')}</ul>`
                : '';

            return `
                <li class="mb-2">
                    <div class="d-inline-flex align-items-center gap-2 border rounded px-2 py-1">
                        <i class="bi bi-person-circle"></i>
                        <span class="fw-semibold">${node.name}</span>
                        <span class="text-muted small">${node.employee_code ?? ''}</span>
                        ${node.department ? `<span class="badge bg-light text-dark border">${node.department}</span>` : ''}
                        ${node.organogram_role ? `<span class="badge bg-info text-dark">${node.organogram_role}</span>` : ''}
                        ${node.manager_override ? `<span class="badge bg-warning text-dark reset-manager-btn" style="cursor:pointer" data-employee-id="${node.id}" title="Click to reset to structure default">Manual</span>` : ''}
                    </div>
                    ${childrenHtml}
                </li>
            `;
        }

        async function loadChart() {
            const container = document.getElementById('orgChartContainer');
            try {
                const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const tree = payload.data ?? payload;

                if (!tree || !tree.length) {
                    container.innerHTML = '<p class="text-muted">No employees found for this business yet.</p>';
                    return;
                }

                container.innerHTML = `<ul class="list-unstyled">${tree.map(renderNode).join('')}</ul>`;

                container.querySelectorAll('.reset-manager-btn').forEach(el => {
                    el.addEventListener('click', async function () {
                        if (!confirm('Reset this employee to the organization structure default?')) return;
                        try {
                            const resp = await fetch(resetUrl, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                                body: JSON.stringify({ employee_id: this.dataset.employeeId }),
                            });
                            const payload = await resp.json();
                            if (!resp.ok) { toastr.error(payload.message || 'Could not reset.'); return; }
                            toastr.success('Reset to structure default.');
                            loadChart();
                        } catch (e) {
                            console.error(e);
                            toastr.error('Could not reset.');
                        }
                    });
                });
            } catch (e) {
                console.error(e);
                container.innerHTML = '<p class="text-danger">Could not load the organogram.</p>';
            }
        }

        async function loadEmployeeOptions() {
            const resp = await fetch(optionsUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await resp.json();
            const employees = payload.data ?? payload;

            const employeeSelect = document.getElementById('assignEmployeeSelect');
            const managerSelect = document.getElementById('assignManagerSelect');

            employeeSelect.innerHTML = employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            managerSelect.innerHTML = '<option value="">— None (top of the chart) —</option>'
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
                loadChart();
            } catch (e) {
                console.error(e);
                toastr.error('Could not assign manager.');
            }
        });

        loadChart();
    })();
    </script>
</x-app-layout>
