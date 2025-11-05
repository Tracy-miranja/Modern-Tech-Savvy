<table class="table table-striped" id="jobApplicationsTable">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>#</th>
            <th>Applicant</th>
            <th>Phone</th>
            <th>Job Title</th>
            <th>Stage</th>
            <th>Applied On</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($applications as $index => $application)
        <tr>
            <td><input type="checkbox" name="application_ids[]" value="{{ $application->id }}"></td>
            <td>{{ $applications->firstItem() + $index }}</td>
            <td>
                @if($application->applicant->user)
                <div class="d-flex align-items-center" style="gap: 3px">
                    <span class="table-avatar">
                        <a class="employee__avatar mr-5" href="">
                            <img class="img-48 border-circle" src="{{ $application->applicant->user->getImageUrl() }}"
                                alt="{{ $application->applicant->user->name }}">
                        </a>
                    </span>
                    <span>
                        <strong>{{ $application->applicant->user->name }}</strong> <br>
                        <small class="text-muted">{{ $application->applicant->user->email }}</small>
                    </span>
                </div>
                @else
                <div class="d-flex align-items-center" style="gap: 3px">
                    <span class="badge bg-secondary">No User</span>
                    <span>
                        <strong>Applicant #{{ $application->applicant->id }}</strong>
                    </span>
                </div>
                @endif
            </td>
            <td>
                @if($application->applicant->user)
                    {{ $application->applicant->user->phone }}
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>{{ $application->jobPost->title }}</td>
            <td>
                <span
                    class="badge bg-{{ $application->stage === 'applied' ? 'primary' : ($application->stage === 'shortlisted' ? 'success' : ($application->stage === 'rejected' ? 'danger' : 'info')) }}">
                    {{ ucfirst($application->stage) }}
                </span>
            </td>
            <td>{{ $application->created_at->format('d M, Y') }}</td>
            <td>
                <a href="{{ route('business.applications.view', [$currentBusiness->slug, $application->id]) }}"
                    class="btn btn-sm btn-info">
                    <i class="bi bi-eye me-2"></i> View
                </a>
                <button class="btn btn-sm btn-danger" onclick="deleteJobApplication({{ $application->id }})">
                    <i class="bi bi-trash me-2"></i> Delete
                </button>
                @if($application->applicant->user)
                <button class="btn btn-sm btn-primary"
                    onclick="openScheduleInterviewModal({{ $application->id }}, '{{ $application->applicant->user->name }}', '{{ $application->jobPost->title }}')">
                    <i class="bi bi-calendar-plus me-2"></i> Schedule Interview
                </button>
                @endif
                <button class="btn btn-sm btn-success" onclick="shortlistApplications(this, [{{ $application->id }}])">
                    <i class="bi bi-check-circle me-2"></i> Shortlist
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center text-muted">No applications found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="d-flex justify-content-between mt-3">
    {{ $applications->links() }}
    <div class="btn-group" role="group">
        <button class="btn btn-danger" onclick="deleteJobApplications(this)"><i class="bi bi-trash"></i> Delete
            Selected</button>
        <button class="btn btn-success" onclick="shortlistApplications(this)"><i class="bi bi-check-circle"></i>
            Shortlist Selected</button>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="bi bi-arrow-up-circle me-2"></i> Update Stage
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'applied')">Applied</a></li>
                <li><a class="dropdown-item" href="#"
                        onclick="updateApplicationStage(this, 'shortlisted')">Shortlisted</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'in_progress')">In
                        Progress</a></li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'rejected')">Rejected</a>
                </li>
                <li><a class="dropdown-item" href="#" onclick="updateApplicationStage(this, 'Finished')">Finished</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
    $('#selectAll').on('click', function() {
        $('input[name="application_ids[]"]').prop('checked', this.checked);
    });
</script>
