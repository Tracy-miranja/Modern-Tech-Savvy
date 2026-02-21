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
        @foreach($applications as $index => $application)
            <tr>
                <td>
                    <input type="checkbox" name="application_ids[]" value="{{ $application->id }}">
                </td>

                <td>{{ $applications->firstItem() + $index }}</td>

                <td>
                    @if($application->applicant->user)
                        <div class="d-flex align-items-center" style="gap: 3px">
                            <span class="table-avatar">
                                <a class="employee__avatar mr-5" href="#">
                                    <img class="img-48 border-circle"
                                         src="{{ $application->applicant->user->getImageUrl() }}"
                                         alt="{{ $application->applicant->user->name }}">
                                </a>
                            </span>
                            <span>
                                <strong>{{ $application->applicant->user->name }}</strong><br>
                                <small class="text-muted">{{ $application->applicant->user->email }}</small>
                            </span>
                        </div>
                    @else
                        <span class="badge bg-secondary">
                            Applicant #{{ $application->applicant->id }}
                        </span>
                    @endif
                </td>

                <td>
                    {{ optional($application->applicant->user)->phone ?? '—' }}
                </td>

                <td>{{ $application->jobPost->title }}</td>

                <td>
                    <span class="badge bg-{{
                        $application->stage === 'applied' ? 'primary' :
                        ($application->stage === 'shortlisted' ? 'success' :
                        ($application->stage === 'rejected' ? 'danger' : 'info'))
                    }}">
                        {{ ucfirst($application->stage) }}
                    </span>
                </td>

                <td>{{ $application->created_at->format('d M, Y') }}</td>

                <td class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('business.applications.view', [$currentBusiness->slug, $application->id]) }}"
                       class="btn btn-sm btn-info">
                        <i class="bi bi-eye"></i> View
                    </a>

                    <button class="btn btn-sm btn-danger"
                            onclick="deleteJobApplication({{ $application->id }})">
                        <i class="bi bi-trash"></i> Delete
                    </button>

                    @if($application->applicant->user)
                        <button class="btn btn-sm btn-primary"
                                onclick="openScheduleInterviewModal(
                                    {{ $application->id }},
                                    '{{ $application->applicant->user->name }}',
                                    '{{ $application->jobPost->title }}'
                                )">
                            <i class="bi bi-calendar-plus"></i> Interview
                        </button>
                    @endif

                    <button class="btn btn-sm btn-success"
                            onclick="shortlistApplications(this, [{{ $application->id }}])">
                        <i class="bi bi-check-circle"></i> Shortlist
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($applications->isEmpty())
    <div class="text-center text-muted py-4">
        No applications found.
    </div>
@endif

<div class="d-flex justify-content-between mt-3">
    {{ $applications->links() }}

    <div class="btn-group">
        <button class="btn btn-danger" onclick="deleteJobApplications(this)">
            <i class="bi bi-trash"></i> Delete Selected
        </button>

        <button class="btn btn-success" onclick="shortlistApplications(this)">
            <i class="bi bi-check-circle"></i> Shortlist Selected
        </button>

        <div class="btn-group">
            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                Update Stage
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" onclick="updateApplicationStage(this, 'applied')">Applied</a></li>
                <li><a class="dropdown-item" onclick="updateApplicationStage(this, 'shortlisted')">Shortlisted</a></li>
                <li><a class="dropdown-item" onclick="updateApplicationStage(this, 'in_progress')">In Progress</a></li>
                <li><a class="dropdown-item" onclick="updateApplicationStage(this, 'rejected')">Rejected</a></li>
                <li><a class="dropdown-item" onclick="updateApplicationStage(this, 'finished')">Finished</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
    $('#selectAll').on('click', function () {
        $('input[name="application_ids[]"]').prop('checked', this.checked);
    });
</script>
