<x-app-layout title="Surveys">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-semibold text-dark">Surveys (<span
                                    id="surveyCount">{{ $surveys->count() }}</span>)</h4>
                            <a href="{{ route('business.surveys.create', $businessSlug) }}"
                                class="btn btn-primary btn-modern">
                                <i class="fa fa-plus me-2"></i> Create Survey
                            </a>
                        </div>
                        <div id="surveysContainer" data-business-slug="{{ $businessSlug }}">
                            @include('surveys._table')
                        </div>
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

        .btn-modern {
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/surveys.js') }}" type="module"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.getSurveys();
        });
    </script>
    @endpush
</x-app-layout>