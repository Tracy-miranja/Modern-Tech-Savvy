
<div class="modal fade" id="leaveEntitlementsDetailsModal" tabindex="-1" aria-labelledby="leaveEntitlementsDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveEntitlementsDetailsModalLabel">Leave Entitlement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" id="leaveEntitlementsDetailsContent">
                <div class="mb-2"><strong>Employee:</strong> {{ $entitlement->employee->user->name ?? 'N/A' }}</div>
                <div class="mb-2"><strong>Employee No:</strong> {{ $entitlement->employee->employee_code ?? '—' }}</div>
                <div class="mb-2"><strong>Leave Type:</strong> {{ $entitlement->leaveType->name ?? 'N/A' }}</div>
                <div class="mb-2"><strong>Period:</strong> {{ $entitlement->leavePeriod->name ?? 'N/A' }}</div>
                <hr>
                <div class="row g-3">
                    <div class="col-6"><strong>Entitled Days:</strong> {{ number_format((float)$entitlement->entitled_days,2) }}</div>
                    <div class="col-6"><strong>Accrued Days:</strong> {{ number_format((float)$entitlement->accrued_days,2) }}</div>
                    <div class="col-6"><strong>Carried Over Days:</strong> {{ number_format((float)$entitlement->carryover_days,2) }}</div>
                    <div class="col-6"><strong>Total Days:</strong> {{ number_format((float)$entitlement->total_days,2) }}</div>
                    <div class="col-6"><strong>Days Taken:</strong> {{ number_format((float)$entitlement->days_taken,2) }}</div>
                    <div class="col-6"><strong>Days Remaining:</strong> {{ number_format((float)$entitlement->days_remaining,2) }}</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
