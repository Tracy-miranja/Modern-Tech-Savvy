@php
    $policy = $leaveType->leavePolicies->first();
    $departments = $departments ?? \App\Models\Department::where('business_id', $leaveType->business_id)->get();
    $jobCategories = $jobCategories ?? \App\Models\JobCategory::where('business_id', $leaveType->business_id)->get();
    $formatDate = function($d) { return $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : ''; };
    $standardLeaveTypeNames = getLeaveTypeNames();
    $currentNameIsStandard = in_array($leaveType->name, $standardLeaveTypeNames, true);
@endphp

<div class="container-fluid p-0">
    <h4 class="mb-3">Edit Leave Type: {{ $leaveType->name }}</h4>

    <form id="leaveTypeForm">
        <input type="hidden" name="leave_type_slug" value="{{ $leaveType->slug }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <select id="name_select" name="name" class="form-select" required>
                    <option value="">Select a leave type</option>
                    @foreach ($standardLeaveTypeNames as $leaveTypeName)
                        <option value="{{ $leaveTypeName }}" @selected(old('name', $leaveType->name) === $leaveTypeName)>{{ $leaveTypeName }}</option>
                    @endforeach
                    <option value="__other__" @selected(!$currentNameIsStandard)>Other (specify)...</option>
                </select>
                <input type="text" id="name_custom" placeholder="Enter a custom leave type name"
                    class="form-control mt-2 d-none" value="{{ old('name', $currentNameIsStandard ? '' : $leaveType->name) }}">
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
                <label class="form-label">Carryover Type</label>
                @php $carryoverType = old('carryover_type', $policy->carryover_type ?? 'full'); @endphp
                <select class="form-select" id="carryover_type" name="carryover_type">
                    <option value="full" @selected($carryoverType === 'full')>Full</option>
                    <option value="fixed" @selected($carryoverType === 'fixed')>Fixed</option>
                    <option value="percent" @selected($carryoverType === 'percent')>Percent</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Carryover Value</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" class="form-control" id="carryover_value" name="carryover_value" value="{{ old('carryover_value', $policy->carryover_value ?? '') }}">
                    <span class="input-group-text" id="carryover_value_suffix">{{ $carryoverType === 'percent' ? '%' : 'days' }}</span>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Carryover Valid For (months)</label>
                <input type="number" min="0" class="form-control" name="carryover_expiry_months" value="{{ old('carryover_expiry_months', $policy->carryover_expiry_months ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Min Days Between Requests</label>
                <input type="number" min="0" class="form-control" name="min_interval_days" value="{{ old('min_interval_days', $policy->min_interval_days ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Encashable</label>
                @php $isEncashable = old('is_encashable', $policy->is_encashable ?? 0); @endphp
                <select class="form-select" name="is_encashable">
                    <option value="0" @selected(!$isEncashable)>No</option>
                    <option value="1" @selected($isEncashable)>Yes</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Max Encashable Days</label>
                <input type="number" min="0" class="form-control" name="max_encashable_days" value="{{ old('max_encashable_days', $policy->max_encashable_days ?? '') }}">
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
                <input type="number" min="0" class="form-control" id="approval_levels" name="approval_levels" value="{{ old('approval_levels', $leaveType->approval_levels ?? 0) }}" required>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label d-block">Who Approves Each Level</label>
                <div id="approval_chain_rows" class="d-flex flex-wrap gap-2" data-approval-chain="{{ json_encode($leaveType->approval_chain ?? []) }}"></div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Stepwise Approval?</label>
                @php $isStep = old('is_stepwise', $leaveType->is_stepwise ?? 0); @endphp
                <select class="form-select" name="is_stepwise" required>
                    <option value="1" @selected($isStep)>Yes</option>
                    <option value="0" @selected(!$isStep)>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Exclude Public Holidays</label>
                @php $excludeHolidays = old('exclude_public_holidays', $leaveType->exclude_public_holidays ?? 1); @endphp
                <select class="form-select" name="exclude_public_holidays">
                    <option value="1" @selected($excludeHolidays)>Yes</option>
                    <option value="0" @selected(!$excludeHolidays)>No</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Exclude Non-Working Days</label>
                @php $excludeNonWorkingDays = old('exclude_non_working_days', $leaveType->exclude_non_working_days ?? 1); @endphp
                <select class="form-select" name="exclude_non_working_days">
                    <option value="1" @selected($excludeNonWorkingDays)>Yes</option>
                    <option value="0" @selected(!$excludeNonWorkingDays)>No</option>
                </select>
            </div>

        <div class="mt-4 d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="saveLeaveType(this)">Update Leave Type</button>
            <button type="button" class="btn btn-outline-secondary" onclick="$('#leaveTypeFormContainer').empty();">Cancel</button>
        </div>
    </form>
</div>
