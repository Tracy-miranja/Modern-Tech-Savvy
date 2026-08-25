<x-app-layout title="Contact Submissions">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between text-dark">
                        <h5 class="mb-0">Contact Submissions</h5>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <input type="hidden" id="businessSlug" value="{{ $currentBusiness->slug }}">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Export
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item export-contacts" href="#" data-format="xlsx">Export as
                                            XLSX</a></li>
                                    <li><a class="dropdown-item export-contacts" href="#" data-format="csv">Export as
                                            CSV</a></li>
                                    <li><a class="dropdown-item export-contacts" href="#" data-format="pdf">Export as
                                            PDF</a></li>
                                </ul>
                            </div>
                            <a href="{{ route('business.crm.contacts.create', ['business' => $currentBusiness->slug]) }}"
                                class="btn btn-primary btn-sm ms-2">Add Contact</a>
                        </div>
                    </div>
                    <div class="card-body">

                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('business.index', ['business' => $currentBusiness->slug]) }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Contact Submissions</li>
                            </ol>
                        </nav>

                        <div class="mb-3">
                            <input type="text" id="contactFilter" class="form-control"
                                placeholder="Filter by name, email, or message...">
                        </div>

                        <div id="contactsTable">
                            <div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading contacts...</div>
                        </div>

                        <div class="toast-container position-fixed bottom-0 end-0 p-3">
                            <div id="contactToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header">
                                    <strong class="me-auto">Notification</strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast"
                                        aria-label="Close"></button>
                                </div>
                                <div class="toast-body"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/crm-contacts.js') }}" type="module"></script>
    @endpush
</x-app-layout>