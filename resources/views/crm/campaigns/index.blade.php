<x-app-layout title="Campaigns">
    <div class="container-fluid py-4">
        <div class="row">

            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between text-dark">
                        <h5 class="mb-0">Campaigns</h5>
                        <div class="d-flex align-items-center">
                            <a href="{{ route('business.crm.campaigns.create', ['business' => $currentBusiness->slug]) }}"
                                class="btn btn-secondary btn-sm me-2">Create Campaign</a>
                            <div class="dropdown">
                                <input type="hidden" id="businessSlug" value="{{ $currentBusiness->slug }}">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Export
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item export-campaigns" href="#" data-format="xlsx">Export as
                                            XLSX</a></li>
                                    <li><a class="dropdown-item export-campaigns" href="#" data-format="csv">Export as
                                            CSV</a></li>
                                    <li><a class="dropdown-item export-campaigns" href="#" data-format="pdf">Export as
                                            PDF</a></li>
                                    <li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('business.index', ['business' => $currentBusiness->slug]) }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Campaigns</li>
                            </ol>
                        </nav>

                        <div class="mb-3">
                            <input type="text" id="campaignFilter" class="form-control"
                                placeholder="Search campaigns...">
                        </div>

                        <div id="campaignsTable"></div>

                        <div class="toast-container position-fixed bottom-0 end-0 p-3">
                            <div id="campaignToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
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
    <script src="{{ asset('js/main/campaigns.js') }}" type="module"></script>
    @endpush
</x-app-layout>