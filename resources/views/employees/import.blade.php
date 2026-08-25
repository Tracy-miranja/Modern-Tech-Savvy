<x-app-layout title="Import Employees">
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('employees.import') }}" id="importEmployeesForm"
                        enctype="multipart/form-data">
                        @csrf
                        <label for="csv_file" class="mb-2">Employees XLSX File</label>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="file" name="file" id="csv_file" class="form-control" accept=".csv,.xlsx"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary w-100" onclick="importEmployees(this)">
                                    <i class="bi bi-cloud-upload"></i> Import Employees
                                </button>
                            </div>
                        </div>
                        <div id="loading" class="mt-3 text-center" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Importing employees...</p>
                            <p id="progress"></p>
                        </div>
                        <div id="import-result" class="mt-3" style="display: none;">
                            <h5>Import Result</h5>
                            <p id="success-count"></p>
                            <p id="error-count"></p>
                            <ul id="error-list"></ul>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body mb-0 text-center">
                    <a href="{{ route('business.employees.downloadXlsxTemplate', $currentBusiness->slug) }}"
                        class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-excel me-2"></i> Download XLSX Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/employees.js') }}" type="module"></script>
    @endpush
</x-app-layout>