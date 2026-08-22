<form id="employeeForm" enctype="multipart/form-data" class="needs-validation p-3 rounded" novalidate>
    @csrf
    @if(isset($employee))
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    @endif

    <!-- Tabs -->
    <ul class="nav nav-pills mb-3 border-bottom" id="employeeTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active px-3 py-2" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal"
                type="button" role="tab">
                <i class="fa fa-user me-1"></i> Personal
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-3 py-2" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment"
                type="button" role="tab">
                <i class="fa fa-briefcase me-1"></i> Employment
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-3 py-2" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment"
                type="button" role="tab">
                <i class="fa fa-credit-card me-1"></i> Payment
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-3 py-2" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents"
                type="button" role="tab">
                <i class="fa fa-file-alt me-1"></i> Documents
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Personal Tab -->
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <!-- Personal Info Group -->
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-2">Personal Info</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="first_name" id="first_name" class="form-control border-primary"
                            value="{{ isset($employee) ? explode(' ', $employee->user->name)[0] : '' }}"
                            placeholder="First Name" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="last_name" id="last_name" class="form-control border-primary"
                            value="{{ isset($employee) ? (explode(' ', $employee->user->name)[1] ?? '') : '' }}"
                            placeholder="Last Name" required>
                    </div>
                    <div class="col-md-6">
                        <select name="gender" id="gender" class="form-select border-primary" required>
                            <option value="">Select Gender</option>
                            <option value="male"
                                {{ isset($employee) && strtolower($employee->gender) === 'male' ? 'selected' : '' }}>
                                Male</option>
                            <option value="female"
                                {{ isset($employee) && strtolower($employee->gender) === 'female' ? 'selected' : '' }}>
                                Female</option>
                        </select>
                    </div>
                    <div class="col-md-6 position-relative">
                        <label for="date_of_birth" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control border-primary"
                            value="{{ isset($employee) && $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('Y-m-d') : '' }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="place_of_birth" id="place_of_birth" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->place_of_birth : '' }}"
                            placeholder="Place of Birth">
                    </div>
                    <div class="col-md-6">
                        <select name="marital_status" id="marital_status" class="form-select border-primary" required>
                            <option value="">Select Marital Status</option>
                            <option value="single"
                                {{ isset($employee) && strtolower($employee->marital_status) === 'single' ? 'selected' : '' }}>
                                Single</option>
                            <option value="married"
                                {{ isset($employee) && strtolower($employee->marital_status) === 'married' ? 'selected' : '' }}>
                                Married</option>
                            <option value="divorced"
                                {{ isset($employee) && strtolower($employee->marital_status) === 'divorced' ? 'selected' : '' }}>
                                Divorced</option>
                            <option value="widowed"
                                {{ isset($employee) && strtolower($employee->marital_status) === 'widowed' ? 'selected' : '' }}>
                                Widowed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="blood_group" id="blood_group" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->blood_group : '' }}" placeholder="Blood Group">
                    </div>
                </div>
            </div>

            <!-- Contact Group -->
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-2">Contact</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="email" name="email" id="email" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->user->email : '' }}" placeholder="Email" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="phone" id="phone" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->user->phone : '' }}" placeholder="Phone" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="alternate_phone" id="alternate_phone"
                            class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->alternate_phone : '' }}"
                            placeholder="Alternate Phone">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="address" id="address" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->address : '' }}" placeholder="Current Address">
                    </div>
                    <div class="col-12">
                        <input type="text" name="permanent_address" id="permanent_address"
                            class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->permanent_address : '' }}"
                            placeholder="Permanent Address" required>
                    </div>
                </div>
            </div>

            <!-- Identification Group -->
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-2">Identification</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="file_number" id="file_number" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->file_number : '' }}" placeholder="File Number">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="national_id" id="national_id" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->national_id : '' }}" placeholder="National ID">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="tax_no" id="tax_no" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->tax_no : '' }}" placeholder="Tax Number">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="nhif_no" id="nhif_no" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->nhif_no : '' }}" placeholder="NHIF Number">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="nssf_no" id="nssf_no" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->nssf_no : '' }}" placeholder="NSSF Number">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="passport_no" id="passport_no" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->passport_no : '' }}" placeholder="Passport Number">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="place_of_issue" id="place_of_issue" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->place_of_issue : '' }}"
                            placeholder="Place of Issue">
                    </div>
                    <div class="col-md-4 position-relative">
                        <label for="passport_issue_date" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Passport Issue
                            Date</label>
                        <input type="date" name="passport_issue_date" id="passport_issue_date"
                            class="form-control border-primary"
                            value="{{ isset($employee) && $employee->passport_issue_date ? \Carbon\Carbon::parse($employee->passport_issue_date)->format('Y-m-d') : '' }}"
                            placeholder="Passport Issue Date">
                    </div>
                    <div class="col-md-4 position-relative">
                        <label for="passport_expiry_date" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Passport Expiry
                            Date</label>
                        <input type="date" name="passport_expiry_date" id="passport_expiry_date"
                            class="form-control border-primary"
                            value="{{ isset($employee) && $employee->passport_expiry_date ? \Carbon\Carbon::parse($employee->passport_expiry_date)->format('Y-m-d') : '' }}"
                            placeholder="Passport Expiry Date">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="resident_status" id="resident_status"
                            class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->resident_status : '' }}"
                            placeholder="Resident Status (e.g., Resident, Non-Resident)">
                    </div>
                    <div class="col-md-4">
                        <select name="kra_employee_status" id="kra_employee_status" class="form-select border-primary">
                            <option value="">Select KRA Employee Status</option>
                            <option value="Primary Employee"
                                {{ isset($employee) && $employee->kra_employee_status === 'Primary Employee' ? 'selected' : '' }}>
                                Primary Employee</option>
                            <option value="Secondary Employee"
                                {{ isset($employee) && $employee->kra_employee_status === 'Secondary Employee' ? 'selected' : '' }}>
                                Secondary Employee</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Profile Picture -->
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-2">Profile Picture</h6>
                <input type="file" name="profile_picture" id="profile_picture" class="form-control border-primary"
                    accept="image/*" onchange="previewImage(event)">
                <img id="profile_preview"
                    src="{{ isset($employee) && $employee->getFirstMediaUrl('avatars') ? $employee->getFirstMediaUrl('avatars') : '' }}"
                    class="mt-2 rounded"
                    style="max-width: 100px; display: {{ isset($employee) && $employee->getFirstMediaUrl('avatars') ? 'block' : 'none' }};">
            </div>
        </div>

        <!-- Employment Tab -->
        <div class="tab-pane fade" id="employment" role="tabpanel">
            <!-- Work Info Group -->
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-2">Work Info</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="employee_code" id="employee_code" class="form-control border-primary"
                            value="{{ isset($employee) ? $employee->employee_code : '' }}" placeholder="Employee Code"
                            required>
                    </div>
                    <div class="col-md-4">
                        <select name="department_id" id="department_id" class="form-select border-primary">
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                            <option value="{{ $department->id }}"
                                {{ isset($employee) && $employee->department_id == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="location_id" id="location_id" class="form-select border-primary">
                            <option value="">{{ $business->company_name }} (Main Business)</option>
                            @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ isset($employee) && $employee->location_id == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="job_category_id" id="job_category_id" class="form-select border-primary">
                            <option value="">Select Job Category</option>
                            @foreach ($jobCategories as $jobCategory)
                            <option value="{{ $jobCategory->id }}"
                                {{ isset($employee) && optional($employee->employmentDetails)->job_category_id == $jobCategory->id ? 'selected' : '' }}>
                                {{ $jobCategory->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="organogram_role_id" id="organogram_role_id" class="form-select border-primary">
                            <option value="">Select Organogram Role (optional)</option>
                            @foreach (($organogramRoles ?? []) as $organogramRole)
                            <option value="{{ $organogramRole->id }}"
                                {{ isset($employee) && $employee->organogram_role_id == $organogramRole->id ? 'selected' : '' }}>
                                {{ $organogramRole->name }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Used with department to compute this employee's default manager in the organogram.</small>
                    </div>
                    <div class="col-md-4">
                        <select name="employment_term" id="employment_term" class="form-select border-primary" required>
                            <option value="">Select Contract Type</option>
                            <option value="permanent"
                                {{ isset($employee) && optional($employee->employmentDetails)->employment_term === 'permanent' ? 'selected' : '' }}>
                                Permanent</option>
                            <option value="contract"
                                {{ isset($employee) && optional($employee->employmentDetails)->employment_term === 'contract' ? 'selected' : '' }}>
                                Contract</option>
                            <option value="temporary"
                                {{ isset($employee) && optional($employee->employmentDetails)->employment_term === 'temporary' ? 'selected' : '' }}>
                                Temporary</option>
                            <option value="internship"
                                {{ isset($employee) && optional($employee->employmentDetails)->employment_term === 'internship' ? 'selected' : '' }}>
                                Internship</option>
                               <option value="consultant"
    {{ isset($employee) && optional($employee->employmentDetails)->employment_term === 'consultant' ? 'selected' : '' }}>
    Consultant
</option>
<option value="locum"
    {{ isset($employee) && optional($employee->employmentDetails)->employment_term === 'locum' ? 'selected' : '' }}>
    Locum
</option>
                        </select>
                    </div>
                    <div class="col-md-4 position-relative">
                        <label for="employment_date" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Employment
                            Date</label>
                        <input type="date" name="employment_date" id="employment_date"
                            class="form-control border-primary"
                            value="{{ isset($employee) && optional($employee->employmentDetails)->employment_date ? \Carbon\Carbon::parse($employee->employmentDetails->employment_date)->format('Y-m-d') : '' }}"
                            required>
                    </div>
                    <div class="col-md-4 position-relative">
                        <label for="probation_end_date" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">First Probation End
                            Date</label>
                        <input type="date" name="probation_end_date" id="probation_end_date"
                            class="form-control border-primary"
                            value="{{ isset($employee) && optional($employee->employmentDetails)->probation_end_date ? \Carbon\Carbon::parse($employee->employmentDetails->probation_end_date)->format('Y-m-d') : '' }}"
                            placeholder="Probation End Date">
                    </div>
                    <div class="col-md-4 position-relative">
    <label for="second_probation_end_date" class="form-label position-absolute text-muted"
           style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Second Probation End
        Date</label>
    <input type="date" name="second_probation_end_date" id="second_probation_end_date"
           class="form-control border-primary"
           value="{{ isset($employee) && optional($employee->employmentDetails)->second_probation_end_date ? \Carbon\Carbon::parse($employee->employmentDetails->second_probation_end_date)->format('Y-m-d') : '' }}"
           placeholder="Second Probation End Date">
</div>
                    <div class="col-md-4 position-relative">
                        <label for="contract_end_date" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Contract End
                            Date</label>
                        <input type="date" name="contract_end_date" id="contract_end_date"
                            class="form-control border-primary"
                            value="{{ isset($employee) && optional($employee->employmentDetails)->contract_end_date ? \Carbon\Carbon::parse($employee->employmentDetails->contract_end_date)->format('Y-m-d') : '' }}"
                            placeholder="Contract End Date">
                    </div>
                    <div class="col-md-4 position-relative">
                        <label for="retirement_date" class="form-label position-absolute text-muted"
                            style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">Retirement
                            Date</label>
                        <input type="date" name="retirement_date" id="retirement_date"
                            class="form-control border-primary"
                            value="{{ isset($employee) && optional($employee->employmentDetails)->retirement_date ? \Carbon\Carbon::parse($employee->employmentDetails->retirement_date)->format('Y-m-d') : '' }}"
                            placeholder="Retirement Date">
                    </div>
                    <!-- New License Fields -->
            <div class="col-md-4 position-relative">
                <label for="license_reg_number" class="form-label position-absolute text-muted"
                    style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">License Registration Number</label>
                <input type="text" name="license_reg_number" id="license_reg_number"
                    class="form-control border-primary"
                    value="{{ isset($employee) && optional($employee->employmentDetails)->license_reg_number ? $employee->employmentDetails->license_reg_number : '' }}"
                    placeholder="License Registration Number">
            </div>
            <div class="col-md-4 position-relative">
                <label for="license_expiry_date" class="form-label position-absolute text-muted"
                    style="top: -10px; left: 10px; background: #f0f4f9; padding: 0 5px;">License Expiry Date</label>
                <input type="date" name="license_expiry_date" id="license_expiry_date"
                    class="form-control border-primary"
                    value="{{ isset($employee) && optional($employee->employmentDetails)->license_expiry_date ? \Carbon\Carbon::parse($employee->employmentDetails->license_expiry_date)->format('Y-m-d') : '' }}"
                    placeholder="License Expiry Date">
                @if (isset($employee) && optional($employee->employmentDetails)->license_expiry_date && \Carbon\Carbon::parse($employee->employmentDetails->license_expiry_date)->diffInDays(\Carbon\Carbon::now()) <= 30)
                    <small class="text-danger">License nearing expiry (within 30 days).</small>
                @endif
            </div>

                    <div class="col-12">
                        <textarea name="job_description" id="job_description" class="form-control border-primary"
                            rows="3"
                            placeholder="Job Description">{{ isset($employee) ? optional($employee->employmentDetails)->job_description : '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Payroll Exemption -->
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-2">Payroll Exemption</h6>
                <div class="form-check">
    <input type="hidden" name="is_exempt_from_payroll" value="0">
    <input type="checkbox" name="is_exempt_from_payroll" id="is_exempt_from_payroll"
        class="form-check-input"
        {{ isset($employee) && $employee->is_exempt_from_payroll ? 'checked' : '' }} value="1">
    <label class="form-check-label" for="is_exempt_from_payroll">Exempt from Payroll</label>
</div>
            </div>
            <!-- PWD Tax Exemption -->
<div class="mb-3">
    <h6 class="text-muted fw-semibold mb-2">
        <i class="fa fa-wheelchair me-1"></i> Persons with Disabilities (PWD) Tax Exemption
    </h6>
    <div class="alert alert-info py-2 small mb-2">
        <i class="fa fa-info-circle"></i>
        Under the Persons with Disabilities Act 2003 &amp; 2010 Order, qualifying PWDs are exempt
        from PAYE on the first <strong>KES 150,000/month</strong>.
        Employee must hold a valid NCPWD membership certificate.
    </div>
    <div class="form-check mb-2">
        <input type="checkbox" name="has_disability_exemption" id="has_disability_exemption"
            class="form-check-input"
            value="1"
            {{ isset($employee) && optional($employee->payrollDetail)->has_disability_exemption ? 'checked' : '' }}
            onchange="togglePwdFields(this)">
        <label class="form-check-label fw-semibold" for="has_disability_exemption">
            This employee qualifies for PWD Tax Exemption
        </label>
    </div>

    <div id="pwd_fields" style="display: {{ isset($employee) && optional($employee->payrollDetail)->has_disability_exemption ? 'block' : 'none' }};">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="pwd_certificate_no" id="pwd_certificate_no"
                    class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->payrollDetail)->pwd_certificate_no ?? '') : '' }}"
                    placeholder="Disability Assessment Certificate No.">
                <small class="text-muted">From a government gazetted hospital</small>
            </div>
            <div class="col-md-4">
                <input type="text" name="pwd_ncpwd_membership_no" id="pwd_ncpwd_membership_no"
                    class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->payrollDetail)->pwd_ncpwd_membership_no ?? '') : '' }}"
                    placeholder="NCPWD Membership Certificate No.">
                <small class="text-muted">National Council for Persons with Disabilities</small>
            </div>
            <div class="col-md-4">
                <input type="number" name="pwd_exemption_limit" id="pwd_exemption_limit"
                    class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->payrollDetail)->pwd_exemption_limit ?? 150000) : 150000 }}"
                    placeholder="Monthly Exemption Limit (KES)"
                    step="0.01" min="0" max="150000">
                <small class="text-muted">Max KES 150,000/month per KRA</small>
            </div>
        </div>
    </div>
</div>
        </div>

<!-- Payment Tab -->
<div class="tab-pane fade" id="payment" role="tabpanel">
    <!-- Payment Type Selection -->
    <div class="mb-3">
        <h6 class="text-muted fw-semibold mb-2">Payment Type</h6>
        <div class="row g-2">
            <div class="col-md-12">
                <select name="payment_type" id="payment_type" class="form-select border-primary" required>
                    <option value="">Select Payment Type</option>
                    <option value="salary"
                        {{ isset($employee) && optional($employee->paymentDetails)->payment_type === 'salary' ? 'selected' : (!isset($employee) ? 'selected' : '') }}>
                        Monthly Salary (Fixed)
                    </option>
                    <option value="hourly"
                        {{ isset($employee) && optional($employee->paymentDetails)->payment_type === 'hourly' ? 'selected' : '' }}>
                        Hourly Rate
                    </option>
                </select>
                <small class="form-text text-muted">
                    Choose how this employee will be paid: fixed monthly salary or hourly rate based on attendance.
                </small>
            </div>
        </div>
    </div>

    <!-- Currency Selection (Shared for both payment types) -->
    <div class="mb-3">
        <h6 class="text-muted fw-semibold mb-2">Currency</h6>
        <div class="row g-2">
            <div class="col-md-6">
                <select name="currency" id="currency" class="form-select border-primary" required>
                    <option value="">Select Currency</option>
                    <option value="KES"
                        {{ isset($employee) && optional($employee->paymentDetails)->currency === 'KES' ? 'selected' : 'selected' }}>
                        KES - Kenyan Shilling</option>
                    <option value="USD"
                        {{ isset($employee) && optional($employee->paymentDetails)->currency === 'USD' ? 'selected' : '' }}>
                        USD - US Dollar</option>
                    <option value="UGX"
                        {{ isset($employee) && optional($employee->paymentDetails)->currency === 'UGX' ? 'selected' : '' }}>
                        UGX - Uganda Shilling</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Salary Group (for salary-based employees) -->
   <div class="mb-3" id="salary_group">
    <h6 class="text-muted fw-semibold mb-2" id="salary_group_title">Monthly Salary</h6>
    <div class="row g-2">
        <div class="col-md-12">
            <input type="number" name="basic_salary" id="basic_salary" class="form-control border-primary"
                value="{{ isset($employee) ? (optional($employee->paymentDetails)->basic_salary ?? '') : '' }}"
                placeholder="Basic Monthly Salary" step="0.01" min="0">
        </div>
    </div>
</div>

    <!-- Hourly Rate Group (for hourly employees) -->
   <div class="mb-3" id="hourly_rate_group" style="display:none;">
    <h6 class="text-muted fw-semibold mb-2" id="hourly_group_title">Hourly Rate</h6>
    <div class="row g-2">
        <div class="col-md-12">
            <input type="number" name="hourly_rate" id="hourly_rate" class="form-control border-primary"
                value="{{ isset($employee) ? (optional($employee->paymentDetails)->hourly_rate ?? '') : '' }}"
                placeholder="Hourly Rate" step="0.01" min="0">
            <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Regular hours will be paid at this rate.
                Overtime will be calculated at 1.5x this rate automatically.
            </small>
        </div>
    </div>

        <!-- Hourly Pay Info Card -->
        <div class="alert alert-info mt-3" role="alert">
            <h6 class="alert-heading"><i class="fa fa-clock"></i> How Hourly Pay Works</h6>
            <ul class="mb-0 small">
                <li>Hours are calculated from attendance records (clock in/out times)</li>
                <li>Regular hours: Paid at the hourly rate specified above</li>
                <li>Overtime hours: Automatically paid at 1.5x the hourly rate</li>
                <li>Gross pay = (Regular Hours × Hourly Rate) + (Overtime Hours × 1.5 × Hourly Rate)</li>
                <li>Ensure attendance is tracked daily for accurate payroll</li>
            </ul>
        </div>
    </div>

    <!-- Bank Details Group -->
    <div class="mb-3">
        <h6 class="text-muted fw-semibold mb-2">Bank Details</h6>
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" name="account_name" id="account_name" class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->paymentDetails)->account_name ?? '') : '' }}"
                    placeholder="Account Name" required>
            </div>
            <div class="col-md-6">
                <input type="text" name="account_number" id="account_number" class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->paymentDetails)->account_number ?? '') : '' }}"
                    placeholder="Account Number" required>
            </div>
            <div class="col-md-6">
                <input type="text" name="bank_name" id="bank_name" class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->paymentDetails)->bank_name ?? '') : '' }}"
                    placeholder="Bank Name" required>
            </div>
            <div class="col-md-6">
                <select name="payment_mode" id="payment_mode" class="form-select border-primary" required>
                    <option value="">Select Payment Mode</option>
                    <option value="bank"
                        {{ isset($employee) && optional($employee->paymentDetails)->payment_mode === 'bank' ? 'selected' : '' }}>
                        Bank</option>
                    <option value="mpesa"
                        {{ isset($employee) && optional($employee->paymentDetails)->payment_mode === 'mpesa' ? 'selected' : '' }}>
                        M-Pesa</option>
                    <option value="cash"
                        {{ isset($employee) && optional($employee->paymentDetails)->payment_mode === 'cash' ? 'selected' : '' }}>
                        Cash</option>
                    <option value="cheque"
                        {{ isset($employee) && optional($employee->paymentDetails)->payment_mode === 'cheque' ? 'selected' : '' }}>
                        Cheque</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" name="bank_code" id="bank_code" class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->paymentDetails)->bank_code ?? '') : '' }}"
                    placeholder="Bank Code">
            </div>
            <div class="col-md-6">
                <input type="text" name="bank_branch" id="bank_branch" class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->paymentDetails)->bank_branch ?? '') : '' }}"
                    placeholder="Bank Branch">
            </div>
            <div class="col-md-6">
                <input type="text" name="bank_branch_code" id="bank_branch_code"
                    class="form-control border-primary"
                    value="{{ isset($employee) ? (optional($employee->paymentDetails)->bank_branch_code ?? '') : '' }}"
                    placeholder="Bank Branch Code">
            </div>
        </div>
    </div>
    <!-- WHT SECTION — only visible for consultants -->
<!-- WHT SECTION — only visible for consultants/locums -->
<div class="mb-3 border rounded p-3 bg-light"
     id="wht_section"
     style="display: {{ isset($employee) && in_array(optional($employee->employmentDetails)->employment_term, ['consultant', 'locum']) ? 'block' : 'none' }};">

    <h6 class="fw-semibold mb-2">
        <i class="fa fa-percent me-1 text-warning"></i>
        Withholding Tax Settings
    </h6>

    <div class="alert alert-warning py-2 small">
        <i class="fa fa-info-circle"></i>
        WHT replaces PAYE for consultants. The company deducts it
        from the gross fee before paying the consultant,
        then remits it to KRA by the 20th of the following month.
    </div>

    <div class="row g-2">

        {{-- WHT Category --}}
        <div class="col-md-4">
            <label class="form-label small fw-semibold">WHT Category</label>
            <select name="wht_payment_type" id="wht_payment_type"
                class="form-select border-primary">
                <option value="professional_fees"
                    {{ isset($employee) && optional($employee->paymentDetails)->wht_payment_type === 'professional_fees' ? 'selected' : '' }}>
                    Professional / Consultancy Fees (5%)
                </option>
                <option value="training_fees"
                    {{ isset($employee) && optional($employee->paymentDetails)->wht_payment_type === 'training_fees' ? 'selected' : '' }}>
                    Training Fees (5%)
                </option>
                <option value="contractual"
                    {{ isset($employee) && optional($employee->paymentDetails)->wht_payment_type === 'contractual' ? 'selected' : '' }}>
                    Contractual Payments (3%)
                </option>
                <option value="commissions"
                    {{ isset($employee) && optional($employee->paymentDetails)->wht_payment_type === 'commissions' ? 'selected' : '' }}>
                    Commissions (5%)
                </option>
            </select>
        </div>

        {{-- Residency Status --}}
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Residency Status</label>
            <select name="wht_residency" id="wht_residency"
                class="form-select border-primary">
                <option value="resident"
                    {{ isset($employee) && optional($employee->paymentDetails)->wht_residency !== 'non_resident' ? 'selected' : '' }}>
                    Resident (5% / 3%)
                </option>
                <option value="non_resident"
                    {{ isset($employee) && optional($employee->paymentDetails)->wht_residency === 'non_resident' ? 'selected' : '' }}>
                    Non-Resident (20%)
                </option>
            </select>
        </div>

        {{-- Payee KRA PIN --}}
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Payee KRA PIN</label>
            <input type="text" name="wht_pin" id="wht_pin"
                class="form-control border-primary"
                value="{{ isset($employee) ? (optional($employee->paymentDetails)->wht_pin ?? '') : '' }}"
                placeholder="A0123456789B">
            <small class="text-muted">Required for WHT certificate on iTax</small>
        </div>

        {{-- SHIF toggle --}}
        <div class="col-md-6">
            <div class="card border p-2">
                <div class="form-check">
                    <input type="checkbox"
                        name="consultant_shif_covered"
                        id="consultant_shif_covered"
                        class="form-check-input"
                        value="1"
                        {{ isset($employee) && optional($employee->paymentDetails)->consultant_shif_covered ? 'checked' : '' }}
                        onchange="toggleShifDetail(this)">
                    <label class="form-check-label fw-semibold" for="consultant_shif_covered">
                        Deduct SHIF from consultant pay
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    Company remits SHIF on consultant's behalf (deducted from their fee)
                </small>

                <div id="shif_detail"
                    style="display: {{ isset($employee) && optional($employee->paymentDetails)->consultant_shif_covered ? 'block' : 'none' }};"
                    class="mt-2">
                    <label class="form-label small">Calculation Basis</label>
                    <select name="consultant_shif_basis" id="consultant_shif_basis"
                        class="form-select form-select-sm border-primary"
                        onchange="toggleShifAmount(this)">
                        <option value="statutory"
                            {{ isset($employee) && optional($employee->paymentDetails)->consultant_shif_basis !== 'fixed' ? 'selected' : '' }}>
                            Statutory (2.75% of gross, min KES 300)
                        </option>
                        <option value="fixed"
                            {{ isset($employee) && optional($employee->paymentDetails)->consultant_shif_basis === 'fixed' ? 'selected' : '' }}>
                            Fixed Amount
                        </option>
                    </select>
                    <input type="number"
                        name="consultant_shif_fixed_amount"
                        id="consultant_shif_fixed_amount"
                        class="form-control form-control-sm border-primary mt-1"
                        value="{{ isset($employee) ? (optional($employee->paymentDetails)->consultant_shif_fixed_amount ?? '') : '' }}"
                        placeholder="Fixed SHIF amount (KES)"
                        step="0.01" min="0"
                        style="display: {{ isset($employee) && optional($employee->paymentDetails)->consultant_shif_basis === 'fixed' ? 'block' : 'none' }};">
                </div>
            </div>
        </div>

        {{-- NSSF toggle --}}
        <div class="col-md-6">
            <div class="card border p-2">
                <div class="form-check">
                    <input type="checkbox"
                        name="consultant_nssf_covered"
                        id="consultant_nssf_covered"
                        class="form-check-input"
                        value="1"
                        {{ isset($employee) && optional($employee->paymentDetails)->consultant_nssf_covered ? 'checked' : '' }}
                        onchange="toggleNssfDetail(this)">
                    <label class="form-check-label fw-semibold" for="consultant_nssf_covered">
                        Deduct NSSF from consultant pay
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    Company remits NSSF on consultant's behalf (deducted from their fee)
                </small>

                <div id="nssf_detail"
                    style="display: {{ isset($employee) && optional($employee->paymentDetails)->consultant_nssf_covered ? 'block' : 'none' }};"
                    class="mt-2">
                    <label class="form-label small">Calculation Basis</label>
                    <select name="consultant_nssf_basis" id="consultant_nssf_basis"
                        class="form-select form-select-sm border-primary"
                        onchange="toggleNssfAmount(this)">
                        <option value="statutory"
                            {{ isset($employee) && optional($employee->paymentDetails)->consultant_nssf_basis !== 'fixed' ? 'selected' : '' }}>
                            Statutory Rate (Year 4 — max KES 6,480)
                        </option>
                        <option value="fixed"
                            {{ isset($employee) && optional($employee->paymentDetails)->consultant_nssf_basis === 'fixed' ? 'selected' : '' }}>
                            Fixed Amount
                        </option>
                    </select>
                    <input type="number"
                        name="consultant_nssf_fixed_amount"
                        id="consultant_nssf_fixed_amount"
                        class="form-control form-control-sm border-primary mt-1"
                        value="{{ isset($employee) ? (optional($employee->paymentDetails)->consultant_nssf_fixed_amount ?? '') : '' }}"
                        placeholder="Fixed NSSF amount (KES)"
                        step="0.01" min="0"
                        style="display: {{ isset($employee) && optional($employee->paymentDetails)->consultant_nssf_basis === 'fixed' ? 'block' : 'none' }};">
                </div>
            </div>
        </div>

        {{-- Summary box showing what will be deducted --}}
        @if(isset($employee) && optional($employee->paymentDetails)->is_consultant)
        <div class="col-12 mt-2">
            <div class="alert alert-secondary py-2 small mb-0">
                {{-- <strong><i class="fa fa-calculator me-1"></i> Deduction Summary</strong> --}}
                <div class="row mt-1">
                    @php
                        $grossFee   = floatval(optional($employee->paymentDetails)->basic_salary ?? 0);
                        $whtType    = optional($employee->paymentDetails)->wht_payment_type ?? 'professional_fees';
                        $whtRes     = optional($employee->paymentDetails)->wht_residency ?? 'resident';
                        $rateMap    = [
                            'professional_fees' => ['resident' => 5,  'non_resident' => 20],
                            'training_fees'     => ['resident' => 5,  'non_resident' => 20],
                            'contractual'       => ['resident' => 3,  'non_resident' => 20],
                            'commissions'       => ['resident' => 5,  'non_resident' => 20],
                        ];
                        $whtRate    = $rateMap[$whtType][$whtRes] ?? 5;
                        $whtAmt     = round($grossFee * $whtRate / 100, 2);
                        $shifAmt    = optional($employee->paymentDetails)->consultant_shif_covered
                            ? (optional($employee->paymentDetails)->consultant_shif_basis === 'fixed'
                                ? floatval(optional($employee->paymentDetails)->consultant_shif_fixed_amount ?? 0)
                                : max(300, round($grossFee * 0.0275, 2)))
                            : 0;
                        $lel = 9000; $uel = 108000;
                        $nssfAmt = 0;
                        if (optional($employee->paymentDetails)->consultant_nssf_covered) {
                            if (optional($employee->paymentDetails)->consultant_nssf_basis === 'fixed') {
                                $nssfAmt = floatval(optional($employee->paymentDetails)->consultant_nssf_fixed_amount ?? 0);
                            } else {
                                if ($grossFee <= $lel) {
                                    $nssfAmt = round($grossFee * 0.06, 2);
                                } else {
                                    $t1 = round($lel * 0.06, 2);
                                    $t2 = round(min($grossFee - $lel, $uel - $lel) * 0.06, 2);
                                    $nssfAmt = $t1 + $t2;
                                }
                            }
                        }
                        $netPay = $grossFee - $whtAmt - $shifAmt - $nssfAmt;
                    @endphp
                    {{-- <div class="col-6 col-md-3">Gross Fee: <strong>KES {{ number_format($grossFee, 2) }}</strong></div>
                    <div class="col-6 col-md-3 text-danger">WHT ({{ $whtRate }}%): -KES {{ number_format($whtAmt, 2) }}</div>
                    <div class="col-6 col-md-3 text-danger">SHIF: -KES {{ number_format($shifAmt, 2) }}</div>
                    <div class="col-6 col-md-3 text-danger">NSSF: -KES {{ number_format($nssfAmt, 2) }}</div>
                    <div class="col-12 mt-1 text-success fw-semibold">
                        Net to Consultant: KES {{ number_format($netPay, 2) }}
                    </div> --}}
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
</div>

        <!-- Documents Tab -->
        <div class="tab-pane fade" id="documents" role="tabpanel">
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-3">Upload Documents (Optional)</h6>
                <div id="documentEntries">
                    <div class="document-entry card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <input type="text" name="document_types[]" class="form-control"
                                        placeholder="Document Type (e.g., ID, Certificate)">
                                </div>
                                <div class="col-md-5">
                                    <input type="file" name="documents[]" class="form-control document-input"
                                        accept=".pdf,.doc,.docx,.jpg,.png">
                                </div>
                                <div class="col-md-3 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-document">
                                        <i class="fa fa-trash"></i> Remove
                                    </button>
                                </div>
                                <div class="col-md-12 mt-2 document-preview" style="display: none;">
                                    <div
                                        class="preview-container d-flex align-items-center p-2 border rounded bg-light">
                                        <span class="file-name me-2"></span>
                                        <a href="#" class="view-file btn btn-sm btn-primary me-2"
                                            target="_blank">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addDocument">
                    <i class="fa fa-plus me-1"></i> Add Another Document
                </button>
            </div>

            @if(isset($employee) && $employee->documents->isNotEmpty())
            <div class="mb-3">
                <h6 class="text-muted fw-semibold mb-3">Existing Documents</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Uploaded On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employee->documents as $document)
                            <tr data-document-id="{{ $document->id }}">
                                <td>{{ $document->document_type ?? 'N/A' }}</td>
                                <td>{{ $document->created_at ? date('d M Y', strtotime($document->created_at)) : 'N/A' }}
                                </td>
                                <td>
                                    <a href="{{ route('employees.documents.download', [$employee->id, $document->id]) }}"
                                        class="btn btn-sm btn-primary view-document me-1">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete-document"
                                        data-employee-id="{{ $employee->id }}" data-document-id="{{ $document->id }}">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-3 text-end">
        <button type="button" id="submitButton" class="btn btn-primary btn-modern px-4 py-2"
            onclick="saveEmployee(this)">
            <i class="fa fa-save me-2"></i> {{ isset($employee) ? 'Update' : 'Create' }} Employee
        </button>
    </div>
</form>

<style>
    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        transition: border-color 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #007bff;
        box-shadow: none;
    }

    .nav-pills .nav-link {
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
    }

    .nav-pills .nav-link.active {
        background-color: transparent;
        color: #007bff;
        border-bottom: 2px solid #007bff;
    }

    .btn-modern {
        border-radius: 20px;
        font-weight: 500;
        transition: background-color 0.2s ease;
    }

    .btn-modern:hover {
        background-color: #0056b3;
    }

    .bg-light {
        background-color: #f8f9fa;
    }

    .document-entry {
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .document-entry:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .preview-container {
        border-radius: 6px;
    }

    .document-preview img,
    .document-preview iframe {
        max-width: 100%;
        max-height: 200px;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }


</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("employeeForm");
        const submitButton = document.querySelector("#submitButton");

        // Form validation for employee details
        function validateForm() {
            let isValid = true;
            const requiredFields = form.querySelectorAll("[required]:not([name^='document_'])");

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add("is-invalid");
                    isValid = false;
                } else {
                    field.classList.remove("is-invalid");
                    field.classList.add("is-valid");
                }
            });

            submitButton.disabled = !isValid;
        }

        form.addEventListener("input", validateForm);
        form.addEventListener("submit", function(event) {
            validateForm();
            if (submitButton.disabled) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        validateForm();

        // Initialize tabs
        const tabs = new bootstrap.Tab(document.querySelector('#personal-tab'));
        tabs.show();

        // Handle tab switching to clear previous content
        document.querySelectorAll('#employeeTabs .nav-link').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                const target = document.querySelector(e.target.getAttribute('href'));
                if (target) {
                    target.classList.add('show', 'active');
                }
            });
        });
    });

    // Show/hide WHT section based on employment term
document.getElementById('employment_term')
    .addEventListener('change', function() {
        const isConsultant = ['consultant', 'locum'].includes(this.value);
        document.getElementById('wht_section').style.display =
            isConsultant ? 'block' : 'none';

        // Also hide PAYE-related fields if consultant
        // (optional — depends on your form layout)
    });

function toggleShifDetail(cb) {
    document.getElementById('shif_detail').style.display =
        cb.checked ? 'block' : 'none';
}
function toggleShifAmount(select) {
    document.getElementById('consultant_shif_fixed_amount').style.display =
        select.value === 'fixed' ? 'block' : 'none';
}
function toggleNssfDetail(cb) {
    document.getElementById('nssf_detail').style.display =
        cb.checked ? 'block' : 'none';
}
function toggleNssfAmount(select) {
    document.getElementById('consultant_nssf_fixed_amount').style.display =
        select.value === 'fixed' ? 'block' : 'none';
}

// Run on page load for edit mode
document.addEventListener('DOMContentLoaded', function() {
    const term = document.getElementById('employment_term').value;
    if (['consultant', 'locum'].includes(term)) {
        document.getElementById('wht_section').style.display = 'block';
    }
});

    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById('profile_preview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    }
</script>


