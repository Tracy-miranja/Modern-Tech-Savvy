<x-app-layout title="{{ $page }}">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                    <h4 class="fw-semibold text-dark mb-0">Current KPIs <span id="kpiCount">0</span></h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('business.performance.kpis.create', $business->slug) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Create KPI
                        </a>
                        <a href="{{ route('business.performance.tasks.index', $business->slug) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-card-checklist me-1"></i> Assign Tasks
                        </a>
                    </div>
                </div>
                <ul class="nav nav-tabs" id="kpiTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="business-tab" data-bs-toggle="tab" href="#business-kpis"
                            role="tab" aria-controls="business-kpis" aria-selected="true">Business KPIs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="location-tab" data-bs-toggle="tab" href="#location-kpis" role="tab"
                            aria-controls="location-kpis" aria-selected="false">Location KPIs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="department-tab" data-bs-toggle="tab" href="#department-kpis" role="tab"
                            aria-controls="department-kpis" aria-selected="false">Department KPIs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="job-category-tab" data-bs-toggle="tab" href="#job-category-kpis"
                            role="tab" aria-controls="job-category-kpis" aria-selected="false">Job Category KPIs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="employee-tab" data-bs-toggle="tab" href="#employee-kpis" role="tab"
                            aria-controls="employee-kpis" aria-selected="false">Employee KPIs</a>
                    </li>
                </ul>
                <div class="tab-content" id="kpiTabContent">
                    <div class="tab-pane fade show active" id="business-kpis" role="tabpanel"
                        aria-labelledby="business-tab">
                        <div id="businessKpisContainer" class="mt-3">
                            <p class="text-center text-muted">Loading KPIs...</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="location-kpis" role="tabpanel" aria-labelledby="location-tab">
                        <div id="locationKpisContainer" class="mt-3">
                            <p class="text-center text-muted">Loading KPIs...</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="department-kpis" role="tabpanel" aria-labelledby="department-tab">
                        <div id="departmentKpisContainer" class="mt-3">
                            <p class="text-center text-muted">Loading KPIs...</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="job-category-kpis" role="tabpanel"
                        aria-labelledby="job-category-tab">
                        <div id="jobCategoryKpisContainer" class="mt-3">
                            <p class="text-center text-muted">Loading KPIs...</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="employee-kpis" role="tabpanel" aria-labelledby="employee-tab">
                        <div id="employeeKpisContainer" class="mt-3">
                            <p class="text-center text-muted">Loading KPIs...</p>
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
            border-radius: 10px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .btn-modern {
            padding: 6px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            height: 34px;
            font-size: 0.875rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .table {
            font-size: 0.9rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .progress {
            height: 10px;
        }

        .nav-tabs .nav-link {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .tab-content {
            background: #fff;
            border: 1px solid #dee2e6;
            border-top: none;
            padding: 1.5rem;
            border-radius: 0 0 8px 8px;
        }

        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.85rem;
            }

            .btn-modern {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/kpis.js') }}" type="module"></script>
    @endpush
</x-app-layout>