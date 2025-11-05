<form id="jobApplicationForm" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($application))
    <input type="hidden" name="application_id" value="{{ $application->id }}">
    @endif

    <div class="mb-3">
        <label for="applicant_id" class="form-label">Applicant</label>
        <select name="applicant_id" id="applicant_id" class="form-control @error('applicant_id') is-invalid @enderror">
            <option value="">-- Select Applicant --</option>
            @foreach($applicants as $applicant)
            <option value="{{ $applicant->id }}"
                {{ isset($application) && $application->applicant_id == $applicant->id ? 'selected' : '' }}>
                @if($applicant->user)
                    {{ $applicant->user->name }} - {{ $applicant->user->email }}
                @else
                    {{ $applicant->id }} (No user assigned)
                @endif
            </option>
            @endforeach
        </select>
        @error('applicant_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addApplicantModal">
            <i class="bi bi-plus-square-dotted me-2"></i> Edit Applicant
        </button>
    </div>

    <div class="mb-3">
        <label for="job_post_id" class="form-label">Job Post</label>
        <select name="job_post_id" id="job_post_id" class="form-control @error('job_post_id') is-invalid @enderror">
            <option value="">-- Select Job Post --</option>
            @foreach($job_posts as $job)
            <option value="{{ $job->slug }}"
                {{ isset($application) && $application->jobPost->slug == $job->slug ? 'selected' : '' }}>
                {{ $job->title }}
            </option>
            @endforeach
        </select>
        @error('job_post_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="cover_letter" class="form-label">Cover Letter</label>
        <textarea name="cover_letter" id="cover_letter"
            class="form-control tinyMce @error('cover_letter') is-invalid @enderror">{{ $application->cover_letter ?? old('cover_letter') }}</textarea>
        @error('cover_letter')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="attachments" class="form-label">File Attachments</label>
        <input type="file" multiple name="attachments[]" id="attachments"
            class="form-control @error('attachments.*') is-invalid @enderror">
        @error('attachments.*')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="button" onclick="saveApplication(this)" class="btn btn-primary w-100">
        <i class="bi bi-check-circle me-2"></i> {{ isset($application) ? 'Update Application' : 'Submit Application' }}
    </button>
</form>

<!-- Add Applicant Modal -->
<div class="modal fade" id="addApplicantModal" tabindex="-1" aria-labelledby="addApplicantModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addApplicantModalLabel">Add New Applicant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('applicants._form')
            </div>
        </div>
    </div>
</div>
