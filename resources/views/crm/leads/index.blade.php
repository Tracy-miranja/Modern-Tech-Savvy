<x-app-layout title="Leads">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between text-dark">
                        <h5 class="mb-0">Leads</h5>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                    id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Export
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item export-leads" href="#" data-format="xlsx">Export as
                                            XLSX</a></li>
                                    <li><a class="dropdown-item export-leads" href="#" data-format="csv">Export as
                                            CSV</a></li>
                                    <li><a class="dropdown-item export-leads" href="#" data-format="pdf">Export as
                                            PDF</a></li>
                                    <li>
                                </ul>
                            </div>
                            <a href="{{ route('business.crm.leads.create', ['business' => $currentBusiness->slug]) }}"
                                class="btn btn-primary btn-sm ms-2">Create Lead</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="text" id="leadFilter" class="form-control"
                                placeholder="Filter by name, email, or label...">
                        </div>
                        <div id="leadsTable">
                            <div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading leads...</div>
                        </div>

                        <input type="hidden" id="businessSlug" value="{{ $currentBusiness->slug }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/leads.js') }}" type="module"></script>
    @endpush
</x-app-layout>