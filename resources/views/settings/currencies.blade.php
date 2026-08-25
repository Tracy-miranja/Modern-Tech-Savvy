
<x-app-layout>

@php
    $slug = $business->slug;

    $routes = [
        'list'          => route('business.currencies.list',        $slug),
        'known'         => route('business.currencies.known',       $slug),
        'store'         => route('business.currencies.store',       $slug),
        'bulk_destroy'  => route('business.currencies.bulk-destroy',$slug),
        'refresh_all'   => route('business.currencies.refresh-all', $slug),

        'currency_base' => route('business.currencies.store',       $slug),
    ];
@endphp

<style>
    .currency-toast {
        position: fixed !important;
        top: 1.25rem !important;
        right: 1.25rem !important;
        min-width: 320px;
        max-width: 480px;
        z-index: 9999 !important;
        box-shadow: 0 4px 20px rgba(0,0,0,.22);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: .75rem 1rem;
    }
    .currency-toast.toast-success {
    background-color: #198754 !important;  /* Bootstrap success green */
    border: 1px solid #146c43 !important;
    color: #ffffff !important;             /* white text on green */
}
.currency-toast.toast-danger {
    background-color: #dc3545 !important;
    border: 1px solid #b02a37 !important;
    color: #ffffff !important;
}
    .fade-enter-active, .fade-leave-active { transition: opacity .25s ease; }
    .fade-enter-from,  .fade-leave-to      { opacity: 0; }
</style>

<div class="container-fluid py-4" id="currency-manager">

    <transition name="fade">
        <div v-if="toast.message"
             :class="['currency-toast', toast.type === 'success' ? 'toast-success' : 'toast-danger']"
             role="alert">
           <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-times-circle'"
   style="font-size:1.1rem;flex-shrink:0;color:white;"></i>
            <span style="flex:1;">@{{ toast.message }}</span>
            <button type="button"
                    style="background:none;border:none;font-size:1.2rem;cursor:pointer;opacity:.6;padding:0 4px;line-height:1;"
                    @click="toast.message = ''">&times;</button>
        </div>
    </transition>

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="fas fa-plus me-1"></i> Add currency
            </button>
            <button class="btn btn-sm"
        :class="selected.length === 0 ? 'btn-secondary' : 'btn-danger'"
        :disabled="selected.length === 0"
        @click="openBulkDeleteModal">
    <i class="fas fa-trash me-1"></i> Delete
</button>
            <div class="btn-group">
                <button class="btn btn-success btn-sm" @click="exportData('csv')">
                    <i class="fas fa-file-csv me-1"></i> Export to
                </button>
                <button class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" @click.prevent="exportData('csv')">CSV</a></li>
                    <li><a class="dropdown-item" href="#" @click.prevent="exportData('xlsx')">Excel</a></li>
                </ul>
            </div>
            <button class="btn btn-secondary btn-sm" @click="refreshAllRates" :disabled="refreshingAll">
                <i class="fas fa-sync-alt me-1" :class="{'fa-spin': refreshingAll}"></i>
                <span v-if="refreshingAll">Refreshing…</span>
                <span v-else>Refresh All Auto Rates</span>
            </button>
        </div>
        <span class="text-muted small">@{{ currencies.length }} currencies</span>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="36"><input type="checkbox" class="form-check-input" v-model="allSelected" @change="toggleAll"></th>
                            <th width="36"></th>
                            <th style="color:#4a90d9;cursor:pointer" @click="sort('id')">#</th>
                            <th style="color:#4a90d9;cursor:pointer" @click="sort('currency_name')">Currency</th>
                            <th style="color:#4a90d9;cursor:pointer" @click="sort('currency_code')">Code</th>
                            <th style="color:#4a90d9;cursor:pointer" @click="sort('symbol')">Symbol</th>
                            <th style="color:#4a90d9">Decimal places</th>
                            <th style="color:#4a90d9">Primary?</th>
                            <th style="color:#4a90d9">Rate mode</th>
                            <th style="color:#4a90d9">Rate (1 unit → primary)</th>
                            <th style="color:#4a90d9">Last updated</th>
                            <th style="color:#4a90d9">Actions</th>
                        </tr>
                        <tr>
                            <td></td><td></td><td></td>
                            <td><input v-model="filters.currency_name" type="text" class="form-control form-control-sm" placeholder="Filter…"></td>
                            <td><input v-model="filters.currency_code" type="text" class="form-control form-control-sm" placeholder="Filter…"></td>
                            <td></td><td></td>
                            <td>
                                <select v-model="filters.is_primary" class="form-select form-select-sm">
                                    <option value="">All</option><option value="1">Yes</option><option value="0">No</option>
                                </select>
                            </td>
                            <td>
                                <select v-model="filters.rate_mode" class="form-select form-select-sm">
                                    <option value="">All</option><option value="auto">Auto</option><option value="manual">Manual</option>
                                </select>
                            </td>
                            <td></td><td></td>
                            <td><button class="btn btn-sm btn-outline-secondary" @click="clearFilters"><i class="fas fa-times"></i></button></td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="12" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading…
                            </td>
                        </tr>
                        <tr v-else-if="filteredCurrencies.length === 0">
                            <td colspan="12" class="text-center py-4 text-muted">
                                No currencies found. Click <strong>Add currency</strong> to get started.
                            </td>
                        </tr>
                        <tr v-for="c in filteredCurrencies" :key="c.id" :class="{'table-warning': c.is_primary}">
                            <td><input type="checkbox" class="form-check-input" :value="c.id" v-model="selected" :disabled="c.is_primary"></td>
                            <td><i class="fas fa-eye text-muted" style="cursor:pointer" @click="viewCurrency(c)"></i></td>
                            <td class="text-muted small">@{{ c.id }}</td>
                            <td>
                                <span class="fw-semibold">@{{ c.currency_name }}</span>
                                <span v-if="c.is_primary" class="badge bg-warning text-dark ms-1">Primary</span>
                            </td>
                            <td><code>@{{ c.currency_code }}</code></td>
                            <td>@{{ c.symbol || '—' }}</td>
                            <td class="text-center">@{{ c.decimal_places }}</td>
                            <td>
                                <span v-if="c.is_primary" class="badge bg-success">Yes</span>
                                <span v-else class="text-muted">No</span>
                            </td>
                            <td>
                                <span class="badge" :class="c.rate_mode === 'auto' ? 'bg-info text-dark' : 'bg-secondary'">
                                    @{{ c.rate_mode_label }}
                                </span>
                            </td>
                            <td>
                                <span v-if="c.is_primary" class="text-muted">1.00 (base)</span>
                                <span v-else>
                                    <span v-if="c.effective_rate" class="fw-semibold">@{{ Number(c.effective_rate).toFixed(6) }}</span>
                                    <span v-else class="text-danger small">Not set</span>
                                    <button v-if="c.rate_mode === 'auto' && !c.is_primary"
                                        class="btn btn-link btn-sm p-0 ms-1 text-info"
                                        :disabled="refreshing[c.id]"
                                        @click="refreshRate(c)">
                                        <i class="fas fa-sync-alt" :class="{'fa-spin': refreshing[c.id]}"></i>
                                    </button>
                                </span>
                            </td>
                            <td class="small text-muted">@{{ c.rate_fetched_at || '—' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" @click.stop="openEditModal(c)">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            @click.stop="openDeleteModal(c)"
                                            :disabled="c.is_primary">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="currencyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ editMode ? 'Edit Currency' : 'Add Currency' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6" v-if="!editMode">
                            <label class="form-label fw-semibold">Quick select</label>
                            <select class="form-select" v-model="form.currency_code" @change="autofillCurrency">
                                <option value="">— Type or select —</option>
                                <option v-for="(meta, code) in knownCurrencies" :key="code" :value="code">
                                    @{{ code }} — @{{ meta.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6" v-if="!editMode">
                            <label class="form-label fw-semibold">Currency code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" v-model="form.currency_code"
                                placeholder="e.g. USD" maxlength="10">
                        </div>
                        <div class="col-md-6" v-if="editMode">
                            <label class="form-label fw-semibold">Currency code</label>
                            <input type="text" class="form-control" :value="form.currency_code" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Currency name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.currency_name" placeholder="e.g. United States Dollar">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Symbol</label>
                            <input type="text" class="form-control" v-model="form.symbol" placeholder="e.g. $" maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Decimal places</label>
                            <input type="number" class="form-control" v-model.number="form.decimal_places" min="0" max="4">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Set as primary?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" v-model="form.is_primary" id="isPrimaryCheck">
                                <label class="form-check-label" for="isPrimaryCheck">
                                    @{{ form.is_primary ? 'Yes — this is the base currency' : 'No' }}
                                </label>
                            </div>
                        </div>
                        <div class="col-12" v-if="!form.is_primary">
                            <label class="form-label fw-semibold">Exchange rate mode</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="modeAuto" v-model="form.rate_mode" value="auto">
                                    <label class="form-check-label" for="modeAuto">
                                        <i class="fas fa-robot text-info me-1"></i> Automatic (live from API)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="modeManual" v-model="form.rate_mode" value="manual">
                                    <label class="form-check-label" for="modeManual">
                                        <i class="fas fa-pencil-alt text-secondary me-1"></i> Manual
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" v-if="form.rate_mode === 'manual' && !form.is_primary">
                            <label class="form-label fw-semibold">
                                Manual rate
                                <span class="text-muted small">(1 unit of this currency = X units of primary)</span>
                            </label>
                            <input type="number" class="form-control" v-model.number="form.manual_rate"
                                step="0.000001" min="0" placeholder="e.g. 129.50">
                            <div class="form-text">
                                Example: primary is KES, this is USD → enter <strong>129.50</strong> (1 USD = 129.50 KES).
                            </div>
                        </div>
                        <div class="col-12" v-if="form.rate_mode === 'auto' && !form.is_primary">
                            <div class="alert alert-info mb-0 py-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Rate will be fetched automatically. You can refresh it at any time.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveCurrency" :disabled="saving">
                        <span v-if="saving"><i class="fas fa-spinner fa-spin me-1"></i>Saving…</span>
                        <span v-else>@{{ editMode ? 'Save changes' : 'Add currency' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center px-4 pt-4 pb-2">
                    <div class="mb-3" style="font-size:3rem;"><i class="fas fa-trash-alt text-danger"></i></div>
                    <h5 class="fw-bold mb-2">Delete currency?</h5>
                    <p class="text-muted mb-0" v-if="deleteTarget">
                        <strong>@{{ deleteTarget.currency_name }}</strong>
                        (<code>@{{ deleteTarget.currency_code }}</code>) will be permanently removed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" :disabled="deleting">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" @click="confirmDelete" :disabled="deleting">
                        <span v-if="deleting"><i class="fas fa-spinner fa-spin me-1"></i>Deleting…</span>
                        <span v-else>Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center px-4 pt-4 pb-2">
                    <div class="mb-3" style="font-size:3rem;"><i class="fas fa-trash-alt text-danger"></i></div>
                    <h5 class="fw-bold mb-2">Delete @{{ selected.length }} currencies?</h5>
                    <p class="text-muted mb-0">This will permanently remove all selected currencies.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" :disabled="deleting">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" @click="confirmBulkDelete" :disabled="deleting">
                        <span v-if="deleting"><i class="fas fa-spinner fa-spin me-1"></i>Deleting…</span>
                        <span v-else>Delete all</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Currency Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" v-if="viewData">
                    <table class="table table-sm">
                        <tr><th>Code</th><td><code>@{{ viewData.currency_code }}</code></td></tr>
                        <tr><th>Name</th><td>@{{ viewData.currency_name }}</td></tr>
                        <tr><th>Symbol</th><td>@{{ viewData.symbol || '—' }}</td></tr>
                        <tr><th>Decimal places</th><td>@{{ viewData.decimal_places }}</td></tr>
                        <tr><th>Primary</th><td>@{{ viewData.is_primary ? 'Yes' : 'No' }}</td></tr>
                        <tr><th>Rate mode</th><td>@{{ viewData.rate_mode_label }}</td></tr>
                        <tr v-if="!viewData.is_primary">
                            <th>Effective rate</th>
                            <td>
                                <span v-if="viewData.effective_rate">@{{ Number(viewData.effective_rate).toFixed(6) }}</span>
                                <span v-else class="text-danger">Not set</span>
                            </td>
                        </tr>
                        <tr v-if="viewData.rate_mode === 'manual'"><th>Manual rate</th><td>@{{ viewData.manual_rate }}</td></tr>
                        <tr v-if="viewData.rate_mode === 'auto'"><th>Auto rate</th><td>@{{ viewData.auto_rate }}</td></tr>
                        <tr><th>Last updated</th><td>@{{ viewData.rate_fetched_at || '—' }}</td></tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" @click="openEditModal(viewData); closeViewModal()">
                        <i class="fas fa-pencil-alt me-1"></i> Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.21/vue.global.prod.min.js"></script>
<script>
// ── Routes injected by PHP (no placeholder strings to encode) ────────────────
const ROUTES = @json($routes);

// base = "…/{slug}/settings/currencies"  (from the store route, no trailing slash)
// All parameterised endpoints are built as:  base + "/" + id  (+ optional suffix)
const base = ROUTES.currency_base;
const cUrl = (id, suffix = '') => `${base}/${id}${suffix}`;

const { createApp, ref, computed, onMounted, reactive } = Vue;

createApp({
    setup() {
        const currencies      = ref([]);
        const knownCurrencies = ref({});
        const loading         = ref(true);
        const saving          = ref(false);
        const deleting        = ref(false);
        const refreshing      = ref({});
        const refreshingAll   = ref(false);
        const selected        = ref([]);
        const allSelected     = ref(false);
        const editMode        = ref(false);
        const viewData        = ref(null);
        const deleteTarget    = ref(null);
        const toast           = reactive({ message: '', type: 'success' });
        const filters         = reactive({ currency_name: '', currency_code: '', is_primary: '', rate_mode: '' });
        const sortKey         = ref('id');
        const sortOrder       = ref('asc');

        const emptyForm = () => ({
            id: null, currency_code: '', currency_name: '',
            symbol: '', decimal_places: 2,
            is_primary: false, rate_mode: 'auto', manual_rate: null,
        });
        const form = reactive(emptyForm());

        let bsCurrencyModal, bsViewModal, bsDeleteModal, bsBulkDeleteModal;

        onMounted(async () => {
            bsCurrencyModal   = new bootstrap.Modal(document.getElementById('currencyModal'));
            bsViewModal       = new bootstrap.Modal(document.getElementById('viewModal'));
            bsDeleteModal     = new bootstrap.Modal(document.getElementById('deleteModal'));
            bsBulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            await fetchCurrencies();
            await fetchKnown();
        });

        const csrf        = () => document.querySelector('meta[name="csrf-token"]').content;
        const jsonHeaders = () => ({
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'Accept':       'application/json',
        });

        let toastTimer = null;
        function showToast(message, type = 'success') {
            toast.message = message;
            toast.type    = type;
            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { toast.message = ''; }, 5000);
        }

        async function fetchCurrencies() {
            loading.value = true;
            try {
                const res  = await fetch(ROUTES.list);
                const data = await res.json();
                currencies.value = data.data?.currencies ?? [];
            } catch { showToast('Failed to load currencies.', 'danger'); }
            finally  { loading.value = false; }
        }

        async function fetchKnown() {
            try {
                const res  = await fetch(ROUTES.known);
                const data = await res.json();
                knownCurrencies.value = data.data?.currencies ?? {};
            } catch {}
        }

        const filteredCurrencies = computed(() => {
            let list = currencies.value.filter(c => {
                if (filters.currency_name && !c.currency_name.toLowerCase().includes(filters.currency_name.toLowerCase())) return false;
                if (filters.currency_code && !c.currency_code.toLowerCase().includes(filters.currency_code.toLowerCase())) return false;
                if (filters.is_primary !== '' && String(c.is_primary ? 1 : 0) !== filters.is_primary) return false;
                if (filters.rate_mode && c.rate_mode !== filters.rate_mode) return false;
                return true;
            });
            list.sort((a, b) => {
                let va = a[sortKey.value], vb = b[sortKey.value];
                if (typeof va === 'string') { va = va.toLowerCase(); vb = vb.toLowerCase(); }
                return sortOrder.value === 'asc' ? (va > vb ? 1 : -1) : (va < vb ? 1 : -1);
            });
            return list;
        });

        function sort(key) {
            sortKey.value === key
                ? (sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc')
                : (sortKey.value = key, sortOrder.value = 'asc');
        }
        function clearFilters() {
            Object.assign(filters, { currency_name: '', currency_code: '', is_primary: '', rate_mode: '' });
        }
        function toggleAll() {
            selected.value = allSelected.value
                ? filteredCurrencies.value.filter(c => !c.is_primary).map(c => c.id)
                : [];
        }
        function autofillCurrency() {
            const meta = knownCurrencies.value[form.currency_code];
            if (meta) { form.currency_name = meta.name; form.symbol = meta.symbol; form.decimal_places = meta.decimals; }
        }

        function openAddModal()    { Object.assign(form, emptyForm()); editMode.value = false; bsCurrencyModal.show(); }
        function openEditModal(c)  {
            Object.assign(form, {
                id: c.id, currency_code: c.currency_code, currency_name: c.currency_name,
                symbol: c.symbol, decimal_places: c.decimal_places,
                is_primary: c.is_primary, rate_mode: c.rate_mode, manual_rate: c.manual_rate,
            });
            editMode.value = true;
            bsCurrencyModal.show();
        }
        function viewCurrency(c)       { viewData.value = c; bsViewModal.show(); }
        function closeViewModal()      { bsViewModal.hide(); }
        function openDeleteModal(c)    { deleteTarget.value = c; bsDeleteModal.show(); }
        function openBulkDeleteModal() { if (selected.value.length > 0) bsBulkDeleteModal.show(); }

       async function saveCurrency() {
    if (!form.currency_code || !form.currency_name) {
        showToast('Currency code and name are required.', 'danger'); return;
    }
    if (form.rate_mode === 'manual' && !form.is_primary && !form.manual_rate) {
        showToast('Please enter the manual exchange rate.', 'danger'); return;
    }
    saving.value = true;
    try {
        const url    = editMode.value ? cUrl(form.id) : ROUTES.store;
        const method = editMode.value ? 'PUT' : 'POST';
        const res    = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify({ ...form }) });
        const data   = await res.json();

        if (res.ok) {
            bsCurrencyModal.hide();          // ← close modal first
            await fetchCurrencies();         // ← then refresh table
            showToast(data.message || (editMode.value ? 'Currency updated successfully.' : 'Currency added successfully.'), 'success');
        } else {
            showToast(data.message || 'Failed to save currency.', 'danger');
        }
    } catch {
        showToast('Network error. Please try again.', 'danger');
    } finally {
        saving.value = false;
    }
}

        async function confirmDelete() {
            if (!deleteTarget.value) return;
            deleting.value = true;
            try {
                // DELETE  base/{id}
                const res  = await fetch(cUrl(deleteTarget.value.id), { method: 'DELETE', headers: jsonHeaders() });
                const data = await res.json();
                bsDeleteModal.hide();
                if (res.ok) { await fetchCurrencies(); showToast(data.message, 'success'); }
                else          showToast(data.message || 'Failed to delete.', 'danger');
            } catch { showToast('Network error.', 'danger'); }
            finally  { deleting.value = false; deleteTarget.value = null; }
        }

        async function confirmBulkDelete() {
            deleting.value = true;
            try {
                const res  = await fetch(ROUTES.bulk_destroy, {
                    method: 'DELETE', headers: jsonHeaders(),
                    body: JSON.stringify({ ids: selected.value }),
                });
                const data = await res.json();
                bsBulkDeleteModal.hide();
                if (res.ok) {
                    selected.value = []; allSelected.value = false;
                    await fetchCurrencies();
                    showToast(data.message, 'success');
                } else showToast(data.message || 'Failed to delete.', 'danger');
            } catch { showToast('Network error.', 'danger'); }
            finally  { deleting.value = false; }
        }

        async function refreshRate(c) {
            refreshing.value = { ...refreshing.value, [c.id]: true };
            try {
                // POST  base/{id}/refresh
                const res  = await fetch(cUrl(c.id, '/refresh'), { method: 'POST', headers: jsonHeaders() });
                const data = await res.json();
                if (res.ok) {
                    await fetchCurrencies();
                    const rate = Number(data.data?.currency?.effective_rate ?? 0).toFixed(6);
                    showToast(`Rate refreshed: 1 ${c.currency_code} = ${rate} (primary)`, 'success');
                } else showToast(data.message || 'Failed to refresh rate.', 'danger');
            } catch { showToast('Network error.', 'danger'); }
            finally  { refreshing.value = { ...refreshing.value, [c.id]: false }; }
        }

        async function refreshAllRates() {
            refreshingAll.value = true;
            try {
                const res  = await fetch(ROUTES.refresh_all, { method: 'POST', headers: jsonHeaders() });
                const data = await res.json();
                if (res.ok) { currencies.value = data.data?.currencies ?? currencies.value; showToast(data.message, 'success'); }
                else          showToast(data.message || 'Failed to refresh rates.', 'danger');
            } catch { showToast('Network error.', 'danger'); }
            finally  { refreshingAll.value = false; }
        }

        function exportData(format) {
            const rows = [
                ['ID','Currency','Code','Symbol','Decimal Places','Primary','Rate Mode','Effective Rate'],
                ...filteredCurrencies.value.map(c => [
                    c.id, c.currency_name, c.currency_code, c.symbol || '',
                    c.decimal_places, c.is_primary ? 'Yes' : 'No', c.rate_mode_label, c.effective_rate || '',
                ]),
            ];
            const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
            const a   = document.createElement('a');
            a.href    = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            a.download = `currencies.${format}`;
            a.click();
        }

        return {
            currencies, knownCurrencies, loading, saving, deleting,
            refreshing, refreshingAll, selected, allSelected,
            editMode, viewData, deleteTarget, toast, filters, form,
            filteredCurrencies, sort, clearFilters, toggleAll, autofillCurrency,
            openAddModal, openEditModal, viewCurrency, closeViewModal,
            openDeleteModal, openBulkDeleteModal,
            saveCurrency, confirmDelete, confirmBulkDelete,
            refreshRate, refreshAllRates, exportData,
        };
    }
}).mount('#currency-manager');
</script>
@endpush

</x-app-layout>
