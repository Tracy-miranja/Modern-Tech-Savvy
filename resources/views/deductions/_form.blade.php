<form id="deductionForm" class="needs-validation" novalidate>
    @csrf
    @if(isset($deduction))
    <input type="hidden" name="deduction_id" value="{{ $deduction->id }}">
    @endif
    <div class="row g-3">
        <div class="col-12">
            <label for="name" class="form-label fw-medium text-dark">Deduction Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $deduction->name ?? '' }}" required>
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="description" class="form-label fw-medium text-dark">Description</label>
            <textarea name="description" id="description" class="form-control">{{ $deduction->description ?? '' }}</textarea>
        </div>
        <div class="col-12">
            <label for="calculation_basis" class="form-label fw-medium text-dark">Calculation Basis <span class="text-danger">*</span></label>
            <select name="calculation_basis" id="calculation_basis" class="form-select" required>
                <option value="" disabled {{ !isset($deduction) ? 'selected' : '' }}>Select Basis</option>
                <option value="basic_pay" {{ isset($deduction) && $deduction->calculation_basis == 'basic_pay' ? 'selected' : '' }}>Basic Pay</option>
                <option value="gross_pay" {{ isset($deduction) && $deduction->calculation_basis == 'gross_pay' ? 'selected' : '' }}>Gross Pay</option>
                <option value="cash_pay" {{ isset($deduction) && $deduction->calculation_basis == 'cash_pay' ? 'selected' : '' }}>Cash Pay</option>
                <option value="taxable_pay" {{ isset($deduction) && $deduction->calculation_basis == 'taxable_pay' ? 'selected' : '' }}>Taxable Pay</option>
                <option value="custom" {{ isset($deduction) && $deduction->calculation_basis == 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
            @error('calculation_basis')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="computation_method" class="form-label fw-medium text-dark">Computation Method <span class="text-danger">*</span></label>
            <select name="computation_method" id="computation_method" class="form-select" required>
                <option value="" disabled {{ !isset($deduction) ? 'selected' : '' }}>Select Method</option>
                <option value="fixed" {{ isset($deduction) && $deduction->computation_method == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                <option value="rate" {{ isset($deduction) && $deduction->computation_method == 'rate' ? 'selected' : '' }}>Rate (%)</option>
                <option value="formula" {{ isset($deduction) && $deduction->computation_method == 'formula' ? 'selected' : '' }}>Formula</option>
            </select>
            @error('computation_method')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12" id="amount_field" style="display: {{ isset($deduction) && $deduction->computation_method == 'fixed' ? 'block' : 'none' }};">
            <label for="amount" class="form-label fw-medium text-dark">Amount <span class="text-danger" id="amount_required" style="display: {{ isset($deduction) && $deduction->computation_method == 'fixed' ? 'inline' : 'none' }};">*</span></label>
            <input type="number" name="amount" id="amount" class="form-control" value="{{ $deduction->amount ?? '' }}" step="0.01" min="0" {{ isset($deduction) && $deduction->computation_method == 'fixed' ? 'required' : '' }}>
            @error('amount')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12" id="rate_field" style="display: {{ isset($deduction) && $deduction->computation_method == 'rate' ? 'block' : 'none' }};">
            <label for="rate" class="form-label fw-medium text-dark">Employee Rate (%) <span class="text-danger" id="rate_required" style="display: {{ isset($deduction) && $deduction->computation_method == 'rate' ? 'inline' : 'none' }};">*</span></label>
            <input type="number" name="rate" id="rate" class="form-control" value="{{ $deduction->rate ?? '' }}" step="0.01" min="0" max="100" {{ isset($deduction) && $deduction->computation_method == 'rate' ? 'required' : '' }}>
            <div class="form-text text-muted">This is the employee's own contribution rate (e.g. 10%).</div>
            @error('rate')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12" id="formula_field" style="display: {{ isset($deduction) && $deduction->computation_method == 'formula' ? 'block' : 'none' }};">
            <label for="formula" class="form-label fw-medium text-dark">Formula <span class="text-danger" id="formula_required" style="display: {{ isset($deduction) && $deduction->computation_method == 'formula' ? 'inline' : 'none' }};">*</span></label>
            <input type="text" name="formula" id="formula" class="form-control" value="{{ $deduction->formula ?? '' }}" placeholder="e.g. FringeBenefit(5%)" {{ isset($deduction) && $deduction->computation_method == 'formula' ? 'required' : '' }}>
            @error('formula')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="actual_amount" id="actual_amount" class="form-check-input" value="1" {{ isset($deduction) && $deduction->actual_amount ? 'checked' : '' }}>
                <label for="actual_amount" class="form-check-label fw-medium text-dark">Actual Amount (Varies by Employee)</label>
            </div>
            @error('actual_amount')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="fraction_to_consider" class="form-label fw-medium text-dark">Fraction to Consider <span class="text-danger">*</span></label>
            <select name="fraction_to_consider" id="fraction_to_consider" class="form-select" required>
                <option value="" disabled {{ !isset($deduction) ? 'selected' : '' }}>Select Fraction</option>
                <option value="employee_only" {{ isset($deduction) && $deduction->fraction_to_consider == 'employee_only' ? 'selected' : '' }}>Employee Only</option>
                <option value="employee_and_employer" {{ isset($deduction) && $deduction->fraction_to_consider == 'employee_and_employer' ? 'selected' : '' }}>Employee & Employer</option>
                <option value="employer_only" {{ isset($deduction) && $deduction->fraction_to_consider == 'employer_only' ? 'selected' : '' }}>Employer Only</option>
            </select>
            @error('fraction_to_consider')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- ── EMPLOYER RATE FIELD ─────────────────────────────────────────────── --}}
        {{-- Shown only when fraction_to_consider is employee_and_employer or employer_only --}}
        {{-- and computation_method is rate.                                                --}}
        {{-- If left blank, system will use the same rate as the employee (legacy behaviour) --}}
        <div class="col-12" id="employer_rate_field" style="display: {{ (isset($deduction) && in_array($deduction->fraction_to_consider, ['employee_and_employer','employer_only']) && $deduction->computation_method === 'rate') ? 'block' : 'none' }};">
            <label for="employer_rate" class="form-label fw-medium text-dark">
                Employer Contribution Rate (%)
                <span class="badge bg-info text-dark ms-1" style="font-size:0.7rem;">Optional</span>
            </label>
            <input type="number"
                   name="employer_rate"
                   id="employer_rate"
                   class="form-control"
                   value="{{ $deduction->employer_rate ?? '' }}"
                   step="0.01" min="0" max="100"
                   placeholder="Leave blank to match employee rate">
            <div class="form-text text-muted">
                Enter a different rate if the employer contributes at a different percentage than the employee.
                Example: Employee = 10%, Employer = 6%. Leave blank to use the same rate as the employee.
            </div>
            @error('employer_rate')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="limit" class="form-label fw-medium text-dark">Employee Contribution Limit (Optional)</label>
            <input type="number" name="limit" id="limit" class="form-control" value="{{ $deduction->limit ?? '' }}" step="0.01" min="0">
            <div class="form-text text-muted">Maximum employee deduction per month (e.g. KES 30,000 for pension).</div>
            @error('limit')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- ── EMPLOYER LIMIT FIELD ────────────────────────────────────────────── --}}
        <div class="col-12" id="employer_limit_field" style="display: {{ (isset($deduction) && in_array($deduction->fraction_to_consider, ['employee_and_employer','employer_only'])) ? 'block' : 'none' }};">
            <label for="employer_limit" class="form-label fw-medium text-dark">
                Employer Contribution Limit (Optional)
            </label>
            <input type="number"
                   name="employer_limit"
                   id="employer_limit"
                   class="form-control"
                   value="{{ $deduction->employer_limit ?? '' }}"
                   step="0.01" min="0"
                   placeholder="Leave blank to use employee limit">
            <div class="form-text text-muted">
                Maximum employer contribution per month. Leave blank to use the same limit as the employee.
            </div>
            @error('employer_limit')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label for="round_off" class="form-label fw-medium text-dark">Round Off <span class="text-danger">*</span></label>
            <select name="round_off" id="round_off" class="form-select" required>
                <option value="" disabled {{ !isset($deduction) ? 'selected' : '' }}>Select Rounding</option>
                <option value="round_off_up" {{ isset($deduction) && $deduction->round_off == 'round_off_up' ? 'selected' : '' }}>Round Up</option>
                <option value="round_off_down" {{ isset($deduction) && $deduction->round_off == 'round_off_down' ? 'selected' : '' }}>Round Down</option>
            </select>
            @error('round_off')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label for="decimal_places" class="form-label fw-medium text-dark">Decimal Places <span class="text-danger">*</span></label>
            <select name="decimal_places" id="decimal_places" class="form-select" required>
                @for ($i = 0; $i <= 5; $i++)
                    <option value="{{ $i }}" {{ isset($deduction) && $deduction->decimal_places == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
            @error('decimal_places')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-primary btn-modern" onclick="saveDeduction(this)">
            <i class="fa fa-save me-2"></i> {{ isset($deduction) ? 'Update Deduction' : 'Create Deduction' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
(function() {
    'use strict';

    // ── Helper: toggle employer-specific fields based on fraction + method ──
    function updateEmployerFields() {
        var method   = $('#computation_method').val();
        var fraction = $('#fraction_to_consider').val();
        var hasEmployer = (fraction === 'employee_and_employer' || fraction === 'employer_only');

        // Employer rate: only visible when method=rate AND employer contributes
        $('#employer_rate_field').toggle(method === 'rate' && hasEmployer);

        // Employer limit: visible whenever employer contributes (any method)
        $('#employer_limit_field').toggle(hasEmployer);
    }

    // ── Computation method change ──────────────────────────────────────────
    $('#computation_method').on('change', function() {
        const method = $(this).val();
        const $amountField  = $('#amount_field');
        const $rateField    = $('#rate_field');
        const $formulaField = $('#formula_field');
        const $amountInput  = $('#amount');
        const $rateInput    = $('#rate');
        const $formulaInput = $('#formula');

        $amountField.toggle(method === 'fixed');
        $rateField.toggle(method === 'rate');
        $formulaField.toggle(method === 'formula');

        $amountInput.prop('required', method === 'fixed');
        $rateInput.prop('required', method === 'rate');
        $formulaInput.prop('required', method === 'formula');
        $('#amount_required').toggle(method === 'fixed');
        $('#rate_required').toggle(method === 'rate');
        $('#formula_required').toggle(method === 'formula');

        if (method === 'fixed') {
            $rateInput.val(''); $formulaInput.val('');
            $rateInput.removeClass('is-invalid'); $formulaInput.removeClass('is-invalid');
        } else if (method === 'rate') {
            $amountInput.val(''); $formulaInput.val('');
            $amountInput.removeClass('is-invalid'); $formulaInput.removeClass('is-invalid');
        } else if (method === 'formula') {
            $amountInput.val(''); $rateInput.val('');
            $amountInput.removeClass('is-invalid'); $rateInput.removeClass('is-invalid');
        }

        updateEmployerFields();
    });

    // ── Fraction to consider change ────────────────────────────────────────
    $('#fraction_to_consider').on('change', function() {
        updateEmployerFields();
    });

    // ── Bootstrap validation ───────────────────────────────────────────────
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (form.checkValidity()) {
                const method = $('#computation_method').val();
                const amount = $('#amount').val();
                const rate   = $('#rate').val();
                const formula = $('#formula').val();

                if (method === 'fixed' && (!amount || parseFloat(amount) <= 0)) {
                    $('#amount').addClass('is-invalid');
                    $('#amount').siblings('.invalid-feedback').text('Amount is required and must be greater than 0.');
                    form.classList.add('was-validated');
                    return;
                }
                if (method === 'rate' && (!rate || parseFloat(rate) <= 0)) {
                    $('#rate').addClass('is-invalid');
                    $('#rate').siblings('.invalid-feedback').text('Rate is required and must be greater than 0.');
                    form.classList.add('was-validated');
                    return;
                }
                if (method === 'formula' && !formula) {
                    $('#formula').addClass('is-invalid');
                    $('#formula').siblings('.invalid-feedback').text('Formula is required.');
                    form.classList.add('was-validated');
                    return;
                }

                saveDeduction(form.querySelector('button[type="button"]'));
            }
            form.classList.add('was-validated');
        }, false);
    });

    // ── Init on page load ──────────────────────────────────────────────────
    updateEmployerFields();
})();
</script>
@endpush
