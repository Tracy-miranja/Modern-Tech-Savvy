<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Leave Periods</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="leavePeriodsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Accept <br> Applications</th>
                        <th>Restrict <br> Applications <br> Within Dates</th>
                        <th>Can Accrue</th>
                        <th>Status</th>
                        <th>Lock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leavePeriods as $leavePeriod)
                        <tr>
                            <td>{{ $leavePeriod->name }}</td>
                            <td>{{ $leavePeriod->start_date->format('Y-m-d') }}</td>
                            <td>{{ $leavePeriod->end_date->format('Y-m-d') }}</td>
                            <td>
                                @if ($leavePeriod->accept_applications)
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
                                @endif
                            </td>
                            <td>
                                @if ($leavePeriod->restrict_applications_within_dates)
                                    <span class="badge bg-success"> <i class="bi bi-check-circle"></i> Yes</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
                                @endif
                            </td>
                            <td>
                                @if ($leavePeriod->can_accrue)
                                    <span class="badge bg-success"> <i class="bi bi-check-circle"></i> Yes</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> No</span>
                                @endif
                            </td>
                            <td>
                                @if ($leavePeriod->status === "active")
                                    <span class="badge bg-success"> <i class="bi bi-check-circle"></i> Active</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if ($leavePeriod->isClosed())
                                    <span class="badge bg-dark"><i class="bi bi-lock-fill"></i> Closed</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-unlock-fill"></i> Open</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-info" onclick="viewLeavePeriod(this)" data-id="{{ $leavePeriod->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning" onclick="editLeavePeriod(this)" data-slug="{{ $leavePeriod->slug }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if (!$leavePeriod->isClosed())
                                        <button class="btn btn-outline-dark" onclick="closeLeavePeriod(this)" data-slug="{{ $leavePeriod->slug }}" title="Close this period - blocks new leave requests and computes carryover into the next period">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-danger" onclick="deleteLeavePeriod(this)" data-slug="{{ $leavePeriod->slug }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
