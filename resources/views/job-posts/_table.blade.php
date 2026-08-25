<table class="table table-hover table-striped" id="jobPostsTable">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Job Title</th>
            <th>Type</th>
            <th>Location</th>
            <th>Posted</th>
            <th>Status</th>
            <th>Public</th>
            <th>Actions</th>
        </tr>
    </thead>

    @php

@endphp
    <tbody>
        @foreach ($job_posts as $index => $job)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $job->title }}</td>
            <td>{{ ucfirst($job->employment_type) }}</td>
            <td>{{ $job->place }}</td>
            <td>{{ $job->created_at->format('d M Y') }}</td>
            <td>
                <span
                    class="badge bg-{{ $job->status === 'open' ? 'success' : ($job->status === 'closed' ? 'danger' : 'secondary') }}">
                    {{ ucfirst($job->getAttribute('status') ?? 'unknown') }}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-{{ $job->is_public ? 'success' : 'warning' }}"
                    data-job-post="{{ $job->slug }}" onclick="togglePublic(this)">
                    <i class="bi bi-{{ $job->is_public ? 'eye' : 'eye-slash' }}"></i>
                    {{ $job->is_public ? 'Yes' : 'No' }}
                </button>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <a href="{{ route('business.recruitment.jobs.edit', ['business' => $currentBusiness->slug, 'jobpost' => $job->slug]) }}"
                        class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button class="btn btn-danger btn-sm" data-job-post="{{ $job->slug }}"
                        onclick="deleteJobPost(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                    <a href="{{ route('business.recruitment.jobs.show', ['business' => $job->business->slug, 'jobpost' => $job->slug]) }}"
                        class="btn btn-info btn-sm">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>