<form id="applicantForm" method="POST">
    @csrf
    @if(isset($applicant))
    <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
    @endif

    <div class="row">

        <div class="col-md-6">
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" name="first_name" id="first_name"
                    value="{{ $applicant?->user->name ?? old('first_name') }}" placeholder="First Name">
            </div>
            <div class="mb-3">
                <label for="middle_name" class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="middle_name" id="middle_name"
                    value="{{ old('middle_name') }}" placeholder="Middle Name">
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" name="last_name" id="last_name" value="{{ old('last_name') }}"
                    placeholder="Last Name">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email"
                    value="{{ $applicant?->user->email ?? old('email') }}" placeholder="Email">
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" class="phone-input-control" name="phone" id="phone"
                    value="{{ $applicant?->user->phone ?? old('phone') }}">
                <input type="hidden" name="code" id="code" value="{{ old('code') }}">
                <input type="hidden" name="country" id="country" value="{{ $applicant?->country ?? old('country') }}">
            </div>
            @if(!isset($applicant))
            <div class="mb-3">
                <label for="password" class="form-label">Login Password <strong>Enable applicant to log
                        in.</strong></label>
                <input type="text" class="form-control" name="password" id="password" placeholder="Login password">
            </div>
            @endif
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" name="address" id="address"
                    value="{{ $applicant?->address ?? old('address') }}" placeholder="123 Main St">
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" name="city" id="city"
                    value="{{ $applicant?->city ?? old('city') }}" placeholder="Nairobi">
            </div>
            <div class="mb-3">
                <label for="state" class="form-label">State</label>
                <input type="text" class="form-control" name="state" id="state"
                    value="{{ $applicant?->state ?? old('state') }}" placeholder="Nairobi County">
            </div>
            <div class="mb-3">
                <label for="zip_code" class="form-label">Zip Code</label>
                <input type="text" class="form-control" name="zip_code" id="zip_code"
                    value="{{ $applicant?->zip_code ?? old('zip_code') }}">
            </div>
            <div class="mb-3">
                <label for="country" class="form-label">Country</label>
                <input type="text" class="form-control" name="country" id="country"
                    value="{{ $applicant?->country ?? old('country') }}" placeholder="Kenya">
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="linkedin_profile" class="form-label">LinkedIn Profile</label>
                <input type="url" class="form-control" name="linkedin_profile" id="linkedin_profile"
                    value="{{ $applicant?->linkedin_profile ?? old('linkedin_profile') }}"
                    placeholder="https://linkedin.com/in/username">
            </div>
            <div class="mb-3">
                <label for="portfolio_url" class="form-label">Portfolio URL</label>
                <input type="url" class="form-control" name="portfolio_url" id="portfolio_url"
                    value="{{ $applicant?->portfolio_url ?? old('portfolio_url') }}"
                    placeholder="https://myportfolio.com">
            </div>
            <div class="mb-3">
                <label for="current_job_title" class="form-label">Current Job Title</label>
                <input type="text" class="form-control" name="current_job_title" id="current_job_title"
                    value="{{ $applicant?->current_job_title ?? old('current_job_title') }}"
                    placeholder="Software Engineer">
            </div>
            <div class="mb-3">
                <label for="current_company" class="form-label">Current Company</label>
                <input type="text" class="form-control" name="current_company" id="current_company"
                    value="{{ $applicant?->current_company ?? old('current_company') }}" placeholder="Google">
            </div>
            <div class="mb-3">
                <label for="experience_level" class="form-label">Experience Level</label>
                <select name="experience_level" id="experience_level" class="form-control">
                    <option value="">Select Level</option>
                    <option value="Entry-level"
                        {{ old('experience_level', $applicant?->experience_level) == 'Entry-level' ? 'selected' : '' }}>
                        Entry-level</option>
                    <option value="Mid-level"
                        {{ old('experience_level', $applicant?->experience_level) == 'Mid-level' ? 'selected' : '' }}>
                        Mid-level</option>
                    <option value="Senior"
                        {{ old('experience_level', $applicant?->experience_level) == 'Senior' ? 'selected' : '' }}>
                        Senior</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="education_level" class="form-label">Education Level</label>
                <select name="education_level" id="education_level" class="form-control">
                    <option value="">Select Level</option>
                    <option value="High School"
                        {{ old('education_level', $applicant?->education_level) == 'High School' ? 'selected' : '' }}>
                        High School</option>
                    <option value="Bachelor's"
                        {{ old('education_level', $applicant?->education_level) == "Bachelor's" ? 'selected' : '' }}>
                        Bachelor's</option>
                    <option value="Master's"
                        {{ old('education_level', $applicant?->education_level) == "Master's" ? 'selected' : '' }}>
                        Master's</option>
                    <option value="PhD"
                        {{ old('education_level', $applicant?->education_level) == 'PhD' ? 'selected' : '' }}>
                        PhD</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="desired_salary" class="form-label">Desired Salary (KES)</label>
                <input type="text" class="form-control" name="desired_salary" id="desired_salary"
                    value="{{ $applicant?->desired_salary ?? old('desired_salary') }}" placeholder="100000">
            </div>
            <div class="mb-3">
                <label for="job_preferences" class="form-label">Job Preferences</label>
                <input type="text" class="form-control" name="job_preferences" id="job_preferences"
                    value="{{ $applicant?->job_preferences ?? old('job_preferences') }}"
                    placeholder="Software Engineering, DevOps">
            </div>
            <div class="mb-3">
                <label for="source" class="form-label">Source</label>
                <input type="text" class="form-control" name="source" id="source"
                    value="{{ $applicant?->source ?? old('source') }}" placeholder="LinkedIn, Referral">
            </div>
        </div>
    </div>

    <button type="button" onclick="saveApplicant(this)" class="btn btn-primary w-100">
        <i class="bi bi-check-circle me-2"></i> {{ isset($applicant) ? 'Update Applicant' : 'Save Applicant' }}
    </button>
</form>