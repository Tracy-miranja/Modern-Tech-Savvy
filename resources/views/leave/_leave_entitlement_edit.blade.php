<!-- Edit Leave Entitlement Modal -->
<div class="modal fade" id="leaveEntitlementsEditModal" tabindex="-1" aria-labelledby="leaveEntitlementsEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="leaveEntitlementsEditModalLabel">Edit Leave Entitlement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="leaveEntitlementEditForm" data-accruable="{{ $entitlement->leaveType->allowance_accruable ? '1' : '0' }}">
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
              <label class="form-label">Entitled Days{{ $entitlement->leaveType->allowance_accruable ? ' (reference ceiling)' : '' }}</label>
              <input type="number" name="entitled_days" class="form-control calc-field" step="0.5" min="0"
                     value="{{ (float)$entitlement->entitled_days }}" required>
            </div>

            <div class="col-6">
              <label class="form-label">Accrued Days{{ $entitlement->leaveType->allowance_accruable ? ' (usable now)' : '' }}</label>
              <input type="number" name="accrued_days" class="form-control calc-field" step="0.5" min="0"
                     value="{{ (float)$entitlement->accrued_days }}">
            </div>

            <div class="col-6">
              <label class="form-label">Carryover Days</label>
              <input type="number" name="carryover_days" class="form-control calc-field" step="0.5" min="0"
                     value="{{ (float)$entitlement->carryover_days }}">
            </div>

            <div class="col-6">
              <label class="form-label">Adjustment Days</label>
              <input class="form-control" value="{{ (float)$entitlement->adjustment_days }}" disabled>
              <small class="text-muted">Use the Adjust action to change this, not this form.</small>
            </div>

            <div class="col-6">
              <label class="form-label">Days Taken (approved, live)</label>
              <input class="form-control" value="{{ (float)$entitlement->days_taken }}" disabled>
            </div>

            <div class="col-6">
              <label class="form-label">Days Pending (awaiting approval, live)</label>
              <input class="form-control" value="{{ (float)$entitlement->days_pending }}" disabled>
            </div>

            <div class="col-6">
              <label class="form-label">Total Days (auto)</label>
              <input id="total_days_preview" class="form-control" value="{{ (float)$entitlement->total_days }}" disabled>
            </div>

            <div class="col-6">
              <label class="form-label">Days Remaining (auto)</label>
              <input id="days_remaining_preview" class="form-control" value="{{ (float)$entitlement->days_remaining }}" disabled>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <small class="me-auto text-muted" id="calcHint"></small>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitEditEntitlementBtn">
          <i class="bi bi-check-circle me-1"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Live preview only - the server (LeaveEntitlement::recalculateTotals())
// is the actual source of truth; this just mirrors its formula so the
// modal doesn't show stale numbers while editing.
(function() {
  function toNum(v){ const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function recalc() {
    const form = document.getElementById('leaveEntitlementEditForm');
    const isAccruable = form.dataset.accruable === '1';
    const entitled  = toNum(document.querySelector('[name="entitled_days"]').value);
    const accrued   = toNum(document.querySelector('[name="accrued_days"]').value);
    const carryover = toNum(document.querySelector('[name="carryover_days"]').value);
    const adjustment = {{ (float)$entitlement->adjustment_days }};
    const taken     = {{ (float)$entitlement->days_taken }};
    const pending   = {{ (float)$entitlement->days_pending }};

    // Never sum entitled + accrued together - one or the other, matching
    // LeaveEntitlement::recalculateTotals().
    const usableFromGrant = isAccruable ? accrued : entitled;
    const total = usableFromGrant + carryover + adjustment;
    const remaining = Math.max(0, total - taken - pending);

    document.getElementById('total_days_preview').value = total.toFixed(2);
    document.getElementById('days_remaining_preview').value = remaining.toFixed(2);

    const hint = document.getElementById('calcHint');
    hint.textContent = (taken + pending) > total
      ? 'Warning: taken + pending exceeds total entitlement.'
      : '';
  }

  document.querySelectorAll('#leaveEntitlementEditForm .calc-field').forEach(el => {
    el.addEventListener('input', recalc);
    el.addEventListener('change', recalc);
  });
})();
</script>
