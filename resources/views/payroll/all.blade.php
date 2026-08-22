<x-app-layout title="{{ $page }}">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <h5 class="fw-bold text-dark mb-4">Filter Payrolls</h5>
                <div class="card border-0 rounded-3 p-4 mb-4">
                    <form id="payrollFilterForm" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="month" class="form-label">Month</label>
                            <select name="month" id="month" class="form-select shadow-sm border-0">
                                <option value="">All</option>
                                @foreach ($months as $month)
                                <option value="{{ $month }}"
                                    {{ isset($selectedMonth) && $selectedMonth == $month ? 'selected' : '' }}>
                                    {{ now()->month($month)->monthName }} ({{ str_pad($month, 2, '0', STR_PAD_LEFT) }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select name="year" class="form-select" id="payroll-year" required>
                                @foreach($years as $year)
                                <option value="{{ $year }}" {{ $year == now()->year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
    <label for="location" class="form-label">Location</label>
    <select name="location" id="location" class="form-select shadow-sm border-0">
        <option value="">All</option>
        @foreach ($locations as $location)
        <option value="{{ $location->id }}">{{ $location->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-2">
    <label for="department" class="form-label">Department</label>
    <select name="department" id="department" class="form-select shadow-sm border-0">
        <option value="">All</option>
        @foreach ($departments as $department)
        <option value="{{ $department->id }}">{{ $department->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-2">
    <label for="job_category" class="form-label">Job Category</label>
    <select name="job_category" id="job_category" class="form-select shadow-sm border-0">
        <option value="">All</option>
        @foreach ($jobCategories as $jobCategory)
        <option value="{{ $jobCategory->id }}">{{ $jobCategory->name }}</option>
        @endforeach
    </select>
</div>
                        <div class="col-md-2 mt-3">
                            <button type="button" class="btn btn-dark px-4" onclick="filterPayrolls()">Filter</button>
                            <button type="button" class="btn btn-outline-dark px-4"
                                onclick="clearFilters()">Clear</button>
                        </div>
                    </form>
                </div>

                <div class="mb-4">
                    <h4 class="fw-semibold text-dark mb-3">Summary Payroll List</h4>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button class="btn btn-outline-dark btn-sm" onclick="deletePayroll()">Delete</button>
                        <button class="btn btn-outline-dark btn-sm" onclick="closeMonth()">Close Month</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="emailPayslips()">Email Payslip</button>
                        <button class="btn btn-outline-dark btn-sm" onclick="emailP9()">Email P9</button>
                <div id="p9-loading-spinner" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(255,255,255,0.7); z-index:9999; text-align:center;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Sending P9...</span>
        </div>
        <div class="mt-2">Sending P9 forms...</div>
    </div>
</div>

                        <button class="btn btn-outline-dark btn-sm" onclick="downloadPayroll()">Download</button>
                    </div>
                    <h5 class="text-muted">
                        <span class="text-danger">{{ $payrolls->count() }} payroll(s) found</span> |
                        Total Payroll: {{ number_format($totalPayroll, 2) }} |
                        Total Net Pay: {{ number_format($totalNetPay, 2) }}
                    </h5>
                </div>

                <div class="card border-0 p-3">
                    <div id="pastPayrollsContainer">
                        @include('payroll._past')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    window.businessSlug = '{{ $business->slug }}';
    </script>
    <script src="{{ asset('js/main/allpayrolls.js') }}" type="module"></script>
    @endpush
</x-app-layout>
