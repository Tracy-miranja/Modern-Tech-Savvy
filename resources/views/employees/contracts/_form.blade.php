<form id="contractActionForm" class="needs-validation" novalidate>
    @csrf
    @if(isset($contractAction))
    <input type="hidden" name="contract_action_id" value="{{ $contractAction->id }}">
    @endif
    <div class="row g-3">
        <div class="col-12">
            <label for="employee_id" class="form-label fw-medium text-dark">Employee</label>
            <select name="employee_id" id="employee_id" class="form-select" required>
                <option value="" disabled {{ !isset($contractAction) ? 'selected' : '' }}>Select Employee</option>
                @foreach ($employees as $employee)
                <option value="{{ $employee->id }}"
                    {{ isset($contractAction) && $contractAction->employee_id == $employee->id ? 'selected' : '' }}>
                    {{ $employee->user->name }}
                </option>
                @endforeach
            </select>
            @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @if(!isset($contractAction))
        <div class="col-12">
            <label for="action_type" class="form-label fw-medium text-dark">Action Type</label>
            <select name="action_type" id="action_type" class="form-select" required>
                <option value="" disabled selected>Select Action Type</option>
                <option value="termination">Termination</option>
                <option value="suspension">Suspension</option>
                <option value="reminder">Reminder</option>
            </select>
            @error('action_type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endif
        <div class="col-12">
            <label for="action_date" class="form-label fw-medium text-dark">Action Date</label>
            <input type="date" name="action_date" id="action_date" class="form-control"
                value="{{ isset($contractAction) ? $contractAction->action_date->toDateString() : now()->toDateString() }}"
                required>
            @error('action_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="reason" class="form-label fw-medium text-dark">Reason</label>
            <input type="text" name="reason" id="reason" class="form-control"
                value="{{ $contractAction->reason ?? '' }}" required>
            @error('reason')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="description" class="form-label fw-medium text-dark">Description (Optional)</label>
            <textarea name="description" id="description" class="form-control"
                rows="3">{{ $contractAction->description ?? '' }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @if(isset($contractAction))
        <div class="col-12">
            <label for="status" class="form-label fw-medium text-dark">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="active" {{ $contractAction->status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="reversed" {{ $contractAction->status === 'reversed' ? 'selected' : '' }}>Reversed
                </option>
            </select>
            @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @endif
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-primary btn-modern" onclick="saveContractAction(this)">
            <i class="fa fa-save me-2"></i> {{ isset($contractAction) ? 'Update Action' : 'Record Action' }}
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
                    saveContractAction(form.querySelector('button[type="button"]'));
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>
@endpush