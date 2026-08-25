<div id="overtimeTableWrap" data-business-slug="{{ $business->slug ?? session('active_business_slug') }}"></div>
    <table class="table table-striped table-hover" id="overtimeTable">
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                </th>
                <th>Employee</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Type</th>
                <th>Rate</th>
                <th>Total Pay</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimes as $overtime)
                <tr data-overtime-id="{{ $overtime->id }}"
                    class="{{ $overtime->status === 'rejected' ? 'table-danger' : '' }}">
                    <td>
                        @if($overtime->status === 'pending')
                            <input type="checkbox" class="overtime-checkbox" value="{{ $overtime->id }}">
                        @endif
                    </td>
                    <td>{{ $overtime->employee->user->name ?? 'N/A' }}</td>
                    <td>{{ $overtime->date->format('jS M Y') }}</td>
                    <td>{{ \App\Support\TimeFmt::hoursToHm($overtime->overtime_hours) }}</td>
                    <td>
                        @if($overtime->overtime_type === 'regular')
                            <span class="badge bg-primary">Regular OT</span>
                        @elseif($overtime->overtime_type === 'holiday')
                            <span class="badge bg-warning">Holiday OT</span>
                        @else
                            <span class="badge bg-secondary">Manual</span>
                        @endif

                        @if($overtime->attendance_id)
                            <i class="bi bi-robot text-muted" title="Auto-calculated from attendance"></i>
                        @endif
                    </td>
                    <td>{{ number_format($overtime->rate, 2) }}x</td>
                    <td>{{ number_format($overtime->total_pay, 2) }}</td>
                    <td>
                        @if($overtime->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($overtime->status === 'approved')
                            <span class="badge bg-success">Approved</span>
                            @if($overtime->approved_at)
                                <br><small class="text-muted">{{ $overtime->approved_at->format('d/m/Y') }}</small>
                            @endif
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </td>
                    <td>
                        {{ Str::limit($overtime->description, 40) }}
                        @if($overtime->rejection_reason)
                            <br><small class="text-danger"><strong>Reason:</strong> {{ $overtime->rejection_reason }}</small>
                        @endif
                    </td>
                    <td>
                        @if($overtime->status === 'pending')
                            <button onclick="approveOvertime(this)" data-overtime="{{ $overtime->id }}"
                                class="btn btn-sm btn-success" title="Approve">
                                <i class="bi bi-check-circle"></i>
                            </button>
                            <button onclick="rejectOvertime(this)" data-overtime="{{ $overtime->id }}"
                                class="btn btn-sm btn-warning" title="Reject">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            <button onclick="editOvertime(this)" data-overtime="{{ $overtime->id }}"
                                class="btn btn-sm btn-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button onclick="deleteOvertime(this)" data-overtime="{{ $overtime->id }}"
                                class="btn btn-sm btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        @elseif($overtime->status === 'approved')
                            <span class="text-muted">Locked</span>
                            <button onclick="viewOvertimeDetails(this)" data-overtime="{{ $overtime->id }}"
                                class="btn btn-sm btn-info" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                        @else
                            <button onclick="deleteOvertime(this)" data-overtime="{{ $overtime->id }}"
                                class="btn btn-sm btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="table-secondary fw-bold">
                <td colspan="3">TOTAL</td>
                <td>{{ number_format($overtimes->sum('overtime_hours'), 2) }} hrs</td>
                <td colspan="2"></td>
                <td>{{ number_format($overtimes->sum('total_pay'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
            <tr class="table-light">
                <td colspan="3">By Status</td>
                <td colspan="7">
                    Pending: <strong>{{ $overtimes->where('status', 'pending')->count() }}</strong> |
                    Approved: <strong>{{ $overtimes->where('status', 'approved')->count() }}</strong> |
                    Rejected: <strong>{{ $overtimes->where('status', 'rejected')->count() }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    @if($overtimes->where('status', 'pending')->count() > 0)
        <div class="mt-3">
            <button onclick="bulkApproveSelected()" class="btn btn-success">
                <i class="bi bi-check-all"></i> Approve Selected
            </button>
        </div>
    @endif

    @if($overtimes->isEmpty())
        <div class="alert alert-info">
            No overtime records found.
        </div>
    @endif
</div>

@push('scripts')
<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.overtime-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}
</script>
@endpush