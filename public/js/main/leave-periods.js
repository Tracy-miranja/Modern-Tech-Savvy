import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeavePeriodsService from "/js/client/LeavePeriodsService.js";
import LeaveEntitlementsService from "/js/client/LeaveEntitlementsService.js";

const requestClient = new RequestClient();
const leavePeriodsService = new LeavePeriodsService(requestClient);
const leaveEntitlementsService = new LeaveEntitlementsService(requestClient);

window.processCarryover = async function (btn) {
    const $btn = $(btn);
    const form = document.getElementById("processCarryoverForm");
    if (!form.checkValidity()) { form.reportValidity(); return; }

    btn_loader($btn, true);
    try {
        const data = Object.fromEntries(new FormData(form).entries());
        await leaveEntitlementsService.processCarryover(data);
    } finally {
        btn_loader($btn, false);
    }
};

// Base URL for leave-periods routes (adjust for business prefix)
const baseUrl = window.location.pathname.match(/\/business\/[^\/]+/)?.[0] || '';

// Add to the top of leave-period.js
console.log('Current URL:', window.location.pathname);
console.log('Base URL should be:', window.location.pathname.match(/\/business\/[^\/]+/)?.[0]);

// Fetch and render leave periods
window.getLeavePeriods = async function (page = 1) {
    try {
        const html = await leavePeriodsService.fetch({ page });
        $("#leavePeriodsContainer").html(html);
        if ($.fn.DataTable) new DataTable('#leavePeriodsTable');
    } catch (err) {
        console.error('Error loading leave periods:', err);
    }
};

// Save or update leave period
window.saveLeavePeriods = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const form = document.getElementById("leavePeriodsForm");
    let formData = new FormData(form);
    const isUpdate = formData.has('leave_period_slug');

    try {
        if (isUpdate) {
            await leavePeriodsService.update(formData);
            await window.cancelEditLeavePeriod();
        } else {
            await leavePeriodsService.save(formData);
            form.reset();
        }
        getLeavePeriods();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', err?.message || 'Failed to save leave period.', 'error');
    } finally {
        btn_loader(btn, false);
    }
};
window.editLeavePeriod = async function (btn) {
    btn = $(btn);

    const slug = btn.data("slug");
    const data = { leave_period_slug: slug };

    try {
        const form = await leavePeriodsService.edit(data);
        $('#leavePeriodsFormContainer').html(form);
        window.scrollTo({ top: $('#leavePeriodsFormContainer').offset().top - 80, behavior: 'smooth' });
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to load the leave period for editing.', 'error');
    }
};

window.cancelEditLeavePeriod = async function () {
    try {
        const form = await leavePeriodsService.create();
        $('#leavePeriodsFormContainer').html(form);
    } catch (err) {
        console.error(err);
    }
};

window.viewLeavePeriod = async function (btn) {
    btn = $(btn);

    const id = btn.data("id");
    const data = { id: id };

    try {
        const details = await leavePeriodsService.show(data);
        $('#leavePeriodDetailsContent').html(details);
        const modalEl = document.getElementById('leavePeriodDetailsModal');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to load leave period details.', 'error');
    }
};

window.closeLeavePeriod = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const slug = btn.data("slug");

    Swal.fire({
        title: "Close this leave period?",
        text: "New leave requests can no longer be dated within it, and carryover will be computed into the next period. This cannot be undone from here (support-only reopen).",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#068f6d",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, close it",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await leavePeriodsService.close(slug);
                getLeavePeriods();
            } catch (err) {
                Swal.fire('Error', err?.message || 'Failed to close leave period.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};

window.deleteLeavePeriod = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const slug = btn.data("slug");
    const data = { leave_period_slug: slug };

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
                await leavePeriodsService.delete(slug);
                getLeavePeriods();
            } catch (err) {
                Swal.fire('Error', err?.message || 'Failed to delete leave period.', 'error');
            } finally {
                btn_loader(btn, false);
            }
        } else {
            btn_loader(btn, false);
        }
    });
};