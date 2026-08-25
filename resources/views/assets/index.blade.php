<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="statusFilter" style="width:180px;">
                            <option value="">All statuses</option>
                            <option value="available">Available</option>
                            <option value="assigned">Assigned</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="retired">Retired</option>
                        </select>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Asset
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Tag</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Condition</th>
                                    <th>Assigned To</th>
                                    <th style="width:220px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="assetsTableBody">
                                <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAssetForm">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Asset Tag</label>
                                <input type="text" name="asset_tag" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Category</label>
                                <input type="text" name="category" class="form-control" placeholder="e.g. Laptop, Vehicle">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Serial Number</label>
                                <input type="text" name="serial_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Purchase Cost</label>
                                <input type="number" step="0.01" min="0" name="purchase_cost" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Condition</label>
                                <select name="condition" class="form-select">
                                    <option value="new">New</option>
                                    <option value="good" selected>Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAddAssetBtn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignAssetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assignAssetId">
                    <div class="mb-3">
                        <label class="form-label small">Employee</label>
                        <select id="assignEmployeeSelect" class="form-select select2-multiple" style="width:100%;">
                            <option value="">Loading employees…</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">Notes</label>
                        <textarea id="assignNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAssignBtn">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="returnAssetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Return Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="returnAssetId">
                    <div class="mb-3">
                        <label class="form-label small">Condition on Return</label>
                        <select id="returnCondition" class="form-select">
                            <option value="new">New</option>
                            <option value="good" selected>Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">Notes</label>
                        <textarea id="returnNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submitReturnBtn">Confirm Return</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const fetchUrl = @json(route('business.assets.fetch', $business->slug));
        const storeUrl = @json(route('business.assets.store', $business->slug));
        const destroyUrlTemplate = @json(route('business.assets.destroy', ['business' => $business->slug, 'asset' => '__ID__']));
        const assignUrlTemplate = @json(route('business.assets.assign', ['business' => $business->slug, 'asset' => '__ID__']));
        const returnUrlTemplate = @json(route('business.assets.return', ['business' => $business->slug, 'asset' => '__ID__']));
        const employeeOptionsUrl = @json(route('business.organogram.employee-options', $business->slug));

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

        function statusBadge(status) {
            const map = { available: 'success', assigned: 'primary', maintenance: 'warning text-dark', retired: 'secondary' };
            return `<span class="badge bg-${map[status] ?? 'secondary'}">${status}</span>`;
        }

        async function loadAssets() {
            const tbody = document.getElementById('assetsTableBody');
            const status = document.getElementById('statusFilter').value;
            try {
                const resp = await fetch(`${fetchUrl}${status ? '?status=' + status : ''}`, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const assets = payload.data ?? [];

                if (!assets.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No assets found.</td></tr>';
                    return;
                }

                tbody.innerHTML = assets.map(a => `
                    <tr>
                        <td>${escapeHtml(a.name)}</td>
                        <td>${escapeHtml(a.asset_tag)}</td>
                        <td>${escapeHtml(a.category ?? '—')}</td>
                        <td>${statusBadge(a.status)}</td>
                        <td class="text-capitalize">${a.condition ?? '—'}</td>
                        <td>${a.current_assignment ? escapeHtml(a.current_assignment.employee?.user?.name ?? 'N/A') : '—'}</td>
                        <td>
                            ${a.status === 'available' ? `<button type="button" class="btn btn-sm btn-outline-primary assign-btn" data-id="${a.id}">Assign</button>` : ''}
                            ${a.status === 'assigned' ? `<button type="button" class="btn btn-sm btn-outline-success return-btn" data-id="${a.id}">Return</button>` : ''}
                            <button type="button" class="btn btn-sm btn-outline-danger delete-asset-btn" data-id="${a.id}">Delete</button>
                        </td>
                    </tr>`).join('');

                bindRowHandlers();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load assets.</td></tr>';
            }
        }

        function bindRowHandlers() {
            document.querySelectorAll('.assign-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('assignAssetId').value = this.dataset.id;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('assignAssetModal')).show();
                });
            });
            document.querySelectorAll('.return-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('returnAssetId').value = this.dataset.id;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('returnAssetModal')).show();
                });
            });
            document.querySelectorAll('.delete-asset-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const { isConfirmed } = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete this asset?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes',
                    });
                    if (!isConfirmed) return;
                    try {
                        await postJson(destroyUrlTemplate.replace('__ID__', this.dataset.id), {}, 'DELETE');
                        toastr.success('Asset deleted.');
                        loadAssets();
                    } catch (e) {
                        toastr.error(e.message || 'Could not delete asset.');
                    }
                });
            });
        }

        document.getElementById('statusFilter').addEventListener('change', loadAssets);

        document.getElementById('submitAddAssetBtn').addEventListener('click', async function () {
            const form = document.getElementById('addAssetForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());

            try {
                await postJson(storeUrl, data);
                toastr.success('Asset added.');
                bootstrap.Modal.getInstance(document.getElementById('addAssetModal'))?.hide();
                form.reset();
                loadAssets();
            } catch (e) {
                toastr.error(e.message || 'Could not add asset.');
            }
        });

        document.getElementById('assignAssetModal').addEventListener('show.bs.modal', async function () {
            const select = document.getElementById('assignEmployeeSelect');
            try {
                const resp = await fetch(employeeOptionsUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const employees = payload.data ?? [];
                select.innerHTML = employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
                if (window.jQuery) $(select).select2({ dropdownParent: $('#assignAssetModal') });
            } catch (e) {
                select.innerHTML = '<option value="">Could not load employees</option>';
            }
        });

        document.getElementById('submitAssignBtn').addEventListener('click', async function () {
            const assetId = document.getElementById('assignAssetId').value;
            const employeeId = document.getElementById('assignEmployeeSelect').value;
            if (!employeeId) { toastr.error('Select an employee.'); return; }

            try {
                await postJson(assignUrlTemplate.replace('__ID__', assetId), {
                    employee_id: employeeId,
                    notes: document.getElementById('assignNotes').value,
                });
                toastr.success('Asset assigned.');
                bootstrap.Modal.getInstance(document.getElementById('assignAssetModal'))?.hide();
                document.getElementById('assignNotes').value = '';
                loadAssets();
            } catch (e) {
                toastr.error(e.message || 'Could not assign asset.');
            }
        });

        document.getElementById('submitReturnBtn').addEventListener('click', async function () {
            const assetId = document.getElementById('returnAssetId').value;

            try {
                await postJson(returnUrlTemplate.replace('__ID__', assetId), {
                    return_condition: document.getElementById('returnCondition').value,
                    notes: document.getElementById('returnNotes').value,
                });
                toastr.success('Asset returned.');
                bootstrap.Modal.getInstance(document.getElementById('returnAssetModal'))?.hide();
                document.getElementById('returnNotes').value = '';
                loadAssets();
            } catch (e) {
                toastr.error(e.message || 'Could not return asset.');
            }
        });

        loadAssets();
    })();
    </script>
    @endpush
</x-app-layout>
