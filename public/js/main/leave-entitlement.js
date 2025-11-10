import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeaveEntitlementsService from "/js/client/LeaveEntitlementsService.js";

const requestClient = new RequestClient();
const leaveEntitlementsService = new LeaveEntitlementsService(requestClient);

// add this helper at the top (below the const leaves lines is fine)
function currentLeavePeriodSlug() {
  // try the active tab (index page)
  const activeTab = document.querySelector('#myTab .nav-link.active');
  if (activeTab?.dataset.leavePeriodSlug) return activeTab.dataset.leavePeriodSlug;

  // fall back to the select (create page)
  const sel = document.getElementById('leave_period_id');
  return sel?.options[sel.selectedIndex]?.dataset.slug || null;
}

window.getLeaveEntitlementsByPeriod = async function (payload) {
  // Allow old calls (numeric id) and new calls (object with id/slug)
  const body = (payload && typeof payload === 'object')
    ? payload
    : { leave_period_id: payload };

  return await requestClient.post('/leave-entitlements/get-by-period', body);
};

// Make this GLOBAL because the tabs and other scripts call it
window.getLeaveEntitlements = async function (page = 1, leavePeriodArg = null) {
  try {
    // Accept either a slug string, a numeric id, or an object {leave_period_id, leave_period_slug}
    let payload = { page };

    if (leavePeriodArg && typeof leavePeriodArg === 'object') {
      if (leavePeriodArg.leave_period_slug) payload.leave_period_slug = leavePeriodArg.leave_period_slug;
      if (leavePeriodArg.leave_period_id)   payload.leave_period_id   = leavePeriodArg.leave_period_id;
    } else if (typeof leavePeriodArg === 'string') {
      // treat non-numeric string as slug
      if (/^\d+$/.test(leavePeriodArg)) payload.leave_period_id = leavePeriodArg;
      else payload.leave_period_slug = leavePeriodArg;
    } else if (leavePeriodArg != null) {
      // number passed
      payload.leave_period_id = leavePeriodArg;
    } else {
      // no arg passed: try to infer from active tab first, then the select
      const activeTab = document.querySelector('#myTab .nav-link.active');
      if (activeTab?.dataset.leavePeriodSlug) {
        payload.leave_period_slug = activeTab.dataset.leavePeriodSlug;
      } else {
        const sel = document.getElementById('leave_period_id');
        if (sel) {
          const id = sel.value || null;
          const slug = sel.options[sel.selectedIndex]?.dataset.slug || null;
          if (slug) payload.leave_period_slug = slug;
          if (id)   payload.leave_period_id   = id;
        }
      }
    }

    const html = await leaveEntitlementsService.fetch(payload);
    const container = document.getElementById('leaveEntitlementsContainer');
    if (container) {
      container.innerHTML = html;
      // init DataTable only if the table exists in the returned HTML
      const tableEl = document.querySelector('#leaveEntitlementsTable');
      if (tableEl && typeof DataTable !== 'undefined') {
        new DataTable('#leaveEntitlementsTable');
      }
    }
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

  const { id, slug } = currentLeavePeriodParts();
  if (id && !formData.has('leave_period_id'))   formData.append('leave_period_id', id);
  if (slug && !formData.has('leave_period_slug')) formData.append('leave_period_slug', slug);

  try {
    if (formData.has('leave_period_slug') || formData.has('leave_period_id')) {
      await leaveEntitlementsService.save(formData); // or update, depending on your flow
    } else {
      toastr.error('Please select a leave period first.');
      return;
    }
    // refresh with the same period
    getLeaveEntitlements(1, slug || null);
  } finally {
    btn_loader($btn, false);
  }
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

    // Hard assert that SweetAlert2 is available
    if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
      console.warn('SweetAlert2 (Swal) not found. Falling back to window.confirm().');
      const proceed = window.confirm('Are you sure? This action cannot be undone.');
      if (!proceed) return;
    } else {
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
    }

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
