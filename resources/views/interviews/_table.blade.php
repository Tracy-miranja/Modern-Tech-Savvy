<table class="table table-bordered" id="interviewsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Applicant</th>
            <th>Job Position</th>
            <th>Type</th>
            <th>Scheduled At</th>
            <th>Interviewer</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($interviews as $key => $interview)
        <tr>
            <td>{{ $key + 1 }}.</td>
            <td>{{ $interview->jobApplication->applicant->user->name }}</td>
            <td>{{ $interview->jobApplication->jobPost->title }}</td>
            <td>{{ ucfirst($interview->type) }}</td>
            <td>{{ $interview->scheduled_at->format('M d, Y H:i A') }}</td>
            <td>{{ $interview->interviewer?->name ?? 'Not Assigned' }}</td>
            <td><span class="badge bg-{{ $interview->status === 'scheduled' ? 'warning' : ($interview->status === 'completed' ? 'success' : 'danger') }}">
                {{ ucfirst($interview->status) }}
            </span></td>
            <td>
                <a href="" class="btn btn-info btn-sm">View</a>
                <button class="btn btn-primary btn-sm edit-interview" data-id="{{ $interview->id }}">Edit</button>
                <form action="" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteInterview(this)">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
