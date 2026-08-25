<form id="jobPostForm" class="needs-validation" novalidate>
    @csrf
    @isset($jobPost)
    <input type="hidden" name="job_post_slug" value="{{ $jobPost->slug }}">
    @endisset

    <div class="row g-2">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Job Details</h5>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control tinyMce" name="description"
                            required>@isset($jobPost){{ $jobPost->description }}@endisset</textarea>
                        <div class="invalid-feedback">Please provide a description.</div>
                    </div>
                    <div class="mb-3">
                        <label for="requirements" class="form-label">Requirements</label>
                        <textarea class="form-control" name="requirements" rows="4"
                            placeholder="List requirements here...">@isset($jobPost){{ $jobPost->requirements }}@endisset</textarea>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                        data-bs-target="#aiModal">
                        <i class="fa-solid fa-robot me-1"></i> Generate with AI
                    </button>
                    <small class="text-muted d-block mt-2"><i class="fa-solid fa-circle-info me-1"></i> AI content may
                        require editing.</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title"
                            value="@isset($jobPost){{ $jobPost->title }}@endisset" required>
                        <div class="invalid-feedback">Please enter a job title.</div>
                    </div>
                    <div class="mb-3">
                        <label for="employment_type" class="form-label">Employment Type <span
                                class="text-danger">*</span></label>
                        <select class="form-select" name="employment_type" required>
                            <option value="" disabled selected>Select type</option>
                            @foreach(['full-time' => 'Full-Time', 'part-time' => 'Part-Time', 'contract' => 'Contract',
                            'internship' => 'Internship'] as $value => $label)
                            <option value="{{ $value }}" @isset($jobPost) @if($jobPost->employment_type == $value)
                                selected @endif @endisset>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select an employment type.</div>
                    </div>
                    <div class="mb-3">
                        <label for="place" class="form-label">Location <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="place"
                            value="@isset($jobPost){{ $jobPost->place }}@endisset" required>
                        <div class="invalid-feedback">Please enter a location.</div>
                    </div>
                    <div class="mb-3">
                        <label for="salary_range" class="form-label">Salary Range</label>
                        <input type="text" class="form-control" name="salary_range" placeholder="e.g., 30,000 - 50,000"
                            value="@isset($jobPost){{ $jobPost->salary_range }}@endisset">
                    </div>
                    <div class="mb-3">
                        <label for="number_of_positions" class="form-label">Number of Positions <span
                                class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="number_of_positions" min="1"
                            value="@isset($jobPost){{ $jobPost->number_of_positions }}@else 1 @endisset" required>
                        <div class="invalid-feedback">Please enter a valid number.</div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status">
                            @foreach(['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $value => $label)
                            <option value="{{ $value }}" @isset($jobPost) @if($jobPost->status == $value) selected
                                @endif @endisset>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="closing_date" class="form-label">Closing Date</label>
                        <input type="date" class="form-control" name="closing_date"
                            value="@isset($jobPost){{ $jobPost->closing_date }}@endisset">
                    </div>
                    <div class="mb-3">
                        <label for="is_public" class="form-label">Public?</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_public" value="1" @isset($jobPost)
                                @if($jobPost->is_public) checked @endif @endisset>
                            <label class="form-check-label">Make this job post public</label>
                        </div>
                    </div>
                    <button type="button" onclick="saveJobPost(this)" class="btn btn-primary w-100">
                        <i class="fa-regular fa-check-circle me-1"></i> @isset($jobPost) Update @else Create @endisset
                        Job Post
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="aiModal" tabindex="-1" aria-labelledby="aiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aiModalLabel">AI-Generated Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="aiGeneratedContent" class="p-3 border rounded"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="useAiContent">Use Description</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('jobPostForm');
    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>