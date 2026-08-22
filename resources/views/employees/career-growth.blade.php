<x-app-layout :title="$page">
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">Career Growth</h5>
                    <p class="text-muted small mb-0">Every promotion and salary increment recorded across the business. To record a new one, open an employee's profile and use the Career History tab.</p>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Event Type</label>
                            <select class="form-select form-select-sm" id="careerGrowthTypeFilter">
                                <option value="">All types</option>
                                <option value="promotion">Promotion</option>
                                <option value="salary_increment">Salary Increment</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select class="form-select form-select-sm" id="careerGrowthStatusFilter">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="applied">Applied</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="careerGrowthTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Effective Date</th>
                                    <th>Change</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const fetchUrl = @json(route('business.career-growth.fetch', $business->slug));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        }

        function describeChange(e) {
            if (e.event_type === 'promotion') {
                const from = e.old_job_category?.name ?? '—';
                const to = e.new_job_category?.name ?? '—';
                return `${escapeHtml(from)} → ${escapeHtml(to)}`;
            }
            const from = e.old_salary !== null ? Number(e.old_salary).toLocaleString() : '—';
            const to = e.new_salary !== null ? Number(e.new_salary).toLocaleString() : '—';
            return `${from} → ${to}`;
        }

        function typeLabel(type) {
            return type === 'promotion' ? 'Promotion' : 'Salary Increment';
        }

        function formatDate(dateString) {
            if (!dateString) return '—';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        let table = null;

        async function loadEvents() {
            const tbody = document.querySelector('#careerGrowthTable tbody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>';

            const params = new URLSearchParams();
            const type = document.getElementById('careerGrowthTypeFilter').value;
            const status = document.getElementById('careerGrowthStatusFilter').value;
            if (type) params.set('event_type', type);
            if (status) params.set('status', status);

            try {
                const resp = await fetch(`${fetchUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const events = payload.data ?? [];

                if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#careerGrowthTable')) {
                    $('#careerGrowthTable').DataTable().destroy();
                    table = null;
                }

                tbody.innerHTML = events.length ? events.map(e => `
                    <tr>
                        <td>${escapeHtml(e.employee?.user?.name ?? 'N/A')}</td>
                        <td>${typeLabel(e.event_type)}</td>
                        <td>${formatDate(e.effective_date)}</td>
                        <td>${describeChange(e)}</td>
                        <td>${escapeHtml(e.reason)}</td>
                        <td><span class="badge ${e.status === 'applied' ? 'bg-success' : 'bg-warning text-dark'}">${escapeHtml(e.status)}</span></td>
                    </tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted">No career growth records yet.</td></tr>';

                if (window.jQuery && $.fn.DataTable) {
                    table = $('#careerGrowthTable').DataTable({ responsive: true, order: [] });
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load career events.</td></tr>';
            }
        }

        document.getElementById('careerGrowthTypeFilter').addEventListener('change', loadEvents);
        document.getElementById('careerGrowthStatusFilter').addEventListener('change', loadEvents);

        loadEvents();
    })();
    </script>
    @endpush
</x-app-layout>
