// public/js/Leave-Type.js
import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeaveTypeService from "/js/client/LeaveTypeService.js";

const requestClient = new RequestClient();
const leaveTypeService = new LeaveTypeService(requestClient);

// Helper: ensure a modal exists to show the form if the inline container isn't present
function ensureEditModal() {
  let $modal = $('#leaveTypeEditModal');
  if ($modal.length === 0) {
    $('body').append(`
      <div class="modal fade" id="leaveTypeEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Leave Type</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="leaveTypeEditModalBody"></div>
          </div>
        </div>
      </div>
    `);
    $modal = $('#leaveTypeEditModal');
  }
  return $modal;
}

window.getLeaveType = async function (arg1 = 'pending', arg2 = 1) {
  const status = typeof arg1 === 'string' ? arg1 : 'pending';
  const page   = typeof arg2 === 'number' ? arg2 : 1;

  try {
    const leaveTypes = await leaveTypeService.fetch({ page, status });
    $("#leaveTypeContainer").html(leaveTypes);

    if ($.fn.dataTable) {
      if ($.fn.dataTable.isDataTable('#leaveTypesTable')) {
        $('#leaveTypesTable').DataTable().destroy();
      }
      new DataTable('#leaveTypesTable');
    }
  } catch (error) {
    console.error("Error loading leave types:", error);
    Swal.fire('Error', 'Failed to load leave types. Please try again.', 'error');
  }
};

window.saveLeaveType = async function (btn) {
  btn = $(btn);
  btn_loader(btn, true);

  // IMPORTANT: pick the form near the clicked button (avoids grabbing the wrong form when multiple exist)
  const formEl = btn.closest("form")[0] ?? document.getElementById("leaveTypeForm");
  if (!formEl) {
    btn_loader(btn, false);
    return Swal.fire('Error', 'Edit form not found on the page.', 'error');
  }

  const formData = new FormData(formEl);

  // Ensure slug is present for updates (hidden input may be missing in some partials)
  if (!formData.has('leave_type_slug')) {
    const slugInput = formEl.querySelector('[name="leave_type_slug"]');
    const slugAttr = btn.data("slug") ?? btn.data("leave") ?? btn.data("leaveType") ?? btn.data("id");
    if (slugInput?.value) formData.append('leave_type_slug', slugInput.value);
    else if (slugAttr) formData.append('leave_type_slug', slugAttr);
  }

  try {
    const isUpdate = formData.has('leave_type_slug') && String(formData.get('leave_type_slug')).trim().length > 0;

    if (isUpdate) {
      // normalize boolean-y selects to 0/1 strings (Laravel expects these as strings or ints)
      [
        'requires_approval','is_paid','allowance_accruable','allows_half_day',
        'requires_attachment','prorated_for_new_employees','allows_backdating','is_stepwise'
      ].forEach(k => { if (formData.has(k)) formData.set(k, String(formData.get(k))); });

      await leaveTypeService.update(formData);
    } else {
      await leaveTypeService.save(formData);
    }

    await getLeaveType('pending', 1);
    Swal.fire('Success', 'Leave type saved successfully.', 'success');

    // Close modal if we used it
    $('#leaveTypeEditModal').modal('hide');
  } catch (err) {
    console.error(err);
    Swal.fire('Error', err?.message || 'Failed to save leave type.', 'error');
  } finally {
    btn_loader(btn, false);
  }
};

window.editLeaveType = async function (btn) {
  btn = $(btn);

  // Accept different attribute names
  const slug =
    btn.data("slug") ??
    btn.data("leave") ??
    btn.data("leaveType") ??
    btn.data("id");

  if (!slug) {
    console.error("Missing leave type slug on button data-* attribute");
    Swal.fire('Error', 'Missing leave type identifier on the button.', 'error');
    return;
  }

  try {
    const form = await leaveTypeService.edit({ slug });

    // Prefer inline container if present; else use a modal fallback
    const $container = $('#leaveTypeFormContainer');
    if ($container.length) {
      $container.html(form);
      // Scroll into view (nice UX)
      window.scrollTo({ top: $container.offset().top - 80, behavior: 'smooth' });
    } else {
      const $modal = ensureEditModal();
      $('#leaveTypeEditModalBody').html(form);
      $modal.modal('show');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Failed to load leave type for editing.', 'error');
  }
};

window.viewLeaveType = async function (btn) {
  btn = $(btn);
  const leave_type = btn.data("leave-type");
  try {
    const details = await leaveTypeService.show({ leave_type_slug: leave_type });
    $('#leaveTypeDetailsContent').html(details);
    $('#leaveTypeDetailsModal').modal('show');
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Failed to load leave type details.', 'error');
  }
};

window.deleteLeaveType = async function (btn) {
  btn = $(btn);
  btn_loader(btn, true);
  const leave_type = btn.data("leave-type");

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
        // Pass as object
        await leaveTypeService.delete({ leave_type_slug: leave_type });
        await getLeaveType('pending', 1);
        Swal.fire('Deleted!', 'Leave type deleted.', 'success');
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to delete leave type.', 'error');
      } finally {
        btn_loader(btn, false);
      }
    } else {
      btn_loader(btn, false);
    }
  });
};

