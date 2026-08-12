<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">Performance Cycles</h5>
                            <small class="text-muted">Define the timeline and how KPI, OKR, and competency scores are weighted into an overall score.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('business.employees.index', $business->slug) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-people me-1"></i> Manage Employee Objectives
                            </a>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newCycleModal">
                                <i class="bi bi-plus-circle me-1"></i> New Cycle
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-3">
                        <strong>How this works:</strong>
                        <ol class="mb-0 ps-3">
                            <li>Create a cycle below with its timeline, KPI/OKR/competency weights, and review due dates.</li>
                            <li>Set its status to <strong>Active</strong> once ready - only active cycles show up for employees to set objectives and KPIs against.</li>
                            <li>Go to <a href="{{ route('business.employees.index', $business->slug) }}">Employees</a> and click <strong>Performance</strong> on a row to set that employee's objectives, KPIs, and (once due dates pass) reviews.</li>
                            <li>Set the cycle to <strong>Closed</strong> once reviews are done - this grades every objective's final stretch-goal score.</li>
                        </ol>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="cyclesTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Period</th>
                                    <th>KPI / OKR / Competency</th>
                                    <th>Review Due Dates</th>
                                    <th>Status</th>
                                    <th style="width:160px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cyclesTableBody">
                                <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Cycle Modal -->
    <div class="modal fade" id="newCycleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Performance Cycle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newCycleForm">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="form-label">KPI Weight %</label>
                                <input type="number" name="kpi_weight" class="form-control weight-field" value="40" min="0" max="100" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">OKR Weight %</label>
                                <input type="number" name="okr_weight" class="form-control weight-field" value="40" min="0" max="100" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Competency %</label>
                                <input type="number" name="competency_weight" class="form-control weight-field" value="20" min="0" max="100" required>
                            </div>
                        </div>
                        <small id="weightSumHint" class="text-muted d-block mb-3"></small>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Self-Review Due</label>
                                <input type="date" name="self_review_due_date" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Manager Review Due</label>
                                <input type="date" name="manager_review_due_date" class="form-control">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="lockGoalsOnStart" name="lock_goals_on_start" checked>
                            <label class="form-check-label" for="lockGoalsOnStart">Lock goals once the cycle starts</label>
                            <small class="form-text text-muted d-block">When active, employees can still update progress but can no longer add new objectives or key results - prevents retroactively adjusting targets mid-cycle.</small>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitCycleBtn">Create Cycle</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const fetchUrl = @json(route('business.performance.cycles.fetch', ['business' => $business->slug]));
        const storeUrl = @json(route('business.performance.cycles.store', ['business' => $business->slug]));
        const statusUrlTemplate = @json(route('business.performance.cycles.status', ['business' => $business->slug, 'cycle' => '__ID__']));

        function statusBadge(status) {
            const map = { draft: 'secondary', active: 'success', closed: 'dark' };
            return `<span class="badge bg-${map[status] ?? 'secondary'}">${status}</span>`;
        }

        async function loadCycles() {
            const tbody = document.getElementById('cyclesTableBody');
            try {
                const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const cycles = payload.data ?? [];

                if (!cycles.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No cycles yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = cycles.map(c => `
                    <tr>
                        <td>${c.name}</td>
                        <td>${c.start_date} &ndash; ${c.end_date}</td>
                        <td>${c.kpi_weight}% / ${c.okr_weight}% / ${c.competency_weight}%</td>
                        <td class="small">Self: ${c.self_review_due_date ?? '—'}<br>Manager: ${c.manager_review_due_date ?? '—'}</td>
                        <td>${statusBadge(c.status)} ${c.lock_goals_on_start ? '<i class="bi bi-lock-fill text-muted ms-1" title="Goals lock once active"></i>' : ''}</td>
                        <td>
                            <select class="form-select form-select-sm cycle-status-select" data-id="${c.id}">
                                <option value="draft" ${c.status === 'draft' ? 'selected' : ''}>Draft</option>
                                <option value="active" ${c.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="closed" ${c.status === 'closed' ? 'selected' : ''}>Closed</option>
                            </select>
                        </td>
                    </tr>
                `).join('');

                document.querySelectorAll('.cycle-status-select').forEach(sel => {
                    sel.addEventListener('change', async function () {
                        const url = statusUrlTemplate.replace('__ID__', this.dataset.id);
                        await fetch(url, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ status: this.value }),
                        });
                        loadCycles();
                    });
                });
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load cycles.</td></tr>';
            }
        }

        function updateWeightHint() {
            const fields = document.querySelectorAll('.weight-field');
            const sum = Array.from(fields).reduce((acc, f) => acc + (parseFloat(f.value) || 0), 0);
            const hint = document.getElementById('weightSumHint');
            hint.textContent = `Total: ${sum}% ${sum === 100 ? '' : '(must equal 100%)'}`;
            hint.className = sum === 100 ? 'text-success d-block mb-3' : 'text-danger d-block mb-3';
        }
        document.querySelectorAll('.weight-field').forEach(f => f.addEventListener('input', updateWeightHint));

        document.getElementById('submitCycleBtn').addEventListener('click', async function () {
            const form = document.getElementById('newCycleForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const data = Object.fromEntries(new FormData(form).entries());
            data.lock_goals_on_start = document.getElementById('lockGoalsOnStart').checked;

            try {
                const resp = await fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(data),
                });
                const payload = await resp.json();

                if (!resp.ok) {
                    toastr.error(payload.message || 'Could not create cycle.');
                    return;
                }

                toastr.success('Performance cycle created.');
                bootstrap.Modal.getInstance(document.getElementById('newCycleModal')).hide();
                form.reset();
                updateWeightHint();
                loadCycles();
            } catch (e) {
                console.error(e);
                toastr.error('Could not create cycle.');
            }
        });

        updateWeightHint();
        loadCycles();
    })();
    </script>
</x-app-layout>
