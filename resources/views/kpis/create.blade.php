<x-app-layout title="Create KPIs">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <div class="card shadow-sm mb-5 border-0 rounded-3">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold text-dark mb-4">Create New KPI</h4>
                        <div id="kpiFormContainer">
                            @include('kpis._form', [
                            'kpi' => null,
                            'business' => $business,
                            'employees' => $employees,
                            'locations' => $locations,
                            'modelTypes' => [
                            'App\Models\Attendance' => 'Attendance',
                            'App\Models\Application' => 'Job Applications',
                            'App\Models\EmployeePayroll' => 'Payroll',
                            'App\Models\Overtime' => 'Overtime',
                            'App\Models\LeaveRequest' => 'Leave Requests',
                            'App\Models\Task' => 'Tasks',
                            'App\Models\Advance' => 'Advances',
                            'App\Models\Loan' => 'Loans',
                            'App\Models\JobPost' => 'Job Posts',
                            'manual' => 'Manual Indicator',
                            ],
                            'calculationMethods' => ['percentage', 'count', 'average', 'sum', 'ratio'],
                            'comparisonOperators' => ['>=', '<=', '=' ] ]) </div>
                        </div>
                    </div>

                    <h4 class="fw-semibold text-dark mb-4">Existing KPIs</h4>
                    <div id="kpiCardsContainer">
                        @include('kpis._kpi_cards', ['kpis' => $kpis])
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
                border: 1px solid #e2e8f0;
                height: 38px;
                font-size: 14px;
            }

            .form-control:focus,
            .form-select:focus {
                border-color: #007bff;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            .form-group label {
                font-size: 14px;
                color: #4a5568;
                margin-bottom: 5px;
            }

            .form-row {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
            }

            .form-row .form-group {
                flex: 1;
                min-width: 200px;
            }

            textarea.form-control {
                height: 100px;
                resize: vertical;
            }

            .card-body {
                padding: 1.5rem;
            }

            .card-title {
                font-size: 1.25rem;
                color: #2d3748;
            }

            .card-text {
                font-size: 0.9rem;
                color: #718096;
            }

            .card-footer {
                padding: 1rem 1.5rem;
            }
        </style>
        @endpush

        @push('scripts')
        <script src="{{ asset('js/main/kpis.js') }}" type="module"></script>
        @endpush
</x-app-layout>