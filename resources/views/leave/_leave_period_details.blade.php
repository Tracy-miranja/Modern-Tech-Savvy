<div class="card">
    <div class="card-body">
        <p><strong>Name:</strong> {{ $leavePeriod->name }}</p>
        <p><strong>Start Date:</strong> {{ $leavePeriod->start_date->format('Y-m-d') }}</p>
        <p><strong>End Date:</strong> {{ $leavePeriod->end_date->format('Y-m-d') }}</p>
        <p><strong>Accept Applications:</strong>
            @if ($leavePeriod->accept_applications)
                <span class="badge bg-success">Yes</span>
            @else
                <span class="badge bg-danger">No</span>
            @endif
        </p>
        <p><strong>Restrict Applications Within Dates:</strong>
            @if ($leavePeriod->restrict_applications_within_dates)
                <span class="badge bg-success">Yes</span>
            @else
                <span class="badge bg-danger">No</span>
            @endif
        </p>
        <p><strong>Can Accrue:</strong>
            @if ($leavePeriod->can_accrue)
                <span class="badge bg-success">Yes</span>
            @else
                <span class="badge bg-danger">No</span>
            @endif
        </p>
        <p><strong>Status:</strong>
            @if ($leavePeriod->status === "active")
                <span class="badge bg-success">Active</span>
            @else
                <span class="badge bg-danger">Inactive</span>
            @endif
        </p>
        <p><strong>Autocreate Next Period:</strong>
            @if ($leavePeriod->autocreate)
                <span class="badge bg-success">Yes</span>
            @else
                <span class="badge bg-danger">No</span>
            @endif
        </p>
    </div>
</div>
