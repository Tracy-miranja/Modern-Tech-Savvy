<x-app-layout title="Campaign Analytics - {{ $campaign->name }}">
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
                        <h5 class="mb-0 text-dark fw-semibold">{{ $campaign->name }} - Analytics</h5>
                        <div>
                            <a href="{{ route('business.crm.campaigns.view', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}"
                                class="btn btn-outline-primary btn-sm rounded-pill px-3 me-2">View Campaign</a>
                            <a href="{{ route('business.crm.campaigns.index', ['business' => $currentBusiness->slug]) }}"
                                class="btn btn-outline-secondary btn-sm rounded-pill px-3">Back to Campaigns</a>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        <nav aria-label="breadcrumb" class="mb-4">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('business.index', ['business' => $currentBusiness->slug]) }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('business.crm.campaigns.index', ['business' => $currentBusiness->slug]) }}">Campaigns</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a
                                        href="{{ route('business.crm.campaigns.view', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}">{{ $campaign->name }}</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Analytics</li>
                            </ol>
                        </nav>

                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-3">Campaign Summary</h6>
                            <div class="bg-white p-3 rounded-3 border">
                                <p class="mb-2"><strong>Name:</strong> {{ $campaign->name }}</p>
                                <p class="mb-2"><strong>Short Link:</strong>
                                    @if($campaign->shortLink)
                                <div class="input-group w-75 mb-2">
                                    <input type="text" class="form-control rounded-3"
                                        value="{{ url('/campaign/' . $campaign->shortLink->slug) }}" readonly
                                        id="shortLink">
                                    <button class="btn btn-outline-primary rounded-3 copy-link" type="button"
                                        data-link="{{ url('/campaign/' . $campaign->shortLink->slug) }}">Copy</button>
                                </div>
                                @else
                                <span class="text-muted">No short link generated.</span>
                                @endif
                                </p>
                                <p class="mb-2"><strong>Total Visits:</strong> {{ $campaign->shortLink->visits ?? 0 }}
                                </p>
                                <p class="mb-2"><strong>Has Survey:</strong> {{ $campaign->has_survey ? 'Yes' : 'No' }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-3">Short Link Visits</h6>
                            <div class="table-responsive rounded-3">
                                <div id="visitsTable" class="bg-white p-3 border">
                                    <div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading visits...
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($campaign->has_survey)
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold text-dark">Survey Results</h6>
                                <a href="{{ route('business.crm.campaigns.surveys.export', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}"
                                    class="btn btn-outline-primary btn-sm rounded-pill px-3">Export to XLSX</a>
                            </div>
                            <div class="table-responsive rounded-3">
                                <div id="surveyTable" class="bg-white p-3 border">
                                    <div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading survey
                                        results...</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    /* General Styling */
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }

    .form-control {
        border-color: #e9ecef;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .btn-primary,
    .btn-outline-primary {
        transition: background-color 0.2s, transform 0.2s;
    }

    .btn-primary:hover,
    .btn-outline-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-1px);
    }

    .btn-outline-primary {
        border-color: #007bff;
        color: #007bff;
    }

    .btn-outline-primary:hover {
        color: #fff;
    }

    .badge {
        font-weight: 500;
        padding: 6px 12px;
    }

    h6 {
        color: #343a40;
    }

    .breadcrumb {
        background-color: transparent;
        padding: 0;
    }

    .breadcrumb-item a {
        color: #007bff;
        text-decoration: none;
    }

    .breadcrumb-item a:hover {
        text-decoration: underline;
    }

    /* Table Styling */
    .table-responsive {
        border-radius: 8px;
        overflow-x: auto;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .table th {
        font-weight: 600;
        color: #343a40;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/campaigns.js') }}" type="module"></script>
    @endpush
</x-app-layout>