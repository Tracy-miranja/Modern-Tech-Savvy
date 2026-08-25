<x-app-layout title="{{ $page }}">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                @if($business->hasModule('payroll-management'))
                <div class="card shadow-sm mb-4 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-3">Remittance Deadline</h4>
                        <p class="text-muted small mb-3">Day of the month statutory payroll remittances (PAYE, NSSF, etc.) are due for this business.</p>
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <label for="payroll_remittance_deadline_day" class="form-label fw-medium text-dark">Deadline day</label>
                                <input type="number" name="payroll_remittance_deadline_day" id="payroll_remittance_deadline_day" class="form-control" min="1" max="31" placeholder="e.g. 7" value="{{ $business->payroll_remittance_deadline_day ?? '' }}" style="width: 120px;">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" onclick="saveRemittanceDeadline(this)">
                                    <i class="fa fa-save me-2"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4" id="formulaFormTitle">Create New Payroll Formula</h4>
                        <div id="formulaFormContainer">
                            @include('payroll-formulas._form', ['countries' => $countries])
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="fw-semibold text-dark mt-4 mb-4">Current Payroll Formulas</h4>
                    <div id="formulasContainer">
                        @include('payroll-formulas._table', ['formulas' => $formulas])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    window.businessSlug = '{{ $business->slug }}';
    </script>
    <script src="{{ asset('js/main/payroll-formulas.js') }}" type="module"></script>
    @endpush
</x-app-layout>