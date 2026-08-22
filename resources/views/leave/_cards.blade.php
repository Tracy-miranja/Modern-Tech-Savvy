<div class="row row-cols-1 row-cols-md-2 g-4">
    @forelse($leaveRequests as $leaveRequest)
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Leave Request</h5>
                    <p class="card-text"><strong>Start Date:</strong> {{ optional($leaveRequest->start_date)->format('d M Y') }}</p>
                    <p class="card-text"><strong>End Date:</strong> {{ optional($leaveRequest->end_date)->format('d M Y') }}</p>
                    <p class="card-text"><strong>Reason:</strong> {{ $leaveRequest->reason }}</p>
                    <p class="card-text"><strong>Status:</strong>
                        @if ($leaveRequest->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif ($leaveRequest->status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif ($leaveRequest->status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @else
                            {{ $leaveRequest->status }}
                        @endif
                    </p>
                </div>
                <div class="card-footer">
                    <small class="text-muted">Requested on: {{ $leaveRequest->created_at->format('Y-m-d H:i') }}</small>
                </div>
            </div>
        </div>
    @empty
        <div class="col">
            <p>No leave requests made yet.</p>
        </div>
    @endforelse
</div>
