@php
    $isEdit = isset($leavePeriod) && !empty($leavePeriod);
@endphp

<form action="" method="POST" id="leavePeriodsForm">
    @csrf

    @if ($isEdit)
        <input type="hidden" name="leave_period_slug" value="{{ $leavePeriod->slug }}">
    @endif

    <div class="form-group mb-3">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" placeholder="Leave Period Name" class="form-control"
               value="{{ $isEdit ? $leavePeriod->name : '' }}" required>
    </div>

    <div class="form-group mb-3">
        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" id="start_date" class="form-control datepicker"
               value="{{ $isEdit ? $leavePeriod->start_date->toDateString() : '' }}" required>
    </div>

    <div class="form-group mb-3">
        <label for="end_date">End Date</label>
        <input type="date" name="end_date" id="end_date" class="form-control datepicker"
               value="{{ $isEdit ? $leavePeriod->end_date->toDateString() : '' }}" required>
    </div>

    <div class="form-group mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="accept_applications" name="accept_applications" value="1"
                   {{ (!$isEdit || $leavePeriod->accept_applications) ? 'checked' : '' }}>
            <label class="form-check-label" for="accept_applications">Accept Applications</label>
        </div>
    </div>

    <div class="form-group mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="can_accrue" name="can_accrue" value="1"
                   {{ (!$isEdit || $leavePeriod->can_accrue) ? 'checked' : '' }}>
            <label class="form-check-label" for="can_accrue">Can Accrue</label>
        </div>
    </div>

    <div class="form-group mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="restrict_applications_within_dates" name="restrict_applications_within_dates" value="1"
                   {{ ($isEdit && $leavePeriod->restrict_applications_within_dates) ? 'checked' : '' }}>
            <label class="form-check-label" for="restrict_applications_within_dates">Restrict Applications Within Dates</label>
        </div>
    </div>

    <div class="form-group mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="autocreate" name="autocreate" value="1"
                   {{ ($isEdit && $leavePeriod->autocreate) ? 'checked' : '' }}>
            <label class="form-check-label" for="autocreate">Autocreate Next Period</label>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 d-flex gap-2">
            <button type="button" onclick="saveLeavePeriods(this)" class="btn btn-primary w-100">
                <i class="bi bi-check-circle"></i> {{ $isEdit ? 'Update' : 'Save' }} Leave Period
            </button>
            @if ($isEdit)
                <button type="button" onclick="cancelEditLeavePeriod()" class="btn btn-outline-secondary">Cancel</button>
            @endif
        </div>
    </div>
</form>
