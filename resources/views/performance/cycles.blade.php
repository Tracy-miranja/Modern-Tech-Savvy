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
                            <a href="{{ route('business.performance.objectives.index', $business->slug) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-person-check me-1"></i> Manage Employee Objectives
                            </a>
                            <a href="{{ route('business.performance.setup.index', $business->slug) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> New Cycle
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-3">
                        <strong>How this works:</strong>
                        <ol class="mb-0 ps-3">
                            <li>Create a cycle in <a href="{{ route('business.performance.setup.index', $business->slug) }}">Setup</a> with its timeline, KPI/OKR/competency weights, and review due dates.</li>
                            <li>Set its status to <strong>Active</strong> once ready - only active cycles show up for employees to set objectives and KPIs against.</li>
                            <li>Use <strong>Manage Employee Objectives</strong> above to pick an employee and set their objectives, KPIs, and (once due dates pass) reviews - all from right here in Performance.</li>
                            <li>Set the cycle to <strong>Closed</strong> once reviews are done - this grades every objective's final stretch-goal score.</li>
                        </ol>
                    </div>

                    <div id="criticalObjectivesPanel" class="alert alert-danger d-none mb-3">
                        <strong><i class="bi bi-exclamation-triangle me-1"></i> Needs Attention</strong> - objectives that have
                        fallen behind schedule with little runway left to recover:
                        <ul id="criticalObjectivesList" class="mb-0 mt-2 small"></ul>
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

    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const fetchUrl = @json(route('business.performance.cycles.fetch', ['business' => $business->slug]));
        const statusUrlTemplate = @json(route('business.performance.cycles.status', ['business' => $business->slug, 'cycle' => '__ID__']));
        const criticalObjectivesUrl = @json(route('business.performance.objectives.critical', ['business' => $business->slug]));
        const employeeUrlTemplate = @json(route('business.performance.employee', ['business' => $business->slug, 'employee' => '__ID__']));

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
                        <td>${formatDateRange(c.start_date, c.end_date)}</td>
                        <td>${c.kpi_weight}% / ${c.okr_weight}% / ${c.competency_weight}%</td>
                        <td class="small">Self: ${formatDate(c.self_review_due_date)}<br>Manager: ${formatDate(c.manager_review_due_date)}</td>
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

        async function loadCriticalObjectives() {
            const panel = document.getElementById('criticalObjectivesPanel');
            const list = document.getElementById('criticalObjectivesList');
            try {
                const resp = await fetch(criticalObjectivesUrl, { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) { panel.classList.add('d-none'); return; }
                const payload = await resp.json();
                const objectives = payload.data ?? [];

                if (!objectives.length) {
                    panel.classList.add('d-none');
                    return;
                }

                list.innerHTML = objectives.map(o => `
                    <li>
                        <a href="${employeeUrlTemplate.replace('__ID__', o.employee_id)}" class="alert-link">
                            ${o.employee?.user?.name ?? 'Employee'}
                        </a> - ${o.title} (${Math.round(o.progress)}% complete)
                    </li>
                `).join('');
                panel.classList.remove('d-none');
            } catch (e) {
                console.error(e);
                panel.classList.add('d-none');
            }
        }

        loadCycles();
        loadCriticalObjectives();
    })();
    </script>
</x-app-layout>
