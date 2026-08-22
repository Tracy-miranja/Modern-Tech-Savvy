<table class="table table-hover table-bordered table-striped" id="jobApplicantsTable">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>#</th>
            <th>Applicant Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Location</th>
            <th>Experience Level</th>
            <th>Current Job Title</th>
            <th>Applications</th>
            <th>Documents</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($applicants as $index => $user)
        <tr>
            <td><input type="checkbox" name="applicant_ids[]" value="{{ $user->applicant?->id }}"></td>
            <td>{{ $applicants->firstItem() + $index }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone ?? 'N/A' }}</td>
            <td>{{ $user->applicant?->city ?? 'N/A' }}, {{ $user->applicant?->country ?? 'N/A' }}</td>
            <td>{{ ucfirst($user->applicant?->experience_level ?? 'N/A') }}</td>
            <td>{{ $user->applicant?->current_job_title ?? 'N/A' }}</td>
            <td>{{ $user->applicant?->applications->count() ?? 0 }}</td>
            <td>
                @if ($user->applicant)
                    @foreach ($user->applicant->applications as $application)
                    @foreach ($application->getMedia('applications') as $media)
                    <button class="btn btn-sm btn-primary" data-applicant-id="{{ $user->applicant->id }}"
                        data-media-id="{{ $media->id }}" onclick="downloadDocument(this)">
                        <i class="bi bi-download"></i> {{ $media->file_name }}
                    </button>
                    @endforeach
                    @endforeach
                @endif
            </td>
            <td>
                @if ($user->applicant)
                    <a href="{{ route('business.applicants.view', [$currentBusiness->slug, $user->applicant->id]) }}"
                        class="btn btn-info btn-sm">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <button class="btn btn-warning btn-sm" data-job-applicant="{{ $user->applicant->id }}"
                        onclick="editJobApplicant({{ $user->applicant->id }})">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm" data-job-applicant="{{ $user->applicant->id }}"
                        onclick="deleteJobApplicant(this)">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                @else
                    <span class="text-muted">No applicant profile</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="d-flex justify-content-between mt-3">
    {{ $applicants->links() }}
    <div>
        <button class="btn btn-danger" onclick="deleteJobApplicants(this)"><i class="bi bi-trash"></i> Delete
            Selected</button>
        <button class="btn btn-success" onclick="exportApplicants(this)"><i class="bi bi-file-earmark-excel"></i>
            Export</button>
    </div>
</div>

<!-- Edit Applicant Modal (Added here for index page) -->
<div class="modal fade" id="editApplicantModal" tabindex="-1" role="dialog" aria-labelledby="editApplicantModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editApplicantModalLabel">Edit Applicant</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="edit-applicant-form">
                <!-- Populated via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
    $('#selectAll').on('click', function() {
        $('input[name="applicant_ids[]"]').prop('checked', this.checked);
    });
</script>
