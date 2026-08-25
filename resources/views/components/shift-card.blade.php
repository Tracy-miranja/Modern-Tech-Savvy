@props(['shift'])

<div class="card shadow-sm border-0 h-100">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="icon me-3">
                <i class="bi bi-briefcase text-primary" style="font-size: 24px;"></i>
            </div>
            <div>
                <h5 class="card-title mb-0">{{ $shift->name }}</h5>
                <small class="text-muted">{{ $shift->description ?? 'No description provided.' }}</small>
            </div>
        </div>

        <div class="mt-3">
            <h6 class="mb-3"><strong>Start Time: </strong> - {{ $shift->start_time }} HRS</h6>
            <h6><strong>End Time: </strong> - {{ $shift->end_time }} HRS</h6>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-sm btn-warning me-2" data-shift="{{ $shift->slug }}" onclick="editShift(this)">
                <i class="bi bi-pencil-square"></i> Edit
            </button>
            <button type="button" class="btn btn-sm btn-danger" data-shift="{{ $shift->slug }}" onclick="deleteShift(this)">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    </div>
</div>
