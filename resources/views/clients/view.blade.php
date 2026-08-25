<x-app-layout>
    <meta name="business-slug" content="{{ session('active_business_slug') }}">
    <div class="container py-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                <h2 class="mb-0 fw-bold">{{ $clientBusiness->company_name }}</h2>
                <a href="{{ route('business.clients.index', [session('active_business_slug')]) }}"
                    class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
            <div class="card-body p-4">

                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card h-100 border-0 rounded-3 bg-light">
                            <div class="card-body p-3 text-center">
                                <div class="fs-4 fw-bold">{{ $clientBusiness->created_at->format('d M Y') }}</div>
                                <div class="small text-muted">Date Registered</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 border-0 rounded-3 bg-light">
                            <div class="card-body p-3 text-center">
                                <div class="fs-4 fw-bold">{{ $employeeCount }}</div>
                                <div class="small text-muted">Employees</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 border-0 rounded-3 bg-light">
                            <div class="card-body p-3 text-center">
                                <div class="fs-4 fw-bold">{{ $userCount }}</div>
                                <div class="small text-muted">Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 border-0 rounded-3 bg-light">
                            <div class="card-body p-3 text-center">
                                <div class="fs-4 fw-bold">{{ count($activeModuleIds) }}<span class="fs-6 text-muted">/{{ $clientBusiness->modules->count() }}</span></div>
                                <div class="small text-muted">Active / Assigned Modules</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-lg-6">
                        <div class="card h-100 border-0 rounded-3">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-3 text-primary">Business Information</h6>
                                <ul class="list-group list-group-flush bg-transparent">
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Name:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->company_name }}</span></li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Industry:</span> <span
                                            class="float-end text-dark">{{ ucfirst($clientBusiness->industry ?? 'N/A') }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Phone:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->phone ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Country:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->country ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Company Size:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->company_size ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom">
                                        <span class="fw-medium">Status:</span>
                                        <span class="float-end">
                                            @if (is_null($clientBusiness->verified))
                                            <span class="badge rounded-pill bg-secondary">Unknown</span>
                                            @elseif ($clientBusiness->verified)
                                            <span class="badge rounded-pill bg-success">Verified</span>
                                            @else
                                            <span class="badge rounded-pill bg-warning text-dark">Pending</span>
                                            @endif
                                        </span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Registration No:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->registration_no ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Tax PIN:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->tax_pin_no ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Business License:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->business_license_no ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2"><span
                                            class="fw-medium">Physical Address:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->physical_address ?? 'N/A' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100 border-0 rounded-3">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-3 text-primary">Creator Details</h6>
                                <ul class="list-group list-group-flush bg-transparent mb-4">
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Name:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->user->name ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Email:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->user->email ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Phone:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->user->phone ?? 'N/A' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-bottom"><span
                                            class="fw-medium">Created:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->created_at->format('d M Y H:i') }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2"><span
                                            class="fw-medium">Updated:</span> <span
                                            class="float-end text-dark">{{ $clientBusiness->updated_at->format('d M Y H:i') }}</span>
                                    </li>
                                </ul>

                                <h6 class="card-title fw-bold mb-3 text-primary">Documents</h6>
                                @if ($clientBusiness->media->isEmpty())
                                <p class="text-muted fst-italic">No documents uploaded</p>
                                @else
                                <ul class="list-group list-group-flush bg-transparent">
                                    @foreach ($clientBusiness->media as $media)
                                    <li
                                        class="list-group-item bg-transparent px-0 py-2 border-bottom d-flex align-items-center">
                                        <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                        <a href="{{ $media->getUrl() }}" target="_blank"
                                            class="text-decoration-none text-truncate">{{ $media->file_name }}</a>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-12">
                        <div class="card border-0 rounded-3 mt-4">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-3 text-primary">Actions</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        @if (!$clientBusiness->verified)
                                        <button class="btn btn-success w-100 rounded-pill"
                                            onclick="verifyBusiness(this, '{{ $clientBusiness->slug }}')">
                                            <i class="bi bi-check-circle me-2"></i> Verify Business
                                        </button>
                                        @else
                                        <button class="btn btn-danger w-100 rounded-pill"
                                            onclick="deactivateBusiness(this, '{{ $clientBusiness->slug }}')">
                                            <i class="bi bi-x-circle me-2"></i> Deactivate Business
                                        </button>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-outline-info w-100 rounded-pill"
                                            onclick="impersonateBusiness('{{ $clientBusiness->slug }}')">
                                            <i class="bi bi-person-lines-fill me-2"></i> Impersonate Business
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 rounded-3 mt-4">
                    <div class="card-body p-4">
                        <h6 class="card-title fw-bold mb-1 text-primary">Modules</h6>
                        <p class="small text-muted mb-3">
                            Edit a module to activate or deactivate it and optionally set when its subscription
                            ends - leave the date blank for an ongoing subscription with no expiry.
                        </p>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Status</th>
                                        <th>Expires</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $module)
                                    @php
                                        $pivot = $clientBusiness->modules->firstWhere('id', $module->id)?->pivot;
                                        $isAssigned = (bool) $pivot;
                                        $isActive = in_array($module->id, $activeModuleIds);
                                        $isExpired = $isAssigned && $pivot->is_active && !$isActive;
                                        $expiry = $pivot?->subscription_ends_at;
@endphp
                                    <tr>
                                        <td>{{ $module->name }}</td>
                                        <td>
                                            @if ($isActive)
                                            <span class="badge bg-success">Active</span>
                                            @elseif ($isExpired)
                                            <span class="badge bg-warning text-dark">Expired</span>
                                            @elseif ($isAssigned)
                                            <span class="badge bg-secondary">Inactive</span>
                                            @else
                                            <span class="badge bg-light text-muted border">Not assigned</span>
                                            @endif
                                        </td>
                                        <td>{{ $expiry ? \Carbon\Carbon::parse($expiry)->format('d M Y') : ($isAssigned ? 'No expiry' : '—') }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="openModuleStatusModal({{ $module->id }}, {{ Js::from($module->name) }}, {{ $isActive ? 'true' : 'false' }}, {{ Js::from($expiry) }})">
                                                {{ $isAssigned ? 'Edit' : 'Assign' }}
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 rounded-3 mt-4">
                    <div class="card-body p-4">
                        <h6 class="card-title fw-bold mb-3 text-primary">Recent Activity</h6>
                        @if ($recentActivities->isEmpty())
                        <p class="text-muted fst-italic mb-0">No activity recorded yet.</p>
                        @else
                        <ul class="list-group list-group-flush bg-transparent">
                            @foreach ($recentActivities as $log)
                            <li class="list-group-item bg-transparent px-0 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="bi bi-clock-history text-muted me-2"></i>
                                    {{ $log->description }}
                                    <span class="text-muted">by {{ $log->causer->name ?? 'System' }}</span>
                                </span>
                                <span class="text-muted small">{{ $log->created_at->diffForHumans() }}</span>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>

                <div class="card border-0 rounded-3 mt-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title fw-bold mb-0 text-primary">Payments</h6>
                            <button type="button" class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                                <i class="bi bi-cash-coin me-1"></i> Record Payment
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Period</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moduleStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0 rounded-3">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="moduleStatusModalTitle">Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="moduleStatusModuleId">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="moduleStatusActive">
                        <label class="form-check-label" for="moduleStatusActive">Active</label>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">Subscription ends</label>
                        <input type="date" class="form-control" id="moduleStatusExpiry">
                        <div class="form-text">Leave blank for an ongoing subscription with no expiry.</div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-pill" id="moduleStatusSaveBtn">
                        <i class="bi bi-save me-2"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0 rounded-3">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="recordPaymentForm">
                        <div class="mb-3">
                            <label class="form-label small">Module (leave blank to cover all their active modules)</label>
                            <select name="module_id" class="form-select">
                                <option value="">All active modules</option>
                                @foreach ($modules as $module)
                                    <option value="{{ $module->id }}">{{ $module->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Amount</label>
                                <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="KES" selected>KES</option>
                                    <option value="USD">USD</option>
                                    <option value="TZS">TZS</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="cash">Cash</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Reference</label>
                                <input type="text" name="reference" class="form-control" placeholder="Transaction ref">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Period Start</label>
                                <input type="date" name="period_start" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Period End</label>
                                <input type="date" name="period_end" class="form-control" value="{{ now()->addYear()->toDateString() }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success rounded-pill" id="submitPaymentBtn">
                        <i class="bi bi-save me-2"></i> Record Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="remarksModal-{{ $clientBusiness->slug }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow border-0 rounded-3">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Remarks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <textarea class="form-control border rounded-3" id="remarks-{{ $clientBusiness->slug }}" rows="4"
                        placeholder="Enter remarks"></textarea>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-pill"
                        onclick="submitRemarks('{{ $clientBusiness->slug }}')">
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/clients.js') }}" type="module"></script>
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const businessSlug = @json(session('active_business_slug'));
        const clientSlug = @json($clientBusiness->slug);
        const fetchUrl = @json(route('business.clients.payments.fetch', [session('active_business_slug'), $clientBusiness->slug]));
        const storeUrl = @json(route('business.clients.payments.store', [session('active_business_slug'), $clientBusiness->slug]));
        const moduleStatusUrl = @json(route('business.clients.modules.update-status', [session('active_business_slug'), $clientBusiness->slug]));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        }

        async function loadPayments() {
            const tbody = document.getElementById('paymentsTableBody');
            try {
                const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                const payload = await resp.json();
                const payments = payload.data ?? [];

                if (!payments.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No payments recorded yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = payments.map(p => `
                    <tr>
                        <td>${escapeHtml(p.module?.name ?? 'All modules')}</td>
                        <td>${escapeHtml(p.currency)} ${Number(p.amount).toLocaleString()}</td>
                        <td class="text-capitalize">${escapeHtml(p.payment_method)}</td>
                        <td>${escapeHtml(p.reference ?? '—')}</td>
                        <td>${p.period_start} &ndash; ${p.period_end}</td>
                        <td>${escapeHtml(p.recorded_by?.name ?? 'N/A')}</td>
                    </tr>`).join('');
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load payments.</td></tr>';
            }
        }

        document.getElementById('submitPaymentBtn').addEventListener('click', async function () {
            const form = document.getElementById('recordPaymentForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());

            try {
                const resp = await fetch(storeUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(data),
                });
                const payload = await resp.json();
                if (!resp.ok) throw new Error(payload.message || 'Could not record payment.');

                Swal.fire({ icon: 'success', title: 'Success', text: payload.message || 'Payment recorded.' });
                bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal'))?.hide();
                form.reset();
                loadPayments();
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Could not record payment.' });
            }
        });

        loadPayments();

        window.openModuleStatusModal = function (moduleId, moduleName, isActive, expiry) {
            document.getElementById('moduleStatusModuleId').value = moduleId;
            document.getElementById('moduleStatusModalTitle').textContent = moduleName;
            document.getElementById('moduleStatusActive').checked = isActive;
            document.getElementById('moduleStatusExpiry').value = expiry ? expiry.substring(0, 10) : '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('moduleStatusModal')).show();
        };

        document.getElementById('moduleStatusSaveBtn').addEventListener('click', async function () {
            const btn = this;
            btn.disabled = true;

            const moduleId = document.getElementById('moduleStatusModuleId').value;
            const isActive = document.getElementById('moduleStatusActive').checked;
            const expiry = document.getElementById('moduleStatusExpiry').value || null;

            try {
                const resp = await fetch(moduleStatusUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ module_id: moduleId, is_active: isActive, subscription_ends_at: expiry }),
                });
                const payload = await resp.json();
                if (!resp.ok) throw new Error(payload.message || 'Could not update module.');

                bootstrap.Modal.getInstance(document.getElementById('moduleStatusModal'))?.hide();
                await Swal.fire({ icon: 'success', title: 'Success', text: payload.message || 'Module updated.' });
                location.reload();
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Could not update module.' });
            } finally {
                btn.disabled = false;
            }
        });
    })();
    </script>
    @endpush
</x-app-layout>