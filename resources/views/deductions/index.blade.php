<x-app-layout title="{{ $page }}">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4">Create New Deduction</h4>
                        <div id="deductionFormContainer">
                            @include('deductions._form')
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="fw-semibold text-dark mt-4 mb-4">Current Deductions</h4>
                    <div id="deductionsContainer">
                        @include('deductions._table', ['deductions' => $deductions])
                    </div>
                </div>
                <div id="deductionModalContainer"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/deductions.js') }}" type="module"></script>
    @endpush
</x-app-layout>
