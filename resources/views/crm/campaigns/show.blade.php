<x-app-layout title="View Campaign - {{ $campaign->name }}">
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
                        <h5 class="mb-0 text-dark fw-semibold">{{ $campaign->name }}</h5>
                        <div>
                            <a href="{{ route('business.crm.campaigns.analytics', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}"
                                class="btn btn-outline-primary btn-sm rounded-pill px-3 me-2">View Analytics</a>
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
                                <li class="breadcrumb-item active" aria-current="page">{{ $campaign->name }}</li>
                            </ol>
                        </nav>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-dark mb-3">Campaign Details</h6>
                                <div class="bg-white p-3 rounded-3 border">
                                    <p class="mb-2"><strong>Name:</strong> {{ $campaign->name }}</p>
                                    <p class="mb-2"><strong>UTM Source:</strong> {{ $campaign->utm_source }}</p>
                                    <p class="mb-2"><strong>UTM Medium:</strong> {{ $campaign->utm_medium }}</p>
                                    <p class="mb-2"><strong>UTM Campaign:</strong> {{ $campaign->utm_campaign }}</p>
                                    <p class="mb-2"><strong>Target URL:</strong>
                                        <a href="{{ $campaign->target_url }}" target="_blank"
                                            class="text-primary">{{ $campaign->target_url }}</a>
                                    </p>
                                    <p class="mb-2"><strong>Start Date:</strong> {{ optional($campaign->start_date)->format('d M Y') ?? 'N/A' }}</p>
                                    <p class="mb-2"><strong>End Date:</strong> {{ optional($campaign->end_date)->format('d M Y') ?? 'N/A' }}</p>
                                    <p class="mb-2"><strong>Status:</strong>
                                        <span
                                            class="badge rounded-pill {{ $campaign->status === 'active' ? 'bg-success' : ($campaign->status === 'inactive' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </p>
                                    <p class="mb-2"><strong>Has Survey:</strong>
                                        {{ $campaign->has_survey ? 'Yes' : 'No' }}
                                        @if ($campaign->has_survey)
                                        <a href="{{ route('business.crm.campaigns.surveys.create', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}"
                                            class="btn btn-sm btn-outline-primary ms-2">Manage Survey</a>
                                        @else
                                        <a href="{{ route('business.crm.campaigns.surveys.create', ['business' => $currentBusiness->slug, 'campaign' => $campaign->id]) }}"
                                            class="btn btn-sm btn-outline-primary ms-2">Create Survey</a>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-dark mb-3">Short Link</h6>
                                <div class="bg-white p-3 rounded-3 border">
                                    @if($campaign->shortLink)
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control rounded-3"
                                            value="{{ url('/campaign/' . $campaign->shortLink->slug) }}" readonly
                                            id="shortLink">
                                        <button class="btn btn-outline-primary rounded-3 copy-link" type="button"
                                            data-link="{{ url('/campaign/' . $campaign->shortLink->slug) }}">Copy</button>
                                    </div>
                                    <p class="mb-0"><strong>Visits:</strong> {{ $campaign->shortLink->visits }}</p>
                                    @else
                                    <p class="text-muted">No short link generated.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div>
                            <h6 class="fw-semibold text-dark mb-3">Related Leads</h6>
                            @if($campaign->leads->isEmpty())
                            <p class="text-muted">No leads associated with this campaign.</p>
                            @else
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" id="leadsTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($campaign->leads as $lead)
                                        <tr>
                                            <td>{{ $lead->name }}</td>
                                            <td>{{ $lead->email }}</td>
                                            <td>{{ $lead->phone ?? 'N/A' }}</td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill {{ $lead->status === 'new' ? 'bg-info' : ($lead->status === 'contacted' ? 'bg-primary' : ($lead->status === 'qualified' ? 'bg-warning text-dark' : ($lead->status === 'converted' ? 'bg-success' : 'bg-danger'))) }}">
                                                    {{ ucfirst($lead->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('business.crm.leads.view', ['business' => $currentBusiness->slug, 'lead' => $lead->id]) }}"
                                                    class="btn btn-sm btn-outline-primary rounded-pill px-3">View</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
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

    .table-responsive {
        border-radius: 8px;
        overflow-x: auto;
    }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/campaigns.js') }}" type="module"></script>
    @endpush
</x-app-layout>