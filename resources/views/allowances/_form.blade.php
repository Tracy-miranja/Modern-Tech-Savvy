<form id="allowanceForm" class="needs-validation" novalidate>
    @csrf
    @if(isset($allowance))
    <input type="hidden" name="allowance_id" value="{{ $allowance->id }}">
    @endif
    <div class="row g-3">
        <div class="col-12">
            <label for="name" class="form-label fw-medium text-dark">Allowance Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $allowance->name ?? '' }}" required>
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="type" class="form-label fw-medium text-dark">Type</label>
            <select name="type" id="type" class="form-select" required>
                <option value="" disabled {{ !isset($allowance) ? 'selected' : '' }}>Select Type</option>
                <option value="fixed" {{ isset($allowance) && $allowance->type == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                <option value="rate" {{ isset($allowance) && $allowance->type == 'rate' ? 'selected' : '' }}>Rate (%)</option>
            </select>
            @error('type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="calculation_basis" class="form-label fw-medium text-dark">Calculation Basis</label>
            <select name="calculation_basis" id="calculation_basis" class="form-select" required>
                <option value="" disabled {{ !isset($allowance) ? 'selected' : '' }}>Select Basis</option>
                <option value="basic_pay" {{ isset($allowance) && $allowance->calculation_basis == 'basic_pay' ? 'selected' : '' }}>Basic Pay</option>
                <option value="gross_pay" {{ isset($allowance) && $allowance->calculation_basis == 'gross_pay' ? 'selected' : '' }}>Gross Pay</option>
                <option value="custom" {{ isset($allowance) && $allowance->calculation_basis == 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
            @error('calculation_basis')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12" id="amount_field" style="display: {{ isset($allowance) && $allowance->type == 'fixed' ? 'block' : 'none' }};">
            <label for="amount" class="form-label fw-medium text-dark">Amount</label>
            <input type="number" name="amount" id="amount" class="form-control" value="{{ $allowance->amount ?? '' }}" step="0.01" min="0" {{ isset($allowance) && $allowance->type == 'fixed' ? 'required' : '' }}>
            @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12" id="rate_field" style="display: {{ isset($allowance) && $allowance->type == 'rate' ? 'block' : 'none' }};">
            <label for="rate" class="form-label fw-medium text-dark">Rate (%)</label>
            <input type="number" name="rate" id="rate" class="form-control" value="{{ $allowance->rate ?? '' }}" step="0.01" min="0" max="100" {{ isset($allowance) && $allowance->type == 'rate' ? 'required' : '' }}>
            @error('rate')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_taxable" id="is_taxable" class="form-check-input" value="1" {{ isset($allowance) && $allowance->is_taxable ? 'checked' : '' }}>
                <label for="is_taxable" class="form-check-label fw-medium text-dark">Taxable</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="is_nssf_applicable" id="is_nssf_applicable" class="form-check-input" value="1" {{ !isset($allowance) || $allowance->is_nssf_applicable ? 'checked' : '' }}>
                <label for="is_nssf_applicable" class="form-check-label fw-medium text-dark">Counts toward NSSF base</label>
            </div>
            @if (isset($business) && in_array(strtoupper(trim($business->country ?? '')), ['TANZANIA', 'TZ']))
            <div class="form-check">
                <input type="checkbox" name="is_sdl_applicable" id="is_sdl_applicable" class="form-check-input" value="1" {{ !isset($allowance) || $allowance->is_sdl_applicable ? 'checked' : '' }}>
                <label for="is_sdl_applicable" class="form-check-label fw-medium text-dark">Counts toward SDL base</label>
            </div>
            @endif
        </div>
        <div class="col-12">
            <label for="applies_to" class="form-label fw-medium text-dark">Applies To</label>
            <select name="applies_to" id="applies_to" class="form-select" required>
                <option value="" disabled {{ !isset($allowance) ? 'selected' : '' }}>Select Applicability</option>
                <option value="all" {{ isset($allowance) && $allowance->applies_to == 'all' ? 'selected' : '' }}>All Employees</option>
                <option value="specific" {{ isset($allowance) && $allowance->applies_to == 'specific' ? 'selected' : '' }}>Specific Employees</option>
            </select>
            @error('applies_to')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-primary btn-modern" onclick="saveAllowance(this)">
            <i class="fa fa-save me-2"></i> {{ isset($allowance) ? 'Update Allowance' : 'Create Allowance' }}
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
                saveAllowance(form.querySelector('button[type="button"]'));
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Dynamically toggle amount and rate fields based on type
    $('#type').on('change', function() {
        const type = $(this).val();
        const $amountField = $('#amount_field');
        const $rateField = $('#rate_field');
        const $amountInput = $('#amount');
        const $rateInput = $('#rate');

        $amountField.toggle(type === 'fixed');
        $rateField.toggle(type === 'rate');

        // Toggle required attribute based on type
        $amountInput.prop('required', type === 'fixed');
        $rateInput.prop('required', type === 'rate');

        // Clear the non-active field to avoid validation confusion
        if (type === 'fixed') {
            $rateInput.val('');
        } else if (type === 'rate') {
            $amountInput.val('');
        }
    });
})();
</script>
@endpush
