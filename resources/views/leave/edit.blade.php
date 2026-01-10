@php
    // Pick a single policy as the "current" selection to seed the form
    $policy = $leaveType->leavePolicies->first();
    $departments = $departments ?? \App\Models\Department::where('business_id', $leaveType->business_id)->get();
    $jobCategories = $jobCategories ?? \App\Models\JobCategory::where('business_id', $leaveType->business_id)->get();
    $excluded = is_array($leaveType->excluded_days ?? null) ? $leaveType->excluded_days : [];
    $formatDate = function($d) { return $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : ''; };
@endphp

<div class="container-fluid p-0">
    <h4 class="mb-3">Edit Leave Type: {{ $leaveType->name }}</h4>

    {{-- IMPORTANT: no action attribute – this is submitted via AJAX by saveLeaveType() --}}
    <form id="leaveTypeForm">
        {{-- Hidden slug the JS looks for --}}
        <input type="hidden" name="leave_type_slug" value="{{ $leaveType->slug }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $leaveType->name) }}" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description">{{ old('description', $leaveType->description) }}</textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">Requires Approval</label>
                <select class="form-select" name="requires_approval" required>
                    <option value="1" @selected(old('requires_approval', $leaveType->requires_approval))>Yes</option>
                    <option value="0" @selected(!old('requires_approval', $leaveType->requires_approval))>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Is Paid</label>
                <select class="form-select" name="is_paid" required>
                    <option value="1" @selected(old('is_paid', $leaveType->is_paid))>Yes</option>
                    <option value="0" @selected(!old('is_paid', $leaveType->is_paid))>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Allowance Accruable</label>
                <select class="form-select" name="allowance_accruable" required>
                    <option value="1" @selected(old('allowance_accruable', $leaveType->allowance_accruable))>Yes</option>
                    <option value="0" @selected(!old('allowance_accruable', $leaveType->allowance_accruable))>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Allows Half Day</label>
                <select class="form-select" name="allows_half_day" required>
                    <option value="1" @selected(old('allows_half_day', $leaveType->allows_half_day))>Yes</option>
                    <option value="0" @selected(!old('allows_half_day', $leaveType->allows_half_day))>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Requires Attachment</label>
                <select class="form-select" name="requires_attachment" required>
                    <option value="1" @selected(old('requires_attachment', $leaveType->requires_attachment))>Yes</option>
                    <option value="0" @selected(!old('requires_attachment', $leaveType->requires_attachment))>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Max Continuous Days</label>
                <input type="number" class="form-control" name="max_continuous_days" value="{{ old('max_continuous_days', $leaveType->max_continuous_days) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Min Notice Days</label>
                <input type="number" class="form-control" name="min_notice_days" value="{{ old('min_notice_days', $leaveType->min_notice_days) }}" required>
            </div>

            {{-- Scope selectors --}}
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <select class="form-select" name="department" required>
                    <option value="all" @selected(old('department', $selectedDepartment ?? 'all') == 'all')>All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->slug }}" @selected(old('department', $selectedDepartment ?? 'all') == $department->slug)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Job Category</label>
                <select class="form-select" name="job_category" required>
                    <option value="all" @selected(old('job_category', $selectedJobCategory ?? 'all') == 'all')>All Job Categories</option>
                    @foreach($jobCategories as $jobCategory)
                        <option value="{{ $jobCategory->slug }}" @selected(old('job_category', $selectedJobCategory ?? 'all') == $jobCategory->slug)>{{ $jobCategory->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Gender Applicable</label>
                @php $genderOld = old('gender_applicable', $policy->gender_applicable ?? 'all'); @endphp
                <select class="form-select" name="gender_applicable" required>
                    <option value="all" @selected($genderOld === 'all')>All</option>
                    <option value="male" @selected($genderOld === 'male')>Male</option>
                    <option value="female" @selected($genderOld === 'female')>Female</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Prorated for New Employees</label>
                <select class="form-select" name="prorated_for_new_employees" required>
                    @php $prorated = old('prorated_for_new_employees', $policy->prorated_for_new_employees ?? 0); @endphp
                    <option value="1" @selected($prorated)>Yes</option>
                    <option value="0" @selected(!$prorated)>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Default Days</label>
                <input type="number" class="form-control" name="default_days" value="{{ old('default_days', $policy->default_days ?? '') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Accrual Frequency</label>
                @php $freq = old('accrual_frequency', $policy->accrual_frequency ?? 'monthly'); @endphp
                <select class="form-select" name="accrual_frequency" required>
                    <option value="monthly" @selected($freq==='monthly')>Monthly</option>
                    <option value="quarterly" @selected($freq==='quarterly')>Quarterly</option>
                    <option value="yearly" @selected($freq==='yearly')>Yearly</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Accrual Amount</label>
                <input type="number" step="0.01" class="form-control" name="accrual_amount" value="{{ old('accrual_amount', $policy->accrual_amount ?? '') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Max Carryover Days</label>
                <input type="number" class="form-control" name="max_carryover_days" value="{{ old('max_carryover_days', $policy->max_carryover_days ?? '') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Minimum Service Days Required</label>
                <input type="number" class="form-control" name="minimum_service_days_required" value="{{ old('minimum_service_days_required', $policy->minimum_service_days_required ?? '') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Effective Date</label>
                <input type="date" class="form-control" name="effective_date" value="{{ old('effective_date', $formatDate($policy->effective_date ?? null)) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" value="{{ old('end_date', $formatDate($policy->end_date ?? null)) }}">
            </div>

            {{-- Governance / flow (required by validator) --}}
            <div class="col-md-4">
                <label class="form-label">Allows Backdating</label>
                @php $allowsBack = old('allows_backdating', $leaveType->allows_backdating ?? 0); @endphp
                <select class="form-select" name="allows_backdating" required>
                    <option value="1" @selected($allowsBack)>Yes</option>
                    <option value="0" @selected(!$allowsBack)>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Approval Levels</label>
                <input type="number" min="0" class="form-control" name="approval_levels" value="{{ old('approval_levels', $leaveType->approval_levels ?? 0) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Stepwise Approval?</label>
                @php $isStep = old('is_stepwise', $leaveType->is_stepwise ?? 0); @endphp
                <select class="form-select" name="is_stepwise" required>
                    <option value="1" @selected($isStep)>Yes</option>
                    <option value="0" @selected(!$isStep)>No</option>
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label d-block">Excluded Days</label>
                @php $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']; @endphp
                <div class="d-flex flex-wrap gap-3">
                    @foreach($days as $d)
                        <label class="form-check-label">
                            <input class="form-check-input me-1" type="checkbox" name="excluded_days[]" value="{{ $d }}" @checked(in_array($d, old('excluded_days', $excluded)))>
                            {{ ucfirst($d) }}
                        </label>
                    @endforeach
                </div>
            </div>

              {{-- NEW: Excluded Holiday Dates (specific YYYY-MM-DD) --}}
<div class="col-md-12">
    <label class="form-label d-block">Excluded Holiday Dates</label>

    <div class="d-flex align-items-start gap-2 mb-2">
        <input type="date" id="excluded_date_input" class="form-control" style="max-width: 220px;">
        <button
            type="button"
            class="btn btn-outline-primary"
            onclick="(function(btn){
                const form  = btn.closest('form');
                const input = form.querySelector('#excluded_date_input');
                const pills = form.querySelector('#excluded_dates_pills');
                const wrap  = form.querySelector('#excluded_dates_inputs');
                let val = (input && input.value) ? input.value : '';
                if (!val) return;

                // Accept YYYY-MM-DD as-is; otherwise try to normalize
                let iso = val;
                if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
                    const d = new Date(val);
                    if (isNaN(+d)) { alert('Invalid date'); return; }
                    iso = d.toISOString().slice(0,10);
                }

                // Prevent duplicates
                if (wrap.querySelector('input[name=&quot;excluded_dates[]&quot;][value=&quot;'+iso+'&quot;]')) return;

                // Hidden input (so it submits with the form)
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'excluded_dates[]';
                hidden.value = iso;
                wrap.appendChild(hidden);

                // Visual pill with remove
                const pill = document.createElement('span');
                pill.className = 'badge bg-secondary d-inline-flex align-items-center';
                pill.setAttribute('data-iso', iso);
                pill.textContent = iso + ' ';
                const x = document.createElement('button');
                x.type = 'button';
                x.className = 'btn-close btn-close-white btn-sm ms-1';
                x.setAttribute('aria-label', 'Remove');
                x.onclick = function(){ hidden.remove(); pill.remove(); };
                pill.appendChild(x);
                pills.appendChild(pill);

                input.value = '';
            })(this)"
        >Add date</button>
    </div>

    <div id="excluded_dates_pills" class="d-flex flex-wrap gap-2">
        {{-- Prepopulate existing dates as pills with remove buttons --}}
        @foreach((array)($leaveType->excluded_dates ?? []) as $d)
            <span class="badge bg-secondary d-inline-flex align-items-center" data-iso="{{ $d }}">
                {{ $d }}&nbsp;
                <button type="button" class="btn-close btn-close-white btn-sm ms-1" aria-label="Remove"
                    onclick="(function(btn){
                        const pill = btn.closest('span[data-iso]');
                        const iso = pill ? pill.getAttribute('data-iso') : null;
                        const form = btn.closest('form');
                        if (iso && form) {
                            const input = form.querySelector('input[name=&quot;excluded_dates[]&quot;][value=&quot;'+iso+'&quot;]');
                            if (input) input.remove();
                        }
                        if (pill) pill.remove();
                    })(this)">
                </button>
            </span>
        @endforeach
    </div>

    {{-- Hidden inputs live here so they submit with the form --}}
    <div id="excluded_dates_inputs">
        {{-- Presence input so the key exists even when empty (lets you clear all dates) --}}
        <input type="hidden" name="excluded_dates" value="">
        {{-- One hidden input per existing date --}}
        @foreach((array)($leaveType->excluded_dates ?? []) as $d)
            <input type="hidden" name="excluded_dates[]" value="{{ $d }}">
        @endforeach
    </div>

    <small class="text-muted d-block mt-1">
        Specific dates (YYYY-MM-DD) that won’t be counted for this leave type.
    </small>
</div>

        <div class="mt-4 d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="saveLeaveType(this)">Update Leave Type</button>
            <button type="button" class="btn btn-outline-secondary" onclick="$('#leaveTypeFormContainer').empty();">Cancel</button>
        </div>
    </form>
</div>

{{-- Script kept inside the partial so it works when this view is injected via AJAX --}}
<script>
(function () {
    const input  = document.getElementById('excluded_date_input');
    const pills  = document.getElementById('excluded_dates_pills');
    const inputs = document.getElementById('excluded_dates_inputs');
    const btn    = document.getElementById('add_excluded_date_btn');

    function addDate(val) {
        if (!val) return;
        const d = new Date(val);
        if (isNaN(+d)) { alert('Invalid date'); return; }
        const iso = d.toISOString().slice(0,10);

        // prevent duplicates
        if ([...inputs.querySelectorAll('input[name="excluded_dates[]"]')].some(i => i.value === iso)) return;

        // hidden input for form submit (INSIDE the form)
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'excluded_dates[]';
        hidden.value = iso;
        inputs.appendChild(hidden);

        // visual pill with remove
        const pill = document.createElement('span');
        pill.className = 'badge bg-secondary d-inline-flex align-items-center';
        pill.textContent = iso + ' ';
        const x = document.createElement('button');
        x.type = 'button';
        x.className = 'btn-close btn-close-white btn-sm ms-1';
        x.setAttribute('aria-label', 'Remove');
        x.onclick = () => { hidden.remove(); pill.remove(); };
        pill.appendChild(x);
        pills.appendChild(pill);

        input.value = '';
    }

    // Button & Enter key handlers
    btn?.addEventListener('click', () => addDate(input.value));
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); addDate(input.value); }
    });

    // Prepopulate from server (existing values on the LeaveType)
    const initialExcludedDates = @json(old('excluded_dates', $leaveType->excluded_dates ?? []));
    if (Array.isArray(initialExcludedDates)) {
        initialExcludedDates.forEach(function (iso) {
            // add directly; addDate will normalize and de-duplicate
            addDate(iso);
        });
    }
})();
</script>
