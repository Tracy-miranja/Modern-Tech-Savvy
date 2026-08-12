<div class="container mt-5">
    <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-4">
            <!-- Loading Spinner -->
            <div id="viewLoading" style="display: none;" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="personal-tab" data-bs-toggle="tab" href="#personal" role="tab"
                        aria-controls="personal" aria-selected="true">Personal</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="payment-tab" data-bs-toggle="tab" href="#payment" role="tab"
                        aria-controls="payment" aria-selected="false">Payment</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="employment-tab" data-bs-toggle="tab" href="#employment" role="tab"
                        aria-controls="employment" aria-selected="false">Employment</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="additional-tab" data-bs-toggle="tab" href="#additional" role="tab"
                        aria-controls="additional" aria-selected="false">Additional</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#documents" role="tab"
                        aria-controls="documents" aria-selected="false">Documents</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="actions-tab" data-bs-toggle="tab" href="#actions" role="tab"
                        aria-controls="actions" aria-selected="false">Actions</a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="employeeTabContent">
                <!-- Personal Details Tab -->
                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <div class="row align-items-center mb-4">
                        <div class="col-auto">
                            <img src="{{ $employee->getFirstMediaUrl('avatars') ?: 'https://via.placeholder.com/80' }}"
                                class="rounded-circle border object-fit-cover" style="width: 80px; height: 80px;"
                                alt="Profile">
                        </div>
                        <div class="col">
                            <h5 class="fw-semibold mb-1">{{ $employee->user->name }}</h5>
                            <p class="text-muted mb-0">{{ $employee->user->email ?? 'No Email' }}</p>
                            <span class="badge bg-success-subtle text-success fw-normal mt-1">
                                {{ optional($employee->employmentDetails)->jobCategory?->name ?? 'Not Assigned' }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Basic Info -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Basic Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Employee Code</dt>
                                <dd class="col-7">{{ $employee->employee_code ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Gender</dt>
                                <dd class="col-7">{{ ucfirst($employee->gender) ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Phone</dt>
                                <dd class="col-7">
                                    {{ !empty($employee->user->phone) ? $employee->user->phone : ($employee->alternate_phone ?? 'N/A') }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Marital Status</dt>
                                <dd class="col-7">{{ ucfirst($employee->marital_status) ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Place of Birth</dt>
                                <dd class="col-7">{{ $employee->place_of_birth ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Blood Group</dt>
                                <dd class="col-7">{{ $employee->blood_group ?? 'N/A' }}</dd>
                            </dl>
                        </div>

                        <!-- Identification -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Identification</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">National ID</dt>
                                <dd class="col-7">{{ $employee->national_id ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Tax No</dt>
                                <dd class="col-7">{{ $employee->tax_no ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">NHIF No</dt>
                                <dd class="col-7">{{ $employee->nhif_no ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">NSSF No</dt>
                                <dd class="col-7">{{ $employee->nssf_no ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Resident Status</dt>
                                <dd class="col-7">{{ $employee->resident_status ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">KRA Employee Status</dt>
                                <dd class="col-7">{{ $employee->kra_employee_status ?? 'N/A' }}</dd>
                            </dl>
                        </div>

                        <!-- Passport & Birth -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Passport & Birth</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Passport No</dt>
                                <dd class="col-7">{{ $employee->passport_no ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Place of Issue</dt>
                                <dd class="col-7">{{ $employee->place_of_issue ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Issue Date</dt>
                                <dd class="col-7">
                                    {{ $employee->passport_issue_date ? date('d M Y', strtotime($employee->passport_issue_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Expiry Date</dt>
                                <dd class="col-7">
                                    {{ $employee->passport_expiry_date ? date('d M Y', strtotime($employee->passport_expiry_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Date of Birth</dt>
                                <dd class="col-7">
                                    {{ $employee->date_of_birth ? date('d M Y', strtotime($employee->date_of_birth)) : 'N/A' }}
                                </dd>
                            </dl>
                        </div>

                        <!-- Address -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Address</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Current Address</dt>
                                <dd class="col-7">{{ $employee->address ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Permanent Address</dt>
                                <dd class="col-7">{{ $employee->permanent_address ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Payment Details Tab -->
                <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                    <div class="row g-4">
                        <!-- Salary Details -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Salary Details</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Basic Salary</dt>
                                <dd class="col-7">
                                    {{ number_format((float) ($employee->paymentDetails->basic_salary ?? 0), 2) }}
                                    {{ $employee->paymentDetails->currency ?? '' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Payment Mode</dt>
                                <dd class="col-7">{{ strtoupper($employee->paymentDetails->payment_mode ?? 'N/A') }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Exempt from Payroll</dt>
                                <dd class="col-7">{{ $employee->is_exempt_from_payroll ? 'Yes' : 'No' }}</dd>
                            </dl>
                        </div>

                        <!-- Bank Details -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Bank Details</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Account Name</dt>
                                <dd class="col-7">{{ $employee->paymentDetails->account_name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Account Number</dt>
                                <dd class="col-7">{{ $employee->paymentDetails->account_number ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Bank Name</dt>
                                <dd class="col-7">{{ $employee->paymentDetails->bank_name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Bank Code</dt>
                                <dd class="col-7">{{ $employee->paymentDetails->bank_code ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Branch</dt>
                                <dd class="col-7">{{ $employee->paymentDetails->bank_branch ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Branch Code</dt>
                                <dd class="col-7">{{ $employee->paymentDetails->bank_branch_code ?? 'N/A' }}</dd>
                            </dl>
                        </div>

                        <!-- Recent Payroll Snapshot -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Recent Payroll</h6>
                            @if($employee->payrolls->isNotEmpty())
                            @php $latestPayroll = $employee->payrolls->sortByDesc('created_at')->first(); @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Gross Pay</dt>
                                <dd class="col-7">{{ number_format((float) ($latestPayroll->gross_pay ?? 0), 2) }}</dd>
                                <dt class="col-5 fw-medium text-muted">Net Pay</dt>
                                <dd class="col-7">{{ number_format((float) ($latestPayroll->net_pay ?? 0), 2) }}</dd>
                                <dt class="col-5 fw-medium text-muted">Deductions</dt>
                                <dd class="col-7">
                                    {{ number_format((float) ($latestPayroll->deductions_after_tax ?? 0), 2) }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Date</dt>
                                <dd class="col-7">
                                    {{ $latestPayroll->created_at ? date('d M Y', strtotime($latestPayroll->created_at)) : 'N/A' }}
                                </dd>
                            </dl>
                            @else
                            <p class="text-muted">No payroll records available.</p>
                            @endif
                        </div>

                        <!-- Advances -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Advances</h6>
                            @if($employee->advances->isNotEmpty())
                            @php $latestAdvance = $employee->advances->sortByDesc('created_at')->first(); @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Amount</dt>
                                <dd class="col-7">{{ number_format((float) ($latestAdvance->amount ?? 0), 2) }}</dd>
                                <dt class="col-5 fw-medium text-muted">Date</dt>
                                <dd class="col-7">
                                    {{ $latestAdvance->date ? date('d M Y', strtotime($latestAdvance->date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Note</dt>
                                <dd class="col-7">{{ $latestAdvance->note ?? 'N/A' }}</dd>
                            </dl>
                            @else
                            <p class="text-muted">No advances recorded.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Employment Details Tab -->
                <div class="tab-pane fade" id="employment" role="tabpanel" aria-labelledby="employment-tab">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Employment Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Employee Since</dt>
                                <dd class="col-7">
                                    {{ optional($employee->employmentDetails)->employment_date ? date('d M Y', strtotime($employee->employmentDetails->employment_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Department</dt>
                                <dd class="col-7">{{ $employee->department?->name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Job Category</dt>
                                <dd class="col-7">
                                    {{ optional($employee->employmentDetails)->jobCategory?->name ?? 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Contract Type</dt>
                                <dd class="col-7">
                                    {{ ucfirst(optional($employee->employmentDetails)->employment_term ?? 'N/A') }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Probation End</dt>
                                <dd class="col-7">
                                    {{ optional($employee->employmentDetails)->probation_end_date ? date('d M Y', strtotime($employee->employmentDetails->probation_end_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">License_reg_number</dt>
                                 <dd class="col-7">
                                    {{ ucfirst(optional($employee->employmentDetails)->license_reg_number ?? 'N/A') }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">License_expiry_date</dt>
                                <dd class="col-7">
                                    {{ optional($employee->employmentDetails)->license_expiry_date ? date('d M Y', strtotime($employee->employmentDetails->license_expiry_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Contract End</dt>
                                <dd class="col-7">
                                    {{ optional($employee->employmentDetails)->contract_end_date ? date('d M Y', strtotime($employee->employmentDetails->contract_end_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Retirement Date</dt>
                                <dd class="col-7">
                                    {{ optional($employee->employmentDetails)->retirement_date ? date('d M Y', strtotime($employee->employmentDetails->retirement_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Business</dt>
                                <dd class="col-7">{{ $employee->business?->company_name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Location</dt>
                                <dd class="col-7">{{ $employee->location?->name ?? 'N/A' }}</dd>
                            </dl>
                        </div>

                        <!-- Job Description -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Job Description</h6>
                            <p class="text-muted">
                                {{ optional($employee->employmentDetails)->job_description ?? 'No description provided.' }}
                            </p>
                        </div>

                        <!-- Attendance Snapshot -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Recent Attendance</h6>
                            @if($employee->attendances->isNotEmpty())
                            @php $latestAttendance = $employee->attendances->sortByDesc('date')->first(); @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Date</dt>
                                <dd class="col-7">
                                    {{ $latestAttendance->date ? date('d M Y', strtotime($latestAttendance->date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Clock In</dt>
                                <dd class="col-7">{{ $latestAttendance->clock_in ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Clock Out</dt>
                                <dd class="col-7">{{ $latestAttendance->clock_out ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Status</dt>
                                <dd class="col-7">{{ $latestAttendance->is_absent ? 'Absent' : 'Present' }}</dd>
                            </dl>
                            @else
                            <p class="text-muted">No attendance records available.</p>
                            @endif
                        </div>

                        <!-- Leave Snapshot -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Recent Leave</h6>
                            @if($employee->leaveRequests->isNotEmpty())
                            @php $latestLeave = $employee->leaveRequests->sortByDesc('created_at')->first(); @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Reference</dt>
                                <dd class="col-7">{{ $latestLeave->reference_number ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Start Date</dt>
                                <dd class="col-7">
                                    {{ $latestLeave->start_date ? date('d M Y', strtotime($latestLeave->start_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">End Date</dt>
                                <dd class="col-7">
                                    {{ $latestLeave->end_date ? date('d M Y', strtotime($latestLeave->end_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Total Days</dt>
                                <dd class="col-7">{{ $latestLeave->total_days ?? 'N/A' }}</dd>
                            </dl>
                            @else
                            <p class="text-muted">No leave requests available.</p>
                            @endif
                        </div>

                        <!-- Overtime Snapshot -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Recent Overtime</h6>
                            @if($employee->overtimes->isNotEmpty())
                            @php $latestOvertime = $employee->overtimes->sortByDesc('created_at')->first(); @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Date</dt>
                                <dd class="col-7">
                                    {{ $latestOvertime->date ? date('d M Y', strtotime($latestOvertime->date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">Hours</dt>
                                <dd class="col-7">{{ number_format((float) ($latestOvertime->hours ?? 0), 2) }}</dd>
                                <dt class="col-5 fw-medium text-muted">Rate</dt>
                                <dd class="col-7">{{ number_format((float) ($latestOvertime->rate ?? 0), 2) }}</dd>
                            </dl>
                            @else
                            <p class="text-muted">No overtime records available.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Additional Details Tab -->
                <div class="tab-pane fade" id="additional" role="tabpanel" aria-labelledby="additional-tab">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Academic Qualifications</h6>
                            @if($employee->academicDetails->isNotEmpty())
                            @php $latestQualification = $employee->academicDetails->sortByDesc('end_date')->first();
                            @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Institution</dt>
                                <dd class="col-7">{{ $latestQualification->institution_name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Certification</dt>
                                <dd class="col-7">{{ $latestQualification->certification_obtained ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Start Date</dt>
                                <dd class="col-7">
                                    {{ $latestQualification->start_date ? date('d M Y', strtotime($latestQualification->start_date)) : 'N/A' }}
                                </dd>
                                <dt class="col-5 fw-medium text-muted">End Date</dt>
                                <dd class="col-7">
                                    {{ $latestQualification->end_date ? date('d M Y', strtotime($latestQualification->end_date)) : 'N/A' }}
                                </dd>
                            </dl>
                            @else
                            <p class="text-muted">No academic qualifications recorded.</p>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Allowances</h6>
                            @if($employee->employeeAllowances->isNotEmpty())
                            @php $latestAllowance = $employee->employeeAllowances->sortByDesc('created_at')->first();
                            @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Name</dt>
                                <dd class="col-7">{{ $latestAllowance->allowance?->name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Amount</dt>
                                <dd class="col-7">{{ number_format((float) ($latestAllowance->amount ?? 0), 2) }}</dd>
                                <dt class="col-5 fw-medium text-muted">Taxable</dt>
                                <dd class="col-7">{{ $latestAllowance->allowance?->is_taxable ? 'Yes' : 'No' }}</dd>
                            </dl>
                            @else
                            <p class="text-muted">No allowances recorded.</p>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Deductions</h6>
                            @if($employee->employeeDeductions->isNotEmpty())
                            @php $latestDeduction = $employee->employeeDeductions->sortByDesc('created_at')->first();
                            @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Name</dt>
                                <dd class="col-7">{{ $latestDeduction->deduction?->name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Amount</dt>
                                <dd class="col-7">{{ number_format((float) ($latestDeduction->amount ?? 0), 2) }}</dd>
                                <dt class="col-5 fw-medium text-muted">Date</dt>
                                <dd class="col-7">
                                    {{ $latestDeduction->created_at ? date('d M Y', strtotime($latestDeduction->created_at)) : 'N/A' }}
                                </dd>
                            </dl>
                            @else
                            <p class="text-muted">No deductions recorded.</p>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Family Members</h6>
                            @if($employee->familyMembers->isNotEmpty())
                            @php $latestFamily = $employee->familyMembers->sortByDesc('created_at')->first(); @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Name</dt>
                                <dd class="col-7">{{ $latestFamily->name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Relationship</dt>
                                <dd class="col-7">{{ ucfirst($latestFamily->relationship ?? 'N/A') }}</dd>
                                <dt class="col-5 fw-medium text-muted">Date of Birth</dt>
                                <dd class="col-7">
                                    {{ $latestFamily->date_of_birth ? date('d M Y', strtotime($latestFamily->date_of_birth)) : 'N/A' }}
                                </dd>
                            </dl>
                            @else
                            <p class="text-muted">No family members recorded.</p>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted mb-3">Emergency Contact</h6>
                            @if($employee->emergencyContacts->isNotEmpty())
                            @php $latestContact = $employee->emergencyContacts->sortByDesc('created_at')->first();
                            @endphp
                            <dl class="row mb-0">
                                <dt class="col-5 fw-medium text-muted">Name</dt>
                                <dd class="col-7">{{ $latestContact->name ?? 'N/A' }}</dd>
                                <dt class="col-5 fw-medium text-muted">Relationship</dt>
                                <dd class="col-7">{{ ucfirst($latestContact->relationship ?? 'N/A') }}</dd>
                                <dt class="col-5 fw-medium text-muted">Phone</dt>
                                <dd class="col-7">{{ $latestContact->phone ?? 'N/A' }}</dd>
                            </dl>
                            @else
                            <p class="text-muted">No emergency contacts recorded.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <h6 class="fw-semibold text-muted mb-3">Documents</h6>
                            @if($employee->documents->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Document Type</th>
                                            <th scope="col">Uploaded On</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employee->documents as $document)
                                        <tr>
                                            <td>{{ $document->document_type ?? 'N/A' }}</td>
                                            <td>
                                                {{ $document->created_at ? date('d M Y', strtotime($document->created_at)) : 'N/A' }}
                                            </td>
                                            <td>
                                                <a href="{{ route('employees.documents.download', [$employee->id, $document->id]) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fa fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted">No documents recorded.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions Tab -->
                <div class="tab-pane fade" id="actions" role="tabpanel" aria-labelledby="actions-tab">
                    @php $employmentStatus = optional($employee->employmentDetails)->status; @endphp
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-warning btn-sm flex-grow-1 flex-md-grow-0"
                                onclick="openWarnEmployeeModal({{ $employee->id }})">Warn Employee</button>
                        <a href="{{ route('business.leave.create', $currentBusiness->slug) }}"
                           class="btn btn-info btn-sm flex-grow-1 flex-md-grow-0">Request Leave</a>

                        @php $activeContractAction = $employee->contractActions->first(); @endphp
                        @if ($employmentStatus === 'suspended' && $activeContractAction)
                            <button type="button" class="btn btn-success btn-sm flex-grow-1 flex-md-grow-0"
                                    onclick="openReinstateModal({{ $activeContractAction->id }}, {{ $employee->id }})">Reinstate (Lift Suspension)</button>
                        @elseif ($employmentStatus === 'terminated' && $activeContractAction)
                            <button type="button" class="btn btn-success btn-sm flex-grow-1 flex-md-grow-0"
                                    onclick="openReinstateModal({{ $activeContractAction->id }}, {{ $employee->id }})">Reinstate (Reverse Termination)</button>
                        @else
                            <button type="button" class="btn btn-danger btn-sm flex-grow-1 flex-md-grow-0"
                                    onclick="openContractActionModal({{ $employee->id }}, 'suspension')">Suspend</button>
                            <button type="button" class="btn btn-dark btn-sm flex-grow-1 flex-md-grow-0"
                                    onclick="openContractActionModal({{ $employee->id }}, 'termination')">Terminate</button>
                        @endif
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        "Delete", "Login as Employee", and "Send Welcome Email" aren't wired up yet.
                    </p>
                    @if ($employmentStatus === 'suspended')
                        <p class="text-danger small mt-2 mb-0">This employee is currently suspended.</p>
                    @elseif ($employmentStatus === 'terminated')
                        <p class="text-danger small mt-2 mb-0">This employee is terminated.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suspend / Terminate Modal -->
<div class="modal fade" id="contractActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contractActionModalTitle">Suspend Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="contractActionForm">
                    <input type="hidden" name="employee_id">
                    <input type="hidden" name="action_type">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Effective Date</label>
                        <input type="date" name="action_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="submitContractActionBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Reinstate Modal -->
<div class="modal fade" id="reinstateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reinstate Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reinstateForm">
                    <input type="hidden" name="employee_id">
                    <input type="hidden" name="contract_action_id">
                    <input type="hidden" name="status" value="reversed">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Effective Date</label>
                        <input type="date" name="action_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitReinstateBtn">Reinstate</button>
            </div>
        </div>
    </div>
</div>

<!-- Warn Employee Modal -->
<div class="modal fade" id="warnEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Disciplinary Case</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="warnEmployeeForm">
                    <input type="hidden" name="employee_id">
                    <div class="mb-3">
                        <label class="form-label">Case Type</label>
                        <select name="case_type" class="form-select">
                            <option value="verbal_warning">Verbal Warning</option>
                            <option value="written_warning" selected>Written Warning</option>
                            <option value="final_warning">Final Warning</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (optional)</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="submitWarnEmployeeBtn">Issue</button>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
        border-radius: 12px;
    }

    .nav-tabs {
        border-bottom: 2px solid #e9ecef;
    }

    .nav-tabs .nav-link {
        color: #495057;
        padding: 0.75rem 1.5rem;
        border-radius: 8px 8px 0 0;
        transition: all 0.2s ease;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #fff;
        border-color: #e9ecef #e9ecef #fff;
        font-weight: 600;
    }

    .nav-tabs .nav-link:hover {
        color: #0d6efd;
    }

    .btn-sm {
        min-width: 130px;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .tab-pane {
        padding: 1rem;
    }

    h6.text-muted {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 0.5rem;
    }

    dl dt {
        font-size: 0.9rem;
    }

    dl dd {
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    @media (max-width: 767.98px) {
        .nav-tabs .nav-link {
            padding: 0.5rem 1rem;
        }

        .btn-sm {
            width: 100%;
        }
    }
</style>

<script>
    $('#viewEmployeeModal').on('shown.bs.modal', function() {
        const firstTab = new bootstrap.Tab(document.querySelector('#personal-tab'));
        firstTab.show();
    });

    $('#viewEmployeeModal').on('hidden.bs.modal', function() {
        $('#viewEmployeeContainer').html('');
    });

    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const contractActionStoreUrl = @json(route('contracts.store'));
        const contractActionUpdateUrlTemplate = @json(route('contracts.update', ['id' => '__ID__']));
        const warningStoreUrl = @json(route('warning.store'));

        async function postJson(url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const payload = await resp.json();
            if (!resp.ok) throw new Error(payload.message || 'Request failed.');
            return payload;
        }

        window.openContractActionModal = function (employeeId, actionType) {
            const form = document.getElementById('contractActionForm');
            form.reset();
            form.querySelector('[name=employee_id]').value = employeeId;
            form.querySelector('[name=action_type]').value = actionType;
            document.getElementById('contractActionModalTitle').textContent =
                actionType === 'termination' ? 'Terminate Employee' : 'Suspend Employee';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('contractActionModal')).show();
        };

        document.getElementById('submitContractActionBtn').addEventListener('click', async function () {
            const form = document.getElementById('contractActionForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            try {
                await postJson(contractActionStoreUrl, data);
                toastr.success('Action recorded successfully.');
                bootstrap.Modal.getInstance(document.getElementById('contractActionModal')).hide();
                if (typeof viewEmployee === 'function') viewEmployee(data.employee_id);
            } catch (e) {
                toastr.error(e.message);
            }
        });

        window.openReinstateModal = function (contractActionId, employeeId) {
            const form = document.getElementById('reinstateForm');
            form.reset();
            form.querySelector('[name=employee_id]').value = employeeId;
            form.querySelector('[name=contract_action_id]').value = contractActionId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('reinstateModal')).show();
        };

        document.getElementById('submitReinstateBtn').addEventListener('click', async function () {
            const form = document.getElementById('reinstateForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            const actionId = data.contract_action_id;
            const employeeId = data.employee_id;
            delete data.contract_action_id;
            try {
                const url = contractActionUpdateUrlTemplate.replace('__ID__', actionId);
                await postJson(url, data);
                toastr.success('Employee reinstated successfully.');
                bootstrap.Modal.getInstance(document.getElementById('reinstateModal')).hide();
                if (typeof viewEmployee === 'function') viewEmployee(employeeId);
            } catch (e) {
                toastr.error(e.message);
            }
        });

        window.openWarnEmployeeModal = function (employeeId) {
            const form = document.getElementById('warnEmployeeForm');
            form.reset();
            form.querySelector('[name=employee_id]').value = employeeId;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('warnEmployeeModal')).show();
        };

        document.getElementById('submitWarnEmployeeBtn').addEventListener('click', async function () {
            const form = document.getElementById('warnEmployeeForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = Object.fromEntries(new FormData(form).entries());
            try {
                await postJson(warningStoreUrl, data);
                toastr.success('Disciplinary case issued successfully.');
                bootstrap.Modal.getInstance(document.getElementById('warnEmployeeModal')).hide();
            } catch (e) {
                toastr.error(e.message);
            }
        });
    })();
</script>
