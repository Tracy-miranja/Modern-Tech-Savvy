import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeaveEntitlementsService from "/js/client/LeaveEntitlementsService.js";

const requestClient = new RequestClient();
const leaveEntitlementsService = new LeaveEntitlementsService(requestClient);

window.toggleAllExportChecks = function (containerId, checked) {
    document.querySelectorAll(`#${containerId} input[type="checkbox"]`).forEach(cb => { cb.checked = checked; });
};

function checkedValues(containerId) {
    return Array.from(document.querySelectorAll(`#${containerId} input[type="checkbox"]:checked`)).map(cb => cb.value);
}

window.downloadLeaveEntitlementsPdf = function () {
    const leavePeriodId = document.getElementById('exportLeavePeriodId')?.value;
    if (!leavePeriodId) {
        Swal.fire('Error', 'Select a leave period first.', 'error');
        return;
    }

    const departmentId = document.getElementById('exportDepartmentId')?.value;
    const leaveTypeIds = checkedValues('exportLeaveTypeChecks');
    const employeeIds = checkedValues('exportEmployeeChecks');

    const params = new URLSearchParams();
    params.set('leave_period_id', leavePeriodId);
    if (departmentId) params.set('department_id', departmentId);
    leaveTypeIds.forEach(id => params.append('leave_type_ids[]', id));
    employeeIds.forEach(id => params.append('employee_ids[]', id));

    window.location.href = `/business/${window.businessSlug}/leave-entitlements/export-pdf?${params.toString()}`;
};

window.getLeaveEntitlements = async function (page = 1, leave_period = null) {
  try {
    let data = { page: page, leave_period_slug: leave_period };
    const leaveEntitlements = await leaveEntitlementsService.fetch(data);
    $('#leaveEntitlementsContainer').html(leaveEntitlements);
    new DataTable('#leaveEntitlementsTable');
  } catch (error) {
    console.error("Error loading entitlements table:", error);
  }
};


function currentLeavePeriodParts() {
  const sel = document.getElementById('leave_period_id');
  return {
    id: sel?.value || null,
    slug: sel?.options[sel.selectedIndex]?.dataset.slug || null
  };
}

window.saveLeaveEntitlements = async function (btn) {
  const $btn = $(btn);
  btn_loader($btn, true);
  const formEl = document.getElementById("leaveEntitlementsForm");
  const formData = new FormData(formEl);

  try {
    if (formData.has('leave_period_slug')) {
      await leaveEntitlementsService.update(formData);
    } else {
      await leaveEntitlementsService.save(formData);
    }

    // This form only exists on the "Set Entitlements" page, which has no
    // #leaveEntitlementsContainer to refresh (that's the separate list
    // page) - redirect there instead so the just-saved entitlements are
    // visible, rather than calling getLeaveEntitlements() with no period
    // (which always fails validation and shows a confusing error toast
    // right after the success toast above).
    const redirectUrl = formEl.dataset.entitlementsIndexUrl;
    if (redirectUrl) {
      setTimeout(() => { window.location.href = redirectUrl; }, 1200);
    }
  } finally {
    btn_loader($btn, false);
  }
};


// ADJUST (add/remove days with a required reason - cumulative, distinct
// from the raw-overwrite Edit form)
window.openAdjustEntitlementModal = function (btn) {
    const $btn = $(btn);
    document.getElementById('adjustEntitlementSlug').value = $btn.data('slug');
    document.getElementById('adjustEntitlementEmployeeName').textContent = $btn.data('employee');
    document.getElementById('adjustEntitlementForm').reset();
    document.getElementById('adjustEntitlementSlug').value = $btn.data('slug');

    const modalEl = document.getElementById('adjustEntitlementModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();

    document.getElementById('submitAdjustEntitlementBtn').onclick = async function () {
        const form = document.getElementById('adjustEntitlementForm');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        const $submitBtn = $(this);
        btn_loader($submitBtn, true);

        try {
            const data = Object.fromEntries(new FormData(form).entries());
            await leaveEntitlementsService.adjust(data);
            bootstrap.Modal.getInstance(modalEl).hide();

            const activeTab = document.querySelector('#myTab .nav-link.active');
            const lpSlug = activeTab?.dataset.leavePeriodSlug;
            if (typeof getLeaveEntitlements === 'function') getLeaveEntitlements(1, lpSlug);
        } catch (e) {
            console.error(e);
        } finally {
            btn_loader($submitBtn, false);
        }
    };
};

// SHOW (details)
window.viewLeaveEntitlements = async function (btn) {
  const $btn = $(btn);
  const slug = $btn.data('slug');
  const original = $btn.html();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading');

  try {
    const html = await leaveEntitlementsService.show(slug);

    // remove old modal and append new
    $('#leaveEntitlementsDetailsModal').remove();
    $('body').append(html);

    const modalEl = document.getElementById('leaveEntitlementsDetailsModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  } catch (e) {
    console.error(e);
    toastr.error('Could not load entitlement details.');
  } finally {
    $btn.prop('disabled', false).html(original);
  }
};

// EDIT (form)
window.editLeaveEntitlements = async function (btn) {
  const $btn = $(btn);
  const slug = $btn.data('slug');
  const original = $btn.html();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Loading');

  try {
    const html = await leaveEntitlementsService.edit({ slug });

    // remove old edit modal and append new
    $('#leaveEntitlementsEditModal').remove();
    $('body').append(html);

    const modalEl = document.getElementById('leaveEntitlementsEditModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    // wire the Save button (scoped inside the appended modal)
    $('#submitEditEntitlementBtn').off('click').on('click', async function () {
      const $form = $('#leaveEntitlementEditForm');

      // Use FormData to support files later; RequestClient handles FormData
      const fd = new FormData($form[0]);

      try {
        const resp = await leaveEntitlementsService.update(fd);
        toastr.success(resp.message || 'Updated.');

        // optionally refresh the current tab list
        const activeTab = document.querySelector('#myTab .nav-link.active');
        if (activeTab) {
          const leavePeriodSlug = activeTab.dataset.leavePeriodSlug;
          if (typeof getLeaveEntitlements === 'function') {
            getLeaveEntitlements(1, leavePeriodSlug);
          }
        }

        modal.hide();
      } catch (e) {
        console.error(e);
        // errors are already handled in RequestClient
      }
    });

  } catch (e) {
    console.error(e);
    toastr.error('Could not load entitlement edit form.');
  } finally {
    $btn.prop('disabled', false).html(original);
  }
};
// FIXED: Now uses data-slug attribute
window.deleteLeaveEntitlements = async function (btn) {
  const $btn = $(btn);
  if ($btn.data('busy')) return; // prevent double clicks
  $btn.data('busy', true);
  btn_loader($btn, true);

  try {
    const slug = $btn.data('slug');
    const url  = $btn.data('delete-url'); // from the button's data attribute

    const { isConfirmed } = await Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#068f6d',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!',
    });
    if (!isConfirmed) return;

    // Perform delete (using absolute URL from the button)
    const resp = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      credentials: 'include',
      body: JSON.stringify({ slug }),
    });

    const ct = resp.headers.get('content-type') || '';
    const payload = ct.includes('json') ? await resp.json() : { message: await resp.text() };
    if (!resp.ok) throw new Error(payload.message || 'Delete failed');

    toastr.info(payload.message || 'Leave entitlement deleted.', 'Success');

    // Refresh current tab
    const activeTab = document.querySelector('#myTab .nav-link.active');
    const lpSlug = activeTab?.dataset.leavePeriodSlug;
    if (typeof getLeaveEntitlements === 'function') getLeaveEntitlements(1, lpSlug);
  } catch (err) {
    console.error(err);
    toastr.error(err.message || 'Could not delete entitlement.');
  } finally {
    btn_loader($btn, false);
    $btn.data('busy', false);
  }
};
