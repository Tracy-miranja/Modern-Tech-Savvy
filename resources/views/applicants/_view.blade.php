<x-app-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Applicant Details: {{ $applicant->user->name }}</h5>
                        <a href="{{ route('business.applicants.index', $currentBusiness->slug) }}"
                            class="btn btn-light btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Back to Applicants
                        </a>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Personal Information</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">Full Name:</dt>
                                    <dd class="col-sm-8">{{ $applicant->user->name }}</dd>
                                    <dt class="col-sm-4">Email:</dt>
                                    <dd class="col-sm-8">{{ $applicant->user->email }}</dd>
                                    <dt class="col-sm-4">Phone:</dt>
                                    <dd class="col-sm-8">{{ $applicant->user->phone }}</dd>
                                    <dt class="col-sm-4">Address:</dt>
                                    <dd class="col-sm-8">{{ $applicant->address ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">City:</dt>
                                    <dd class="col-sm-8">{{ $applicant->city ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">State:</dt>
                                    <dd class="col-sm-8">{{ $applicant->state ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Zip Code:</dt>
                                    <dd class="col-sm-8">{{ $applicant->zip_code ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Country:</dt>
                                    <dd class="col-sm-8">{{ $applicant->country ?? 'N/A' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Professional Information</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">LinkedIn Profile:</dt>
                                    <dd class="col-sm-8">
                                        @if($applicant->linkedin_profile)
                                        <a href="{{ $applicant->linkedin_profile }}"
                                            target="_blank">{{ $applicant->linkedin_profile }}</a>
                                        @else
                                        N/A
                                        @endif
                                    </dd>
                                    <dt class="col-sm-4">Portfolio URL:</dt>
                                    <dd class="col-sm-8">
                                        @if($applicant->portfolio_url)
                                        <a href="{{ $applicant->portfolio_url }}"
                                            target="_blank">{{ $applicant->portfolio_url }}</a>
                                        @else
                                        N/A
                                        @endif
                                    </dd>
                                    <dt class="col-sm-4">Current Job Title:</dt>
                                    <dd class="col-sm-8">{{ $applicant->current_job_title ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Current Company:</dt>
                                    <dd class="col-sm-8">{{ $applicant->current_company ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Experience Level:</dt>
                                    <dd class="col-sm-8">{{ $applicant->experience_level ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Education Level:</dt>
                                    <dd class="col-sm-8">{{ $applicant->education_level ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Desired Salary:</dt>
                                    <dd class="col-sm-8">
                                        {{ $applicant->desired_salary ? number_format($applicant->desired_salary, 2) : 'N/A' }}
                                    </dd>
                                    <dt class="col-sm-4">Job Preferences:</dt>
                                    <dd class="col-sm-8">{{ $applicant->job_preferences ?? 'N/A' }}</dd>
                                    <dt class="col-sm-4">Source:</dt>
                                    <dd class="col-sm-8">{{ $applicant->source ?? 'N/A' }}</dd>
                                </dl>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted">Job Applications</h6>
                        @if($applications->isEmpty())
                        <p class="text-muted">No applications submitted by this applicant.</p>
                        @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Job Title</th>
                                        <th>Status</th>
                                        <th>Applied At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $index => $application)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $application->jobPost->title ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $application->status == 'pending' ? 'badge-warning' : ($application->status == 'approved' ? 'badge-success' : 'badge-danger') }}">
                                                {{ ucfirst($application->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $application->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <a href="{{ route('business.applications.view', [$currentBusiness->slug, $application->id]) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fa-solid fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
    <script src="{{ asset('js/main/job-applicants.js') }}" type="module"></script>
    @endsection
</x-app-layout>