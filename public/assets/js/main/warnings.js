// // warnings.js
// import { btn_loader } from "/js/client/config.js";
// import RequestClient from "/js/client/RequestClient.js";
// import WarningService from "/js/client/WarningService.js";

// const requestClient = new RequestClient();
// const warningService = new WarningService(requestClient);

// window.getWarnings = async function (page = 1) {
//     try {
//         let data = { page: page };
//         const response = await warningService.fetch(data);
//         $("#warningsContainer").html(response.html);
//         $("#warningCount").text(response.count); // Update the badge
//     } catch (error) {
//         console.error("Error loading warnings:", error);
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to load warnings.', 'error');
//     }
// };

// window.saveWarning = async function (btn) {
//     btn = $(btn);
//     btn_loader(btn, true);

//     let form = $('#warningForm');
//     let formData = new FormData(document.getElementById("warningForm"));

//     try {
//         if (formData.has("warning_id")) {
//             await warningService.update(formData);
//             Swal.fire('Success!', 'Warning updated successfully.', 'success');
//         } else {
//             await warningService.save(formData);
//             Swal.fire('Success!', 'Warning issued successfully.', 'success');
//         }
//         form[0].reset();
//         $('#warningFormContainer').html(await warningService.edit({}));
//         getWarnings();
//     } catch (error) {
//         console.error("Error saving warning:", error);
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to save warning.', 'error');
//     } finally {
//         btn_loader(btn, false);
//     }
// };

// window.editWarning = async function (btn) {
//     btn = $(btn);
//     const warning = btn.data("warning");
//     const data = { warning_id: warning };

//     try {
//         const form = await warningService.edit(data);
//         $('#warningFormContainer').html(form);
//     } catch (error) {
//         console.error("Error editing warning:", error);
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to load edit form.', 'error');
//     }
// };

// window.deleteWarning = async function (btn) {
//     btn = $(btn);
//     btn_loader(btn, true);

//     const warning = btn.data("warning");
//     const data = { warning_id: warning };

//     Swal.fire({
//         title: "Are you sure?",
//         text: "You won't be able to revert this!",
//         icon: "warning",
//         showCancelButton: true,
//         confirmButtonColor: "#068f6d",
//         cancelButtonColor: "#d33",
//         confirmButtonText: "Yes, delete it!",
//     }).then(async (result) => {
//         if (result.isConfirmed) {
//             try {
//                 await warningService.delete(data);
//                 Swal.fire('Deleted!', 'Warning deleted successfully.', 'success');
//                 getWarnings();
//             } catch (error) {
//                 console.error("Error deleting warning:", error);
//                 Swal.fire('Error!', error.response?.data?.message || 'Failed to delete warning.', 'error');
//             } finally {
//                 btn_loader(btn, false);
//             }
//         } else {
//             btn_loader(btn, false);
//         }
//     });
// };

// document.addEventListener('DOMContentLoaded', () => {
//     getWarnings();
// });
import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import WarningService from "/js/client/WarningService.js";

const requestClient = new RequestClient();
const warningService = new WarningService(requestClient);

// ── Fetch & render ───────────────────────────────────────────
window.editWarning = async function(id) {
    document.getElementById('warningModalTitle').textContent = 'Edit Warning';
    document.getElementById('warningFormContainer').innerHTML =
        '<div style="text-align:center;padding:30px;color:#9ca3af;">Loading...</div>';
    document.getElementById('warningModal').style.display = 'flex';

    try {
        const response = await warningService.edit({ warning_id: id });
        // WarningService likely already unwraps — try both:
        const html = typeof response === 'string' ? response : (response.data ?? response);
        document.getElementById('warningFormContainer').innerHTML = html;
    } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load form.', 'error');
        closeWarningModal();
    }
};

// ── Save (create or update) ──────────────────────────────────
window.saveWarning = async function(btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const formData = new FormData(document.getElementById("warningForm"));

    try {
        const warningId = formData.get("warning_id");
        const isUpdate  = warningId && warningId.trim() !== "";

        if (isUpdate) {
            await warningService.update(formData);
            Swal.fire('Updated!', 'Warning updated successfully.', 'success');
        } else {
            await warningService.save(formData);
            Swal.fire('Issued!', 'Warning issued successfully.', 'success');
        }
        closeWarningModal();
        getWarnings();
    } catch (error) {
        const msg    = error.response?.data?.message || 'Failed to save warning.';
        const errors = error.response?.data?.errors;
        if (errors) {
            const list = Object.values(errors).flat().join('<br>');
            Swal.fire({ icon: 'error', title: 'Validation Error', html: list });
        } else {
            Swal.fire('Error!', msg, 'error');
        }
    } finally {
        btn_loader(btn, false);
    }
};

// ── Edit ─────────────────────────────────────────────────────

// window.editWarning = async function(id) {
//     document.getElementById('warningModalTitle').textContent = 'Edit Warning';
//     document.getElementById('warningFormContainer').innerHTML =
//         '<div style="text-align:center;padding:30px;color:#9ca3af;">Loading...</div>';
//     document.getElementById('warningModal').style.display = 'flex';

//     try {
//         const html = await warningService.edit({ warning_id: id });
//         document.getElementById('warningFormContainer').innerHTML = html;
//     } catch (error) {
//         Swal.fire('Error!', error.response?.data?.message || 'Failed to load form.', 'error');
//         closeWarningModal();
//     }
// };

// // ── Open create modal ────────────────────────────────────────
// window.openWarningModal = async function() {
//     document.getElementById('warningModalTitle').textContent = 'New Warning';
//     document.getElementById('warningFormContainer').innerHTML =
//         '<div style="text-align:center;padding:30px;color:#9ca3af;">Loading...</div>';
//     document.getElementById('warningModal').style.display = 'flex';

//     try {
//         const html = await warningService.edit({ warning_id: null });
//         document.getElementById('warningFormContainer').innerHTML = html;
//     } catch (error) {
//         Swal.fire('Error!', 'Failed to load form.', 'error');
//         closeWarningModal();
//     }
// };
// ── Close modal ──────────────────────────────────────────────
window.closeWarningModal = function() {
    document.getElementById('warningModal').style.display = 'none';
};

// ── Delete ───────────────────────────────────────────────────
window.deleteWarning = async function(id, btn) {  // ← accepts id directly
    const result = await Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Yes, delete it!",
    });

    if (!result.isConfirmed) return;

    $(btn).prop('disabled', true);
    try {
        await warningService.delete({ warning_id: id });
        Swal.fire('Deleted!', 'Warning deleted successfully.', 'success');
        getWarnings();
    } catch (error) {
        Swal.fire('Error!', error.response?.data?.message || 'Failed to delete.', 'error');
    } finally {
        $(btn).prop('disabled', false);
    }
};

// ── Filters ──────────────────────────────────────────────────
window.filterWarningsTable = function() {
    const search = (document.getElementById('warningSearch')?.value || '').toLowerCase();
    const status = (document.getElementById('statusFilter')?.value || '').toLowerCase();
    const date   =  document.getElementById('dateFilter')?.value || '';

    document.querySelectorAll('#warningsTableBody tr[data-date]').forEach(row => {
        const matchSearch = !search || row.textContent.toLowerCase().includes(search);
        const matchStatus = !status || (row.dataset.status || '') === status;
        const matchDate   = !date   || (row.dataset.date   || '') === date;
        row.style.display = (matchSearch && matchStatus && matchDate) ? '' : 'none';
    });
};
window.getWarnings = async function(page = 1) {
    try {
        const response = await warningService.fetch({ page });
        document.getElementById('warningsTableBody').innerHTML = response.html;
        document.getElementById('warningCount').textContent = response.count;
    } catch (error) {
        console.error("Error loading warnings:", error);
        Swal.fire('Error!', error.response?.data?.message || 'Failed to load warnings.', 'error');
    }
};
window.filterByDate   = () => filterWarningsTable();
window.filterByStatus = () => filterWarningsTable();

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    getWarnings();
    document.getElementById('warningModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeWarningModal();
    });
});
