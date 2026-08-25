<x-app-layout title="View Lead - {{ $lead->name }}">
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
                        <h5 class="mb-0 text-dark fw-semibold">Lead: {{ $lead->name }}</h5>
                        <a href="{{ route('business.crm.leads.index', ['business' => $currentBusiness]) }}"
                            class="btn btn-outline-secondary btn-sm rounded-pill px-3">Back to Leads</a>
                    </div>
                    <div class="card-body p-4">

                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-dark mb-3">Lead Details</h6>
                                <div class="bg-white p-3 rounded-3 border">
                                    <p class="mb-2"><strong>Name:</strong> {{ $lead->name }}</p>
                                    <p class="mb-2"><strong>Email:</strong> {{ $lead->email }}</p>
                                    <p class="mb-2"><strong>Phone:</strong> {{ $lead->phone ?? 'N/A' }}</p>
                                    <p class="mb-2"><strong>Country:</strong> {{ $lead->country ?? 'N/A' }}</p>
                                    <p class="mb-2"><strong>Source:</strong> {{ $lead->source ?? 'N/A' }}</p>
                                    <p class="mb-2"><strong>Campaign:</strong>
                                        {{ $lead->campaign ? $lead->campaign->name : 'N/A' }}
                                    </p>
                                    <p class="mb-2"><strong>Status:</strong>
                                        <span
                                            class="badge rounded-pill {{ $lead->status === 'new' ? 'bg-info' : ($lead->status === 'contacted' ? 'bg-primary' : ($lead->status === 'qualified' ? 'bg-warning text-dark' : ($lead->status === 'converted' ? 'bg-success' : 'bg-danger'))) }}">
                                            {{ ucfirst($lead->status) }}
                                        </span>
                                    </p>
                                    <p class="mb-2"><strong>Label:</strong>
                                        <span
                                            class="badge rounded-pill {{ $lead->label === 'High Priority' ? 'bg-danger' : ($lead->label === 'Low Priority' ? 'bg-secondary' : ($lead->label === 'Follow Up' ? 'bg-info' : ($lead->label === 'Hot Lead' ? 'bg-warning text-dark' : ($lead->label === 'Cold Lead' ? 'bg-primary' : 'bg-dark')))) }}">
                                            {{ $lead->label ?? 'N/A' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-dark mb-3">Survey Responses</h6>
                                <div class="bg-white p-3 rounded-3 border">
                                    @if ($lead->survey_responses)
                                    @foreach ($lead->survey_responses as $field)
                                    <p class="mb-2"><strong>{{ $field['label'] }}:</strong>
                                        {{ $field['value'] ?? 'N/A' }}
                                    </p>
                                    @endforeach
                                    @else
                                    <p class="text-muted">No survey responses available.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-3">Update Lead</h6>
                            <form id="statusForm" class="bg-white p-4 rounded-3 border"
                                data-action="{{ route('crm.leads.update') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $lead->id }}">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="status" class="form-label fw-medium">Status</label>
                                        <select class="form-select rounded-3" name="status" required>
                                            <option value="new" {{ $lead->status === 'new' ? 'selected' : '' }}>New
                                            </option>
                                            <option value="contacted"
                                                {{ $lead->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                            <option value="qualified"
                                                {{ $lead->status === 'qualified' ? 'selected' : '' }}>Qualified</option>
                                            <option value="converted"
                                                {{ $lead->status === 'converted' ? 'selected' : '' }}>Converted</option>
                                            <option value="lost" {{ $lead->status === 'lost' ? 'selected' : '' }}>Lost
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="label" class="form-label fw-medium">Label</label>
                                        <select class="form-select rounded-3" name="label">
                                            <option value="">None</option>
                                            <option value="High Priority"
                                                {{ $lead->label === 'High Priority' ? 'selected' : '' }}>High Priority
                                            </option>
                                            <option value="Low Priority"
                                                {{ $lead->label === 'Low Priority' ? 'selected' : '' }}>Low Priority
                                            </option>
                                            <option value="Follow Up"
                                                {{ $lead->label === 'Follow Up' ? 'selected' : '' }}>Follow Up</option>
                                            <option value="Hot Lead"
                                                {{ $lead->label === 'Hot Lead' ? 'selected' : '' }}>Hot Lead</option>
                                            <option value="Cold Lead"
                                                {{ $lead->label === 'Cold Lead' ? 'selected' : '' }}>Cold Lead</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-4">Update Lead</button>
                            </form>
                        </div>

                        <div class="mb-5">
                            <h6 class="fw-semibold text-dark mb-3">Log Activity</h6>
                            <form id="activityForm" class="bg-white p-4 rounded-3 border"
                                data-action="{{ route('crm.lead-activities.store') }}">
                                @csrf
                                <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                                <div class="mb-3">
                                    <label for="activity_type" class="form-label fw-medium">Activity Type</label>
                                    <select class="form-select rounded-3" id="activity_type" name="activity_type"
                                        required>
                                        <option value="call">Call</option>
                                        <option value="email">Email</option>
                                        <option value="meeting">Meeting</option>
                                        <option value="note">Note</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label fw-medium">Description</label>
                                    <textarea class="form-control rounded-3" id="description" name="description"
                                        rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-4">Log Activity</button>
                            </form>
                        </div>

                        <div>
                            <h6 class="fw-semibold text-dark mb-4">Activity Log</h6>
                            @if ($activities->isEmpty())
                            <p class="text-muted">No activities logged.</p>
                            @else
                            <div class="timeline">
                                @foreach ($activities as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <i
                                            class="fas {{ $activity->activity_type === 'call' ? 'fa-phone' : ($activity->activity_type === 'email' ? 'fa-envelope' : ($activity->activity_type === 'meeting' ? 'fa-users' : 'fa-sticky-note')) }} text-primary"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="card shadow-sm border-0 rounded-3">
                                            <div class="card-body p-3">
                                                <h6 class="fw-semibold mb-1">{{ ucfirst($activity->activity_type) }}
                                                </h6>
                                                <p class="mb-2">{{ $activity->description }}</p>
                                                <small class="text-muted">
                                                    By {{ $activity->user ? $activity->user->name : 'System' }} on
                                                    {{ $activity->created_at->format('M d, Y H:i') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
        /* Timeline Styling */
        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-icon {
            position: absolute;
            left: -40px;
            top: 10px;
            width: 30px;
            height: 30px;
            background-color: #f1f3f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-icon i {
            font-size: 16px;
        }

        .timeline-content {
            margin-left: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }

        .timeline-item:last-child .timeline-content {
            margin-bottom: 0;
        }

        /* General Styling */
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .form-control,
        .form-select {
            border-color: #e9ecef;
            transition: border-color 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.2s, transform 0.2s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }

        .badge {
            font-weight: 500;
            padding: 6px 12px;
        }

        h6 {
            color: #343a40;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('js/main/leads.js') }}" type="module"></script>
    @endpush
</x-app-layout>