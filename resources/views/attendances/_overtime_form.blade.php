<form id="overtimeForm" method="POST">
    @csrf

    @if(isset($overtime))
        <input type="hidden" name="overtime_id" value="{{ $overtime->id }}">
    @endif

    <div class="form-group">
        <label for="employee_id">Employee:</label>
        <select name="employee_id" id="employee_id" class="form-control" required>
            <option value="">Select Employee</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}"
                    {{ (string)old('employee_id', $overtime->employee_id ?? '') === (string)$employee->id ? 'selected' : '' }}>
                    {{ $employee->user->name ?? 'N/A' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="date">Date:</label>
        <input type="date"
               name="date"
               id="date"
               class="form-control"
               value="{{ old('date', isset($overtime) ? optional($overtime->date)->format('Y-m-d') : now()->format('Y-m-d')) }}"
               required>
    </div>

    <div class="form-group">
        <label for="overtime_type">Overtime Type:</label>
        <select name="overtime_type" id="overtime_type" class="form-control" required>
            <option value="">Select Overtime Type</option>
            @php $type = old('overtime_type', $overtime->overtime_type ?? '')@endphp
            <option value="regular" {{ $type === 'regular' ? 'selected' : '' }}>Regular</option>
            <option value="holiday" {{ $type === 'holiday' ? 'selected' : '' }}>Holiday</option>
            <option value="manual"  {{ $type === 'manual'  ? 'selected' : '' }}>Manual</option>
        </select>
    </div>

    <div class="form-group">
        <label for="overtime_hours">Overtime Hours:</label>
        <input type="number"
               step="0.01"
               name="overtime_hours"
               id="overtime_hours"
               class="form-control"
               value="{{ old('overtime_hours', $overtime->overtime_hours ?? '') }}"
               required>
    </div>

    <div class="form-group">
        <label for="description">Description:</label>
        <textarea name="description" id="description" class="form-control" required>{{ old('description', $overtime->description ?? '') }}</textarea>
    </div>

    <button type="button" onclick="saveOvertime(this)" class="btn btn-primary">
        <i class="bi bi-check-circle me-2"></i>
        {{ isset($overtime) ? 'Update' : 'Submit' }}
    </button>
</form>
