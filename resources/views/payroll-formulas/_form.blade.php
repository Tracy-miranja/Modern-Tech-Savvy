<form id="formulaForm" class="needs-validation" novalidate>
    @csrf
    @if(isset($formula))
    <input type="hidden" name="formula_id" value="{{ $formula->id }}">
    @endif
    <div class="row g-2">
        <div class="col-md-6">
            <label for="name" class="form-label fw-medium text-dark">Formula Name <span
                    class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $formula->name ?? '' }}" required>
        </div>
        <div class="col-md-6">
            <label for="country_code" class="form-label fw-medium text-dark">Country <span
                    class="text-danger">*</span></label>
            <select name="country" class="form-control">
                @foreach($countries as $value => $label)
                <option value="{{ $value }}" {{ old('country', $formula->country ?? '') == $value ? 'selected' : '' }}>
                    {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label for="description" class="form-label fw-medium text-dark">Description</label>
            <textarea name="description" id="description"
                class="form-control">{{ $formula->description ?? '' }}</textarea>
            <small class="text-muted">Explain what this formula does (e.g., "Income tax for Kenya").</small>
        </div>
        <div class="col-md-4">
            <label for="formula_type" class="form-label fw-medium text-dark">Formula Type <span
                    class="text-danger">*</span></label>
            <select name="formula_type" id="formula_type" class="form-select" required>
                <option value="rate" {{ isset($formula) && $formula->formula_type == 'rate' ? 'selected' : '' }}>
                    Percentage Rate</option>
                <option value="fixed" {{ isset($formula) && $formula->formula_type == 'fixed' ? 'selected' : '' }}>Fixed
                    Amount</option>
                <option value="progressive"
                    {{ isset($formula) && $formula->formula_type == 'progressive' ? 'selected' : '' }}>Progressive
                    (Brackets)</option>
                <option value="expression"
                    {{ isset($formula) && $formula->formula_type == 'expression' ? 'selected' : '' }}>Simple Rate with
                    Minimum</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="calculation_basis" class="form-label fw-medium text-dark">Based On <span
                    class="text-danger">*</span></label>
            <select name="calculation_basis" id="calculation_basis" class="form-select" required>
                <option value="basic_pay"
                    {{ isset($formula) && $formula->calculation_basis == 'basic_pay' ? 'selected' : '' }}>Basic Pay
                </option>
                <option value="gross_pay"
                    {{ isset($formula) && $formula->calculation_basis == 'gross_pay' ? 'selected' : '' }}>Gross Pay
                </option>
                <option value="taxable_pay"
                    {{ isset($formula) && $formula->calculation_basis == 'taxable_pay' ? 'selected' : '' }}>Taxable Pay
                </option>
                <option value="net_pay"
                    {{ isset($formula) && $formula->calculation_basis == 'net_pay' ? 'selected' : '' }}>Net Pay</option>
                <option value="custom"
                    {{ isset($formula) && $formula->calculation_basis == 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="applies_to" class="form-label fw-medium text-dark">Applies To <span
                    class="text-danger">*</span></label>
            <select name="applies_to" id="applies_to" class="form-select" required>
                <option value="all" {{ isset($formula) && $formula->applies_to == 'all' ? 'selected' : '' }}>All
                    Employees</option>
                <option value="specific" {{ isset($formula) && $formula->applies_to == 'specific' ? 'selected' : '' }}>
                    Specific Employees</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="limit" class="form-label fw-medium text-dark">Maximum Amount (Optional)</label>
            <input type="number" name="limit" id="limit" class="form-control" value="{{ $formula->limit ?? '' }}"
                step="0.01" min="0">
            <small class="text-muted">Cap the deduction at this amount.</small>
        </div>
        <div class="col-md-4">
            <label for="round_off" class="form-label fw-medium text-dark">Round Off</label>
            <select name="round_off" id="round_off" class="form-select">
                <option value="" {{ !isset($formula->round_off) ? 'selected' : '' }}>No Rounding</option>
                <option value="round_up" {{ isset($formula) && $formula->round_off == 'round_up' ? 'selected' : '' }}>
                    Round Up</option>
                <option value="round_down"
                    {{ isset($formula) && $formula->round_off == 'round_down' ? 'selected' : '' }}>Round Down</option>
                <option value="nearest" {{ isset($formula) && $formula->round_off == 'nearest' ? 'selected' : '' }}>
                    Nearest</option>
            </select>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_statutory" id="is_statutory" class="form-check-input" value="1"
                    {{ isset($formula) && $formula->is_statutory ? 'checked' : '' }}>
                <label for="is_statutory" class="form-check-label fw-medium text-dark">Is Statutory (Government
                    Required)</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_progressive" id="is_progressive" class="form-check-input" value="1"
                    {{ isset($formula) && $formula->is_progressive ? 'checked' : '' }}>
                <label for="is_progressive" class="form-check-label fw-medium text-dark">Use Income Brackets</label>
            </div>
        </div>
        <div class="col-12" id="minimum_amount_field"
            style="display: {{ isset($formula) && !$formula->is_progressive && $formula->formula_type != 'expression' ? 'block' : 'none' }};">
            <label for="minimum_amount" class="form-label fw-medium text-dark">Amount</label>
            <input type="number" name="minimum_amount" id="minimum_amount" class="form-control"
                value="{{ $formula->minimum_amount ?? '' }}" step="0.01"
                placeholder="Enter fixed amount or rate percentage">
            <small class="text-muted">For Rate, enter percentage (e.g., 6 for 6%); for Fixed, enter amount (e.g.,
                500).</small>
        </div>
        <div class="col-12" id="expression_field"
            style="display: {{ isset($formula) && $formula->formula_type == 'expression' ? 'block' : 'none' }};">
            <label class="form-label fw-medium text-dark">Simple Rate with Minimum</label>
            <div class="row g-2">
                <div class="col-md-6">
                    <label for="expression_rate" class="form-label fw-medium text-dark">Rate (%)</label>
                    <input type="number" name="expression_rate" id="expression_rate" class="form-control"
                        value="{{ isset($formula) && $formula->expression ? (floatval(preg_match('/\*\s*([\d.]+)/', $formula->expression, $matches) ? $matches[1] : 0) * 100) : '' }}"
                        step="0.01" required>
                    <small class="text-muted">Percentage to apply (e.g., 2.75 for 2.75%).</small>
                </div>
                <div class="col-md-6">
                    <label for="expression_minimum" class="form-label fw-medium text-dark">Minimum Amount</label>
                    <input type="number" name="expression_minimum" id="expression_minimum" class="form-control"
                        value="{{ isset($formula) && $formula->expression ? (floatval(preg_match('/,\s*([\d.]+)/', $formula->expression, $matches) ? $matches[1] : 0)) : '' }}"
                        step="0.01">
                    <small class="text-muted">Minimum deduction (e.g., 300).</small>
                </div>
            </div>
            <small class="text-muted">This creates a formula like: max(basis * rate%, minimum).</small>
        </div>
        <div class="col-12" id="brackets_container"
            style="display: {{ isset($formula) && $formula->is_progressive ? 'block' : 'none' }};">
            <h5 class="fw-medium text-dark mt-3">Income Brackets</h5>
            <div id="brackets">
                @if(isset($formula) && $formula->brackets)
                @foreach($formula->brackets as $index => $bracket)
                @include('payroll-formulas._bracket', ['index' => $index, 'bracket' => $bracket])
                @endforeach
                @else
                @include('payroll-formulas._bracket', ['index' => 0])
                @endif
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addBracket()">
    Add Bracket
</button>
        </div>
    </div>
    <div class="mt-4">
        <button type="button" class="btn btn-primary btn-modern" onclick="saveFormula(this)">
            <i class="fa fa-save me-2"></i> {{ isset($formula) ? 'Update Formula' : 'Create Formula' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
    const bracketTemplateUrl = "{{ route('business.payroll-formulas.bracket-template', ['business' => $business->slug]) }}";

    $('#is_progressive').on('change', function() {
        const isProgressive = $(this).is(':checked');
        $('#brackets_container').toggle(isProgressive);
        $('#minimum_amount_field').toggle(!isProgressive && $('#formula_type').val() !== 'expression');
        if (isProgressive && $('#brackets .bracket').length === 0) {
            addBracket();
        }
    });

    $('#formula_type').on('change', function() {
        const type = $(this).val();
        const isProgressive = $('#is_progressive').is(':checked');
        $('#expression_field').toggle(type === 'expression');
        $('#minimum_amount_field').toggle(type !== 'progressive' && type !== 'expression' && !isProgressive);
        $('#brackets_container').toggle(type === 'progressive' || isProgressive);
    });

   function addBracket() {
    const index = $('#brackets .bracket').length;
    $.get(bracketTemplateUrl, { index: index }, function(response) {
        const html = typeof response === 'object' ? response.html : response;
        $('#brackets').append(html);
    });
}

    function removeBracket(btn) {
        $(btn).closest('.bracket').remove();
    }
</script>
@endpush
