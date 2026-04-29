<form id="holidayForm" method="post">
    @csrf
    @if(isset($holiday))
        <input type="hidden" name="holiday_slug" value="{{ $holiday->slug }}">
    @endif

    <div class="form-group mb-3">
        <label for="name">Holiday Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" required
            placeholder="e.g., New Year's Day, Labor Day"
            value="{{ isset($holiday) ? $holiday->name : '' }}">
    </div>

    <div class="form-group mb-3">
        <label for="date">Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="date" name="date" required
            value="{{ isset($holiday) ? $holiday->date->format('Y-m-d') : '' }}">
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring" value="1"
                    {{ (isset($holiday) && $holiday->is_recurring) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_recurring">
                    <strong>Recurring Annually</strong>
                </label>
                <div class="form-text">Check if this holiday repeats every year</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_working_day" id="is_working_day" value="1"
                    {{ (isset($holiday) && $holiday->is_working_day) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_working_day">
                    <strong>Counted as Working Day</strong>
                </label>
                <div class="form-text">If checked, employees can work but get special overtime rates</div>
            </div>
        </div>
    </div>

    <div class="form-group mb-3">
        <label for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"
            placeholder="Additional notes about this holiday">{{ isset($holiday) ? $holiday->description : '' }}</textarea>
    </div>

    <div class="alert alert-info">
        <strong>Note:</strong>
        <ul class="mb-0">
            <li>If "Counted as Working Day" is checked, employees working on this day will get special overtime rates</li>
            <li>If unchecked, all hours worked will be counted as holiday overtime</li>
        </ul>
    </div>

    <div>
        <button type="button" onclick="saveHoliday(this)" id="submitButton" class="btn btn-primary w-100">
            <i class="bi bi-check-circle"></i> {{ isset($holiday) ? 'Update Holiday' : 'Add Holiday' }}
        </button>
    </div>
</form>
