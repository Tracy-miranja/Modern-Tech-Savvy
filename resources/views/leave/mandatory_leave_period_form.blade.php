@php
    $isEdit = isset($period) && !empty($period);
    $scopeType = old('scope_type', $isEdit ? $period->scope_type : 'organization');
    $scopeIds = old('scope_ids', $isEdit ? ($period->scope_ids ?? []) : []);
@endphp

<form id="mandatoryLeavePeriodForm">
    @if ($isEdit)
        <input type="hidden" name="mandatory_leave_period_slug" value="{{ $period->slug }}">
    @endif

    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Christmas Shutdown 2026"
            value="{{ old('name', $isEdit ? $period->name : '') }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Leave Type to Deduct From</label>
        <select name="leave_type_id" class="form-select" required>
            <option value="">Select a leave type</option>
            @foreach ($leaveTypes as $leaveType)
                <option value="{{ $leaveType->id }}" @selected(old('leave_type_id', $isEdit ? $period->leave_type_id : '') == $leaveType->id)>{{ $leaveType->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control"
                value="{{ old('start_date', $isEdit ? $period->start_date->toDateString() : '') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control"
                value="{{ old('end_date', $isEdit ? $period->end_date->toDateString() : '') }}" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label d-block">Applies To</label>
        <div class="btn-group w-100 scope-btn-group" role="group">
            <input type="radio" class="btn-check" name="scope_type" id="scope_organization" value="organization" autocomplete="off" @checked($scopeType === 'organization')>
            <label class="btn btn-outline-primary" for="scope_organization">Whole Organization</label>

            <input type="radio" class="btn-check" name="scope_type" id="scope_department" value="department" autocomplete="off" @checked($scopeType === 'department')>
            <label class="btn btn-outline-primary" for="scope_department">Specific Department(s)</label>

            <input type="radio" class="btn-check" name="scope_type" id="scope_location" value="location" autocomplete="off" @checked($scopeType === 'location')>
            <label class="btn btn-outline-primary" for="scope_location">Specific Location(s)</label>
        </div>
    </div>

    <style>
        @media (max-width: 576px) {
            .scope-btn-group {
                flex-direction: column;
            }
            .scope-btn-group .btn {
                border-radius: .375rem !important;
                margin-bottom: .25rem;
            }
        }
    </style>

    <div class="mb-3 {{ $scopeType === 'department' ? '' : 'd-none' }}" id="scope_department_picker">
        <label class="form-label">Departments</label>
        <select name="scope_ids_department[]" class="form-select" multiple size="6">
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected($scopeType === 'department' && in_array($department->id, $scopeIds))>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3 {{ $scopeType === 'location' ? '' : 'd-none' }}" id="scope_location_picker">
        <label class="form-label">Locations</label>
        <select name="scope_ids_location[]" class="form-select" multiple size="6">
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected($scopeType === 'location' && in_array($location->id, $scopeIds))>{{ $location->name }}{{ $location->country ? ' (' . $location->country . ')' : '' }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $isEdit ? $period->notes : '') }}</textarea>
    </div>

    <div class="alert alert-info small">
        Days are deducted automatically from every affected employee's balance for the chosen leave type as soon as you save - based on their own location's public holidays and the company's non-working days, same as a normal leave request. Editing or deleting this later re-calculates and restores balances accordingly.
    </div>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="saveMandatoryLeavePeriod(this)">
            {{ $isEdit ? 'Update' : 'Create' }} &amp; Apply
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
