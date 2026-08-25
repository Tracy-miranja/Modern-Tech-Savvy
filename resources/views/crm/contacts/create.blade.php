<x-app-layout title="Create Campaign">
    <div class="container-fluid py-4">
        <div class="row">

            <div class="col-md-9 col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center justify-content-between text-dark">
                        <h5 class="mb-0">Create Campaign</h5>
                        <a href="{{ route('business.crm.campaigns.index', ['business' => $currentBusiness->slug]) }}"
                            class="btn btn-secondary btn-sm">Back to Campaigns</a>
                    </div>
                    <div class="card-body">

                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="{{ route('business.index', ['business' => $currentBusiness->slug]) }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item"><a
                                        href="{{ route('business.crm.campaigns.index', ['business' => $currentBusiness->slug]) }}">Campaigns</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Create</li>
                            </ol>
                        </nav>

                        <form id="campaignForm" action="{{ route('crm.campaigns.store') }}" method="POST"
                            data-redirect="{{ route('business.crm.campaigns.index', ['business' => $currentBusiness->slug]) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Campaign Name</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="utm_source" class="form-label">UTM Source</label>
                                    <input type="text" class="form-control" id="utm_source" name="utm_source"
                                        value="{{ old('utm_source') }}" required>
                                    @error('utm_source')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="utm_medium" class="form-label">UTM Medium</label>
                                    <input type="text" class="form-control" id="utm_medium" name="utm_medium"
                                        value="{{ old('utm_medium') }}" required>
                                    @error('utm_medium')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="utm_campaign" class="form-label">UTM Campaign</label>
                                    <input type="text" class="form-control" id="utm_campaign" name="utm_campaign"
                                        value="{{ old('utm_campaign') }}" required>
                                    @error('utm_campaign')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="target_url" class="form-label">Target URL</label>
                                    <input type="url" class="form-control" id="target_url" name="target_url"
                                        value="{{ old('target_url') }}" required>
                                    @error('target_url')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                        value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="{{ old('end_date') }}">
                                    @error('end_date')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                                            Inactive</option>
                                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>
                                            Completed</option>
                                    </select>
                                    @error('status')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="has_survey"
                                            name="has_survey" value="1" {{ old('has_survey') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="has_survey">Include Survey</label>
                                    </div>
                                    @error('has_survey')
                                    <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Campaign</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/campaigns.js') }}" type="module"></script>
    @endpush
</x-app-layout>