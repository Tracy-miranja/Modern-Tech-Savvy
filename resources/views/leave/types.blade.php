<x-app-layout>
    <div class="row g-20">

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form action="" method="post" id="leaveTypeForm">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" placeholder="Leave Name"
                                class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Leave description"></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="requires_approval">Requires approval</label>
                                <select name="requires_approval" id="requires_approval" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="is_paid">Is paid</label>
                                <select name="is_paid" id="is_paid" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="allowance_accruable">Allowance accruable</label>
                                <select name="allowance_accruable" id="allowance_accruable" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="allows_half_day">Allows half day</label>
                                <select name="allows_half_day" id="allows_half_day" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="requires_attachment">Requires attachment</label>
                                <select name="requires_attachment" id="requires_attachment" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="max_continuous_days">Max continuous days</label>
                                <input type="number" name="max_continuous_days" id="max_continuous_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');">
                            </div>

                            <div class="col-md-12">
                                <label for="min_notice_days">Min notice days</label>
                                <input type="number" name="min_notice_days" id="min_notice_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>
                        </div>

                        <h6 class="mt-3">Leave Policies</h6>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="department">Department</label>
                                <select name="department" id="department" class="form-select">
                                    <option value="all">All</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->slug }}"> {{ $department->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="job_category">Job categories</label>
                                <select name="job_category" id="job_category" class="form-select">
                                    <option value="all">All</option>
                                    @foreach ($job_categories as $job_category)
                                        <option value="{{ $job_category->slug }}"> {{ $job_category->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="gender_applicable">Gender applicable</label>
                                <select name="gender_applicable" id="gender_applicable" class="form-select">
                                    <option value="all">All</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="prorated_for_new_employees">Prorated for new employees</label>
                                <select name="prorated_for_new_employees" id="prorated_for_new_employees"
                                    class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="default_days">Default days</label>
                                <input type="number" name="default_days" id="default_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-4">
                                <label for="accrual_frequency">Accrual frequency</label>
                                <select name="accrual_frequency" id="accrual_frequency" class="form-select">
                                    <option value="monthly">{{ ucfirst('monthly') }}</option>
                                    <option value="quarterly">{{ ucfirst('quarterly') }}</option>
                                    <option value="yearly">{{ ucfirst('yearly') }}</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="accrual_amount">Accrual amount</label>
                                <input type="number" name="accrual_amount" id="accrual_amount"
                                    class="form-control" step="0.01" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-6">
                                <label for="max_carryover_days">Max carryover days</label>
                                <input type="number" name="max_carryover_days" id="max_carryover_days"
                                    class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-6">
                                <label for="minimum_service_days_required">minimum service days required</label>
                                <input type="number" name="minimum_service_days_required"
                                    id="minimum_service_days_required" class="form-control" min="0" oninput="validity.valid||(value='');" required>
                            </div>

                            <div class="col-md-6">
                                <label for="effective_date">Effective date</label>
                                <input type="date" class="form-control datepicker" id="effective_date"
                                    name="effective_date" required>
                            </div>

                            <div class="col-md-6">
                                <label for="end_date">End date</label>
                                <input type="date" class="form-control datepicker" id="end_date" name="end_date">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="excluded_days">Excluded (Non-working) Days</label>
                                <div class="d-flex flex-wrap">
                                    @php
                                        $daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                                    @endphp
                                    @foreach ($daysOfWeek as $day)
                                        <div class="form-check me-3">
                                            <input class="form-check-input" type="checkbox"
                                                name="excluded_days[]"
                                                id="day_{{ $day }}"
                                                value="{{ $day }}">
                                            <label class="form-check-label" for="day_{{ $day }}">
                                                {{ ucfirst($day) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="excluded_dates">Excluded Holiday Dates</label>
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <input type="date" id="excluded_date_input" class="form-control" style="max-width: 220px;">
                                    <button type="button" class="btn btn-outline-primary" id="add_excluded_date_btn">Add date</button>
                                </div>
                                <div id="excluded_dates_pills" class="d-flex flex-wrap gap-2"></div>
                                <!-- Hidden inputs get appended here so they submit with the form -->
                                <div id="excluded_dates_inputs"></div>
                                <small class="text-muted d-block mt-1">
                                    Specific dates (YYYY-MM-DD) that won’t be counted for this leave type.
                                </small>
                            </div>

                            <div class="col-md-4">
                                <label for="approval_levels">Approval Levels</label>
                                <select name="approval_levels" id="approval_levels" class="form-select">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="allows_backdating">Allows Backdating</label>
                                <select name="allows_backdating" id="allows_backdating" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0" selected>No</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="is_stepwise">Is Stepwise</label>
                                <select name="is_stepwise" id="is_stepwise" class="form-select">
                                    <option value="1">Yes</option>
                                    <option value="0" selected>No</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="stepwise_rules">Stepwise Rules</label>
                                <textarea name="stepwise_rules" id="stepwise_rules" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary w-100" onclick="saveLeaveType(this)">
                                    Save Leave Type
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div id="leaveTypeContainer">
                <div class="card">
                    <div class="card-body"> {{ loader() }} </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        @include('modals.leave-type')
        <script src="{{ asset('js/main/leave-type.js') }}" type="module"></script>

        <script>
            // helpers live in this closure
            (function () {
                const input  = document.getElementById('excluded_date_input');
                const pills  = document.getElementById('excluded_dates_pills');
                const inputs = document.getElementById('excluded_dates_inputs');
                const btn    = document.getElementById('add_excluded_date_btn');

                function addDate(val) {
                    if (!val) return;
                    const d = new Date(val);
                    if (isNaN(+d)) { alert('Invalid date'); return; }
                    const iso = d.toISOString().slice(0, 10);

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

                // Add button & Enter key
                btn?.addEventListener('click', () => addDate(input.value));
                input?.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); addDate(input.value); }
                });

                document.addEventListener('DOMContentLoaded', function () {
                    // Load Leave Types list (existing behavior)
                    if (typeof getLeaveType === 'function') {
                        getLeaveType();
                    }

                    // Autocomplete for name (existing behavior)
                    const nameInput = document.getElementById('name');
                    const availableTypes = @json(getLeaveTypeNames());
                    if (typeof $ !== 'undefined' && $.fn.autocomplete) {
                        $('#name').autocomplete({
                            source: availableTypes,
                            minLength: 1,
                        });
                    } else {
                        console.error('jQuery or jQuery UI is not loaded. Autocomplete will not work.');
                    }

                    // Pre-populate excluded dates from server into the form UI
                    const initialExcludedDates = @json($leaveType->excluded_dates ?? []);
                    if (Array.isArray(initialExcludedDates)) {
                        initialExcludedDates.forEach(function (iso) {
                            // Feed directly to addDate; it will normalize & avoid duplicates
                            addDate(iso);
                        });
                    }
                });
            })();
        </script>
    @endpush
</x-app-layout>
