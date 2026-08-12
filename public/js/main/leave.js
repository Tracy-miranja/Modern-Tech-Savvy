import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeaveService from "/js/client/LeaveService.js";

const requestClient = new RequestClient();
const leaveService = new LeaveService(requestClient);

/**
 * Robust getter that supports BOTH calling styles:
 *  - getLeave('pending')            // status first
 *  - getLeave(1, 'pending')         // page, status
 *  - getLeave()                     // defaults to pending, page 1
 */
window.getLeave = async function (arg1 = 'pending', arg2 = 1) {
    let status, page;

    if (typeof arg1 === 'string') {
        status = arg1 || 'pending';
        page = typeof arg2 === 'number' ? arg2 : 1;
    } else {
        page = typeof arg1 === 'number' ? arg1 : 1;
        status = typeof arg2 === 'string' ? arg2 : 'pending';
    }

    // Normalize UI/API status naming
    const normalizedStatus = (status === 'declined') ? 'rejected' : status;

    try {
        const data = { page, status: normalizedStatus };

        const departmentId = document.getElementById('leaveFilterDepartment')?.value;
        const locationId = document.getElementById('leaveFilterLocation')?.value;
        const leaveTypeId = document.getElementById('leaveFilterType')?.value;
        const leavePeriodId = document.getElementById('leaveFilterPeriod')?.value;
        if (departmentId) data.department_id = departmentId;
        if (locationId) data.location_id = locationId;
        if (leaveTypeId) data.leave_type_id = leaveTypeId;
        if (leavePeriodId) data.leave_period_id = leavePeriodId;

        const leaveTable = await leaveService.fetch(data);

        const containerId = `#${normalizedStatus}Container`;
        if (document.querySelector(containerId)) {
            $(containerId).html(leaveTable);
        }

        const tableId = `#${normalizedStatus}LeaveRequestsTable`;
        if ($(tableId).length > 0) {
            try {
                if ($.fn.dataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().destroy();
                }
                new DataTable(tableId);
            } catch (e) {
                console.warn('DataTable init skipped:', e?.message || e);
            }
        }
    } catch (error) {
        console.error("Error loading leave data:", error);
        Swal.fire('Error', 'Failed to load leave requests. Please try again.', 'error');
    }
};


/**
 * Create or update a leave request
 */
window.saveLeave = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const formEl = document.getElementById("leaveForm");
    const formData = new FormData(formEl);

    // If "attach later" is checked, do NOT force an attachment on the client side
    const attachLaterEl = formEl.querySelector('#attach_later');
    const requiresAttachmentNow =
        (formEl.querySelector('#leave_type')?.selectedOptions[0]?.getAttribute('data-requires-attachment') === '1') &&
        !(attachLaterEl && attachLaterEl.checked);

    const attachmentInput = formEl.querySelector('#attachment');
    if (attachmentInput) {
        if (requiresAttachmentNow) {
            attachmentInput.setAttribute('required', 'required');
        } else {
            attachmentInput.removeAttribute('required');
        }
    }

    try {
        if (formData.has('leave_slug')) {
            await leaveService.update(formData);
        } else {
            await leaveService.save(formData);
        }

        // Refresh typical tabs
        getLeave('pending');
        getLeave('approved');
        getLeave('declined');

        Swal.fire('Success', 'Leave request saved successfully.', 'success');
        // Optional: reset form
        // formEl.reset();
    } catch (err) {
        console.error(err);
        const msg = err?.message || 'Failed to save the leave request.';
        Swal.fire('Error', msg, 'error');
    } finally {
        btn_loader(btn, false);
    }
};

window.editLeave = async function (btn) {
    btn = $(btn);

    const leave = btn.data("leave");
    const data = { leave: leave };

    try {
        const form = await leaveService.edit(data);
        $('#leaveFormContainer').html(form);
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to load the edit form.', 'error');
    }
};

window.deleteLeave = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const leave = btn.data("leave");
    const data = { leave: leave };

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
                await leaveService.delete(data);
                getLeave('pending');
                getLeave('approved');
                getLeave('declined');
                Swal.fire('Deleted!', 'Leave request deleted.', 'success');
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to delete leave request.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

/**
 * Approve / Reject
 */
window.manageLeave = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const leave = btn.data("leave");
    const action = btn.data("action");
    const status = action === "reject" ? "rejected" : "approved";

    let data = { reference_number: leave, status: status };

    if (action === "reject") {
        const { value: reject_reason } = await Swal.fire({
            title: "Enter Rejection Reason",
            input: "textarea",
            inputPlaceholder: "Provide a reason for rejection...",
            inputAttributes: {
                maxlength: "200",
                autocapitalize: "off",
                autocorrect: "off",
            },
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Reject Leave",
            cancelButtonText: "Cancel",
            inputValidator: (value) => {
                if (!value) return "Rejection reason is required!";
            },
        });

        if (!reject_reason) {
            btn_loader(btn, false);
            return;
        }

        data.rejection_reason = reject_reason;
    }

    Swal.fire({
        title: `Are you sure you want to ${action} this leave?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: action === "approve" ? "#068f6d" : "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: `Yes, ${action} it!`,
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await leaveService.status(data);

                // Refresh known tabs; "declined" maps to backend 'rejected'
                getLeave('pending');
                getLeave('approved');
                getLeave('declined');

                Swal.fire('Done', `Leave ${action}d successfully.`, 'success');
            } catch (err) {
                console.error(err);
                Swal.fire('Error', `Failed to ${action} the leave.`, 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

/* ---------------------------------------------------
   Attachment field toggle + "attach later" handling
--------------------------------------------------- */
document.addEventListener("DOMContentLoaded", function () {
    const leaveTypeSelect = document.getElementById("leave_type");
    const attachmentDiv   = document.getElementById("attachmentField");
    const attachmentInput = document.getElementById("attachment");
    const attachLater     = document.getElementById("attach_later");

    const halfDayRow      = document.getElementById('halfDayRow');
    const halfDayCheckbox = document.getElementById('half_day');
    const halfDayTypeCol  = document.getElementById('halfDayTypeCol');

    const startDate = document.getElementById("start_date");
    const endDate   = document.getElementById("end_date");

    function todayStr() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }

    function applyBackdatingRule(allowsBackdating) {
        if (!startDate || !endDate) return;
        if (allowsBackdating) {
            startDate.removeAttribute('min');
            endDate.removeAttribute('min');
        } else {
            const t = todayStr();
            startDate.setAttribute('min', t);
            endDate.setAttribute('min', t);
        }
    }

    function toggleHalfDayUI(allowsHalfDay) {
        if (!halfDayRow || !halfDayCheckbox || !halfDayTypeCol) return;
        if (allowsHalfDay) {
            halfDayRow.classList.remove('d-none');
        } else {
            halfDayRow.classList.add('d-none');
            halfDayCheckbox.checked = false;
            halfDayTypeCol.classList.add('d-none');
        }
    }

    halfDayCheckbox?.addEventListener('change', () => {
        if (halfDayCheckbox.checked) {
            halfDayTypeCol.classList.remove('d-none');
        } else {
            halfDayTypeCol.classList.add('d-none');
        }
    });

    if (attachLater && attachmentInput) {
        attachLater.addEventListener('change', () => {
            if (attachLater.checked) {
                attachmentInput.removeAttribute('required');
            } else {
                // Only require if the type truly requires an attachment
                const requires = leaveTypeSelect?.selectedOptions[0]?.getAttribute('data-requires-attachment') === '1';
                if (requires) {
                    attachmentInput.setAttribute('required', 'required');
                }
            }
        });
    }

    if (leaveTypeSelect && attachmentDiv && attachmentInput) {
        leaveTypeSelect.addEventListener("change", function () {
            const selected = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
            const requiresAttachment = selected.getAttribute("data-requires-attachment") === "1";
            const allowsBackdating   = selected.getAttribute("data-allows-backdating") === "1";
            const allowsHalfDay      = selected.getAttribute("data-allows-half-day") === "1";

            // Attachment UI
            if (requiresAttachment) {
                attachmentDiv.classList.remove("d-none");
                if (!(attachLater && attachLater.checked)) {
                    attachmentInput.setAttribute("required", "required");
                }
            } else {
                attachmentDiv.classList.add("d-none");
                attachmentInput.removeAttribute("required");
                attachmentInput.value = "";
                if (attachLater) attachLater.checked = false;
            }

            // Backdating
            applyBackdatingRule(allowsBackdating);

            // Half-day
            toggleHalfDayUI(allowsHalfDay);
        });
    }

    /* ---------------------------------------------------
       Date validation: ensure end_date >= start_date
    --------------------------------------------------- */
    if (startDate && endDate) {
        startDate.addEventListener("change", function () {
            endDate.min = startDate.value || endDate.min || '';
        });
    }

    // Initialize min dates to today by default; changed again after leave type chosen
    if (startDate && endDate) {
        const t = todayStr();
        if (!startDate.min) startDate.min = t;
        if (!endDate.min) endDate.min = t;
    }
});
