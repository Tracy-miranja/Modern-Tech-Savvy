<!-- resources/views/employees/warning/_form.blade.php -->
<form id="warningForm" class="needs-validation" novalidate>
    @csrf
    @if(isset($warning))
    <input type="hidden" name="warning_id" value="{{ $warning->id }}">
    @endif
    <div class="row g-3">
        <div class="col-12">
            <label for="employee_id" class="form-label fw-medium text-dark">Employee</label>
            <select name="employee_id" id="employee_id" class="form-select" required>
                <option value="" disabled {{ !isset($warning) ? 'selected' : '' }}>Select Employee</option>
                @foreach ($employees as $employee)
                <option value="{{ $employee->id }}"
                    {{ isset($warning) && $warning->employee_id == $employee->id ? 'selected' : '' }}>
                    {{ $employee->user->name }}
                </option>
                @endforeach
            </select>
            @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="stage_type_id" class="form-label fw-medium text-dark">Stage</label>
            <select name="stage_type_id" id="stage_type_id" class="form-select"
                data-current-stage-id="{{ isset($warning) ? (int) $warning->stage_type_id : '' }}">
                <option value="">Loading stages…</option>
            </select>
        </div>
        <div class="col-12">
            <label for="severity" class="form-label fw-medium text-dark">Severity</label>
            <select name="severity" id="severity" class="form-select">
                <option value="low" {{ isset($warning) && $warning->severity === 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ !isset($warning) || $warning->severity === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ isset($warning) && $warning->severity === 'high' ? 'selected' : '' }}>High</option>
            </select>
        </div>
        <div class="col-12">
            <label for="issue_date" class="form-label fw-medium text-dark">Issue Date</label>
            <input type="date" name="issue_date" id="issue_date" class="form-control"
                value="{{ isset($warning) ? $warning->issue_date->toDateString() : now()->toDateString() }}" required>
            @error('issue_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="reason" class="form-label fw-medium text-dark">Reason</label>
            <input type="text" name="reason" id="reason" class="form-control" value="{{ $warning->reason ?? '' }}"
                required>
            @error('reason')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="description" class="form-label fw-medium text-dark">Description (Optional)</label>
            <textarea name="description" id="description" class="form-control"
                rows="3">{{ $warning->description ?? '' }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @if(isset($warning))
        <div class="col-12">
            <label for="status" class="form-label fw-medium text-dark">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="active" {{ $warning->status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="resolved" {{ $warning->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
            @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endif
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-primary btn-modern" onclick="saveWarning(this)">
            <i class="fa fa-save me-2"></i> {{ isset($warning) ? 'Update Warning' : 'Issue Warning' }}
        </button>
    </div>
</form>
{{--
    No script here on purpose - this partial is also rendered standalone
    (WarningController::edit(), see warningService.edit() in warnings.js)
    outside of any layout that consumes @stack('scripts'), so anything
    pushed here would silently never render for an AJAX-loaded copy of
    this form (the stage dropdown would sit on "Loading stages…"
    forever). See window.initWarningForm() in warnings.js instead, which
    is called both on initial page load and after every AJAX swap of
    #warningFormContainer.
--}}