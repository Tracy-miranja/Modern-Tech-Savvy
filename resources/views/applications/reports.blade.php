<x-app-layout>
    <div class="container py-5">
        <div class="card shadow-md">
            <div class="card-header text-white">
                <h3 class="mb-0">Job Applications Report</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Applicant Name</th>
                                <th scope="col">Position</th>
                                <th scope="col">Status</th>
                                <th scope="col">Applied On</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($applications as $application)
                            <tr>
                                <td>
                                    @if($application->applicant->user)
                                        {{ $application->applicant->user->name }}
                                    @else
                                        <span class="text-muted">Applicant #{{ $application->applicant->id }}</span>
                                    @endif
                                </td>
                                <td>{{ $application->jobPost->title }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ $application->stage === 'applied' ? 'primary' : ($application->stage === 'shortlisted' ? 'success' : ($application->stage === 'rejected' ? 'danger' : 'info')) }}">
                                        {{ ucfirst($application->stage) }}
                                    </span>
                                </td>
                                <td>{{ $application->created_at->format('M d, Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('business.applications.view', [$currentBusiness->slug, $application->id]) }}"
                                        class="btn btn-sm btn-outline-primary me-1">View</a>
                                    @if($application->stage !== 'rejected')
                                    <button class="btn btn-sm btn-outline-danger"
                                        onclick="updateApplicationStage(this, 'rejected', [{{ $application->id }}])">Reject</button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No applications found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-end mt-3">
                    <button class="btn btn-success" onclick="exportApplications(this)" @if($applications->isEmpty())
                        disabled @endif>
                        <i class="bi bi-file-earmark-arrow-down"></i> Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/job-applications.js') }}" type="module"></script>
    <script>
    const csrfToken = '{{ csrf_token() }}';
    </script>
    @endpush
</x-app-layout>
