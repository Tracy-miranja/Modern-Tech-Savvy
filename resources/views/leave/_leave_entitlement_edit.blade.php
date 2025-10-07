<!-- Edit Leave Entitlement Modal -->
<div class="modal fade" id="leaveEntitlementsEditModal" tabindex="-1" aria-labelledby="leaveEntitlementsEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="leaveEntitlementsEditModalLabel">Edit Leave Entitlement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="leaveEntitlementEditForm">
          @csrf
          <input type="hidden" name="slug" value="{{ rtrim(strtr(base64_encode(implode(':', [
              (int)$entitlement->business_id,
              (int)$entitlement->employee_id,
              (int)$entitlement->leave_type_id,
              (int)$entitlement->leave_period_id
          ])), '+/', '-_'), '=') }}">

          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Employee</label>
              <input class="form-control" value="{{ $entitlement->employee->user->name ?? 'N/A' }}" disabled>
            </div>
            <div class="col-6">
              <label class="form-label">Leave Type</label>
              <input class="form-control" value="{{ $entitlement->leaveType->name ?? 'N/A' }}" disabled>
            </div>

            <div class="col-6">
              <label class="form-label">Period</label>
              <input class="form-control" value="{{ $entitlement->leavePeriod->name ?? 'N/A' }}" disabled>
            </div>

            <div class="col-6">
              <label class="form-label">Entitled Days</label>
              <input type="number" name="entitled_days" class="form-control" step="0.5" min="0"
                     value="{{ (float)$entitlement->entitled_days }}" required>
            </div>

            <div class="col-6">
              <label class="form-label">Accrued Days</label>
              <input type="number" name="accrued_days" class="form-control" step="0.5" min="0"
                     value="{{ (float)$entitlement->accrued_days }}">
            </div>

            <div class="col-6">
              <label class="form-label">Days Taken</label>
              <input class="form-control" value="{{ number_format((float)$entitlement->days_taken,2) }}" disabled>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitEditEntitlementBtn">
          <i class="bi bi-check-circle me-1"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>
