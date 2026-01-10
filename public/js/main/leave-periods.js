import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import LeavePeriodsService from "/js/client/LeavePeriodsService.js";

const requestClient = new RequestClient();
const leavePeriodsService = new LeavePeriodsService(requestClient);

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
    const formData = new FormData(form);

    try {
        if (formData.has('leave_period_slug') && formData.get('leave_period_slug').trim()) {
            await leavePeriodsService.update(formData);
        } else {
            await leavePeriodsService.save(formData);
        }
        getLeavePeriods();
        form.reset();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', err?.message || 'Failed to save leave period.', 'error');
    } finally {
        btn_loader(btn, false);
    }
};

// ---------------------------
// EDIT LEAVE PERIOD (Prefill)
// ---------------------------
window.editLeavePeriods = async function (btn) {
    btn = $(btn);
    const leavePeriodId = btn.data("id");
    if (!leavePeriodId) return Swal.fire('Error', 'Leave period ID missing.', 'error');

    try {
        // Build the full URL for JSON fetch
        const url = `${baseUrl}/leave-periods/${leavePeriodId}/json`;
        const data = await requestClient.get(url);

        const form = document.getElementById("leavePeriodsForm");

        // Prefill form fields
        form.elements['name'].value = data.name || '';
        form.elements['start_date'].value = data.start_date || '';
        form.elements['end_date'].value = data.end_date || '';
        form.elements['accept_applications'].checked = !!data.accept_applications;
        form.elements['can_accrue'].checked = !!data.can_accrue;
        form.elements['restrict_applications_within_dates'].checked = !!data.restrict_applications_within_dates;
        form.elements['autocreate'].checked = !!data.autocreate;

        // Hidden field for slug (needed for update)
        let slugInput = form.querySelector('input[name="leave_period_slug"]');
        if (!slugInput) {
            slugInput = document.createElement('input');
            slugInput.type = 'hidden';
            slugInput.name = 'leave_period_slug';
            form.appendChild(slugInput);
        }
        slugInput.value = data.slug;

        // Scroll to form
        form.scrollIntoView({ behavior: 'smooth' });

    } catch (err) {
        console.error('Edit leave period error:', err);
        Swal.fire('Error', 'Failed to load leave period for editing.', 'error');
    }
};

// ---------------------------
// VIEW LEAVE PERIOD DETAILS
// ---------------------------
window.viewLeavePeriods = async function (btn) {
    btn = $(btn);
    const leavePeriodId = btn.data("id");
    if (!leavePeriodId) return Swal.fire('Error', 'Leave period ID missing.', 'error');

    try {
        // Build the full URL for details
        const url = `${baseUrl}/leave-periods/${leavePeriodId}/details`;
        const response = await requestClient.get(url);

        // The response should contain HTML
        $('#leavePeriodsDetailsContent').html(response.data || response);
        $('#leavePeriodsDetailsModal').modal('show');

    } catch (err) {
        console.error('View leave period error:', err);
        Swal.fire('Error', 'Failed to load leave period details.', 'error');
    }
};

// Delete leave period
window.deleteLeavePeriods = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const slug = btn.data("leave-period-slug");
    if (!slug) return Swal.fire('Error', 'Leave period slug missing.', 'error');

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
