<div class="mb-2"><strong>Name:</strong> {{ $leavePeriod->name }}</div>
<div class="mb-2"><strong>Start Date:</strong> {{ $leavePeriod->start_date->format('jS M Y') }}</div>
<div class="mb-2"><strong>End Date:</strong> {{ $leavePeriod->end_date->format('jS M Y') }}</div>
<hr>
<div class="row g-3">
    <div class="col-6">
        <strong>Status:</strong>
        @if ($leavePeriod->status === 'active')
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
        @else
            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span>
        @endif
    </div>
    <div class="col-6">
        <strong>Accept Applications:</strong>
        @if ($leavePeriod->accept_applications)
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
        @else
            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
        @endif
    </div>
    <div class="col-6">
        <strong>Can Accrue:</strong>
        @if ($leavePeriod->can_accrue)
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
        @else
            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
        @endif
    </div>
    <div class="col-6">
        <strong>Restrict Applications Within Dates:</strong>
        @if ($leavePeriod->restrict_applications_within_dates)
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
        @else
            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
        @endif
    </div>
    <div class="col-6">
        <strong>Autocreate Next Period:</strong>
        @if ($leavePeriod->autocreate)
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
        @else
            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
        @endif
    </div>
    <div class="col-6">
        <strong>Archived:</strong>
        @if ($leavePeriod->archive)
            <span class="badge bg-secondary"><i class="bi bi-archive"></i> Yes</span>
        @else
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> No</span>
        @endif
    </div>
</div>
