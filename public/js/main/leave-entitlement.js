import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeaveEntitlementsService from "/js/client/LeaveEntitlementsService.js";

const requestClient = new RequestClient();
const leaveEntitlementsService = new LeaveEntitlementsService(requestClient);

window.getLeaveEntitlements = async function (page = 1, leave_period = null) {
    try {
        let data = { page: page, leave_period_slug: leave_period };
        const leaveEntitlements = await leaveEntitlementsService.fetch(data);
        $('#leaveEntitlementsContainer').html(leaveEntitlements);
        new DataTable('#leaveEntitlementsTable');
    } catch (error) {
        console.error("Error loading user data:", error);
    }
};

window.saveLeaveEntitlements = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    let formData = new FormData(document.getElementById("leaveEntitlementsForm"));

    try {
        if (formData.has('leave_period_slug')) {
            await leaveEntitlementsService.update(formData);
        } else {
            await leaveEntitlementsService.save(formData);
        }
        getLeaveEntitlements();
    } finally {
        btn_loader(btn, false);
    }
};

/** FIXED: Now uses data-slug attribute
window.editLeaveEntitlements = async function (btn) {
    btn = $(btn);

    const slug = btn.data("slug");
    const data = { slug: slug };

    try {
        const form = await leaveEntitlementsService.edit(data);
        $('#leaveEntitlementsFormContainer').html(form)
    } catch (error) {
        console.error("Error editing entitlement:", error);
    }
};*/


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
    btn = $(btn);
    btn_loader(btn, true);

    const slug = btn.data("slug");
    const data = { slug: slug };

    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await leaveEntitlementsService.delete(data);
                getLeaveEntitlements();
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};