<form id="workScheduleForm" method="post">
    @csrf
    @if(isset($schedule))
        <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
    @endif

    <div class="form-group mb-3">
        <label for="employee_id">Employee <span class="text-danger">*</span></label>
        <select class="form-control" id="employee_id" name="employee_id" required>
            <option value="">Select Employee</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" 
                    {{ (isset($schedule) && $schedule->employee_id == $employee->id) ? 'selected' : '' }}>
                    {{ $employee->user->name ?? 'Employee #' . $employee->id }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group mb-3">
        <label for="shift_id">Shift</label>
        <select class="form-control" id="shift_id" name="shift_id">
            <option value="">No Shift (Custom Hours)</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}" 
                    {{ (isset($schedule) && $schedule->shift_id == $shift->id) ? 'selected' : '' }}>
                    {{ $shift->name }} ({{ $shift->start_time->format('H:i') }} - {{ $shift->end_time->format('H:i') }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Optional: Select a predefined shift or leave blank for custom schedule</small>
    </div>

    <div class="form-group mb-3">
        <label>Working Days <span class="text-danger">*</span></label>
        <div class="row">
            @php
                $days = [
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ];
                $selectedDays = isset($schedule) ? $schedule->working_days : [1, 2, 3, 4, 5]; 
@endphp
            @foreach($days as $value => $label)
                <div class="col-md-6 col-lg-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="working_days[]" 
                            value="{{ $value }}" id="day_{{ $value }}"
                            {{ in_array($value, $selectedDays) ? 'checked' : '' }}>
                        <label class="form-check-label" for="day_{{ $value }}">
                            {{ $label }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
        <small class="text-muted">Select the days this employee is expected to work</small>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="effective_from">Effective From <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="effective_from" name="effective_from" required
                    value="{{ isset($schedule) ? $schedule->effective_from->format('Y-m-d') : now()->format('Y-m-d') }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label for="effective_to">Effective To</label>
                <input type="date" class="form-control" id="effective_to" name="effective_to"
                    value="{{ isset($schedule) && $schedule->effective_to ? $schedule->effective_to->format('Y-m-d') : '' }}">
                <small class="text-muted">Leave blank for ongoing schedule</small>
            </div>
        </div>
    </div>

    <div class="form-group mb-3">
        <label for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ isset($schedule) ? $schedule->notes : '' }}</textarea>
    </div>

    <div>
        <button type="button" onclick="saveWorkSchedule(this)" id="submitButton" class="btn btn-primary w-100">
            <i class="bi bi-check-circle"></i> {{ isset($schedule) ? 'Update Schedule' : 'Create Schedule' }}
        </button>
    </div>
</form>