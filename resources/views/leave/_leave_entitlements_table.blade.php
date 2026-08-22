<!-- _leave_entitlements_table.blade.php -->
@php
    // Build a composite slug that can find the record without exposing ID
    // slug = base64("business:employee:leaveType:leavePeriod") URL-safe
    function ent_slug($e) {
        $raw = implode(':', [
            (int)$e->business_id,
            (int)$e->employee_id,
            (int)$e->leave_type_id,
            (int)$e->leave_period_id
        ]);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
@endphp

<table class="table table-hover table-bordered table-striped" id="leaveEntitlementsTable">
    <thead>
        <tr>
            <th>Employee No</th>
            <th>Employee</th>
            <th>Leave Type</th>
            <th>Entitled Days</th>
            <th>Total Days</th>
            <th>Accrued Days</th>
            <th>Carryover Days</th>
            <th>Days Taken</th>
            <th>Days <br> Remaining</th>
            <th style="width: 220px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($leaveEntitlements as $entitlement)
            @php $slug = ent_slug($entitlement); @endphp
            <tr>
                <td>
                    <a href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-hash"></i>
                        {{ $entitlement->employee->employee_code ?? '—' }}
                    </a>
                </td>
                <td>{{ $entitlement->employee->user->name ?? 'N/A' }}</td>
                <td>{{ $entitlement->leaveType->name ?? 'N/A' }}</td>
                <td>{{ number_format((float) $entitlement->entitled_days, 2) }}</td>
                <td>{{ number_format((float) $entitlement->total_days, 2) }}</td>
                <td>{{ number_format((float) $entitlement->accrued_days, 2) }}</td>
                <td>{{  number_format((float) $entitlement->carryover_days, 2) }}</td>
                <td>{{ number_format((float) $entitlement->days_taken, 2) }}</td>
                <td>{{ number_format((float) $entitlement->days_remaining, 2) }}</td>
                <td class="d-flex gap-1 flex-wrap">
                     <button type="button"
                            class="btn btn-secondary btn-sm"
                            title="Details"
                            data-id="{{ $entitlement->id }}"
                            data-slug="{{ $slug }}"
                            onclick="viewLeaveEntitlements(this)">
                        <i class="bi bi-eye"></i> 
                    </button>

                    <button type="button"
                            class="btn btn-warning btn-sm"
                            title="Edit"
                            data-id="{{ $entitlement->id }}"
                            data-slug="{{ $slug }}"
                            onclick="editLeaveEntitlements(this)">
                        <i class="bi bi-pencil-square me-1"></i> 
                    </button>

                    <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    title="Delete"
                    data-slug="{{ $slug }}"
                    data-delete-url="{{ route('business.leave-entitlements.delete', ['business' => $currentBusiness->slug]) }}"
                    onclick="deleteLeaveEntitlements(this)">
                    <i class="bi bi-trash me-1"></i>
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary btn-sm"
                            title="Adjust (add or remove days with a reason)"
                            data-slug="{{ $slug }}"
                            data-employee="{{ $entitlement->employee->user->name ?? 'N/A' }}"
                            onclick="openAdjustEntitlementModal(this)">
                        <i class="bi bi-sliders me-1"></i> Adjust
                    </button>
            </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted">No entitlements found for the selected period.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Adjust Entitlement Modal -->
<div class="modal fade" id="adjustEntitlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Entitlement - <span id="adjustEntitlementEmployeeName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="adjustEntitlementForm">
                    <input type="hidden" name="slug" id="adjustEntitlementSlug">
                    <div class="mb-3">
                        <label class="form-label">Adjustment (days)</label>
                        <input type="number" step="0.5" name="adjustment_days" class="form-control" required>
                        <small class="text-muted">Positive to grant extra days, negative to claw back. Cumulative with any previous adjustment.</small>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="e.g. data-entry correction, goodwill grant for extra project work..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitAdjustEntitlementBtn">Save</button>
            </div>
        </div>
    </div>
</div>
