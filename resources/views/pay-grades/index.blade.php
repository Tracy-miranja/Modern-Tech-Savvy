<x-app-layout title="{{ $page }}">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4">Create New Pay Grade</h4>
                        <div id="payGradeFormContainer">
                            @include('pay-grades._form')
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="fw-semibold text-dark mt-4 mb-4">Current Pay Grades</h4>
                    <div id="payGradesContainer">
                        @include('pay-grades._table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .bg-primary-soft {
            background-color: #e7f1ff;
        }

        .btn-modern {
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            box-shadow: none;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/pay-grades.js') }}" type="module"></script>
    @endpush
</x-app-layout>