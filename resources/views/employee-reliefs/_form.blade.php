<form id="employeeReliefForm" class="needs-validation" novalidate>
    @csrf
    @if(isset($employeeRelief))
    <input type="hidden" name="employee_relief_id" value="{{ $employeeRelief->id }}">
    @endif
    <div class="row g-3">
        <div class="col-12">
            <label for="employee_id" class="form-label fw-medium text-dark">Employee <span
                    class="text-danger">*</span></label>
            <select name="employee_id" id="employee_id" class="form-select" required>
                <option value="" {{ !isset($employeeRelief) ? 'selected' : '' }}>Select Employee</option>
                @foreach ($employees as $employee)
                <option value="{{ $employee->id }}"
                    {{ isset($employeeRelief) && $employeeRelief->employee_id == $employee->id ? 'selected' : '' }}>
                    {{ $employee->user->name }}
                </option>
                @endforeach
            </select>
            @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="relief_id" class="form-label fw-medium text-dark">Relief <span
                    class="text-danger">*</span></label>
            <select name="relief_id" id="relief_id" class="form-select" required>
                <option value="" {{ !isset($employeeRelief) ? 'selected' : '' }}>Select Relief</option>
                @foreach ($reliefs as $relief)
                <option value="{{ $relief->id }}"
                    {{ isset($employeeRelief) && $employeeRelief->relief_id == $relief->id ? 'selected' : '' }}>
                    {{ $relief->name }}
                </option>
                @endforeach
            </select>
            @error('relief_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="amount" class="form-label fw-medium text-dark">Amount</label>
            <input type="number" name="amount" id="amount" class="form-control"
                value="{{ $employeeRelief->amount ?? '' }}" step="0.01" placeholder="Override default relief amount">
            @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="is_active" class="form-label fw-medium text-dark">Active</label>
           <div class="form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
        {{ isset($employeeRelief) && $employeeRelief->is_active ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Is Active</label>
</div>
        </div>
        <div class="col-12">
            <label for="start_date" class="form-label fw-medium text-dark">Start Date (Optional)</label>
            <input type="date" name="start_date" id="start_date" class="form-control"
                value="{{ $employeeRelief->start_date ?? '' }}">
            @error('start_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="end_date" class="form-label fw-medium text-dark">End Date (Optional)</label>
            <input type="date" name="end_date" id="end_date" class="form-control"
                value="{{ $employeeRelief->end_date ?? '' }}">
            @error('end_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-primary btn-modern" onclick="saveEmployeeRelief(this)">
            <i class="fa fa-save me-2"></i> {{ isset($employeeRelief) ? 'Update Assignment' : 'Assign Relief' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (form.checkValidity()) {
                saveEmployeeRelief(form.querySelector('button[type="button"]'));
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
@endpush
