import { btn_loader } from "/js/client/config.js";
import RequestClient from "/js/client/RequestClient.js";
import BusinessesService from "/js/client/BusinessesService.js";

const requestClient = new RequestClient();
const businessesService = new BusinessesService(requestClient);

if (!window.currentBusinessSlug) {
    console.warn("currentBusinessSlug not defined, falling back to 'krest'");
    window.currentBusinessSlug = 'krest';
}

window.getClients = async function (page = 1) {
    try {
        const response = await businessesService.clients({ page });
        $("#clientsContainer").html(response);
        if ($('#clientsTable').length) {
            new DataTable('#clientsTable', {
                pageLength: 10,
                searching: true,
                ordering: true,
            });
        }
    } catch (error) {
        Swal.fire('Error', 'Failed to load clients.', 'error');
        console.error("Error loading clients:", error);
    }
};

window.impersonateBusiness = async function (businessSlug) {
    try {
        const response = await businessesService.post(`/businesses/${window.currentBusinessSlug}/clients/${businessSlug}/impersonate`, {});

        // response is already the data object, not wrapped in another layer
        if (response.redirect_url) {
            window.location.href = response.redirect_url;
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.message || 'Impersonation successful.',
            });
        }
        return true;
    } catch (error) {
        console.error('Impersonation error:', error.response?.data);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to impersonate business.',
        });
        return false;
    }
};

window.switchBackToAdmin = async function () {
    try {
        const response = await businessesService.post(`/businesses/${window.currentBusinessSlug}/switch-back`, {});

        if (response.redirect_url) {
            window.location.href = response.redirect_url;
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: response.message || 'Switched back successfully.',
            });
        }
        return true;
    } catch (error) {
        console.error('Switch back error:', error.response?.data);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to switch back.',
        });
        return false;
    }
};

window.verifyBusiness = async function (btn, businessSlug) {
    btn = $(btn);
    bootstrap.Modal.getOrCreateInstance(document.getElementById(`remarksModal-${businessSlug}`)).show();
    window.currentBusinessSlugForAction = businessSlug;
    window.currentAction = 'verify';
};

window.deactivateBusiness = async function (btn, businessSlug) {
    btn = $(btn);
    bootstrap.Modal.getOrCreateInstance(document.getElementById(`remarksModal-${businessSlug}`)).show();
    window.currentBusinessSlugForAction = businessSlug;
    window.currentAction = 'deactivate';
};

window.submitRemarks = async function (businessSlug) {
    const remarks = $(`#remarks-${businessSlug}`).val();
    if (!remarks.trim()) {
        Swal.fire('Error', 'Remarks are required.', 'error');
        return;
    }

    // Guard against rapid repeat clicks firing multiple verify/deactivate
    // requests (each one sends an email and writes an activity log entry -
    // without this, five clicks means five emails).
    const submitBtn = document.querySelector(`#remarksModal-${businessSlug} .btn-primary`);
    if (submitBtn?.disabled) {
        return;
    }
    if (submitBtn) {
        submitBtn.disabled = true;
    }

    const formData = new FormData();
    formData.append('remarks', remarks);

    const action = window.currentAction === 'verify' ? 'verify' : 'deactivate';
    const url = `/businesses/${window.currentBusinessSlug}/clients/${businessSlug}/${action}`;

    try {
        // requestClient directly, not businessesService - its post()
        // unwraps to response.data only and silently drops `message`.
        const response = await requestClient.post(url, formData);
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message,
        });
        bootstrap.Modal.getInstance(document.getElementById(`remarksModal-${businessSlug}`))?.hide();
        getClients();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || `Failed to ${action} business.`,
        });
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }
};

window.requestAccess = async function (btn) {
    btn = $(btn);
    btn_loader(btn, true);

    const email = $('#email').val();

    try {
        const response = await requestClient.post(`/businesses/${window.currentBusinessSlug}/access/request`, { email });
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message || 'Access request sent.',
        });
        $('#requestAccessForm')[0].reset();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to send access request.',
        });
    } finally {
        btn_loader(btn, false);
    }
};

window.grantAccess = async function (btn, requestId) {
    btn = $(btn);
    btn_loader(btn, true);

    const role = $(`#role-${requestId}`).val();

    try {
        const response = await requestClient.post(`/businesses/${window.currentBusinessSlug}/access/grant`, { request_id: requestId, role });
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message || 'Access granted.',
        });
        btn.closest('tr').remove();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to grant access.',
        });
    } finally {
        btn_loader(btn, false);
    }
};

window.assignModules = async function (btn, businessSlug, confirmClearAll = false) {
    btn = $(btn);
    btn_loader(btn, true);

    const form = document.getElementById("modulesForm-" + businessSlug);

    // The clients table can be re-rendered from under an open modal by an
    // unrelated action (verify/deactivate both call getClients() on
    // success) - if that just happened, this modal's own form node was
    // replaced too and this lookup would otherwise silently submit
    // whatever stale/empty state is left, reporting "success" either way.
    if (!form) {
        btn_loader(btn, false);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'This dialog is out of date (the list was refreshed) - please reopen it and try again.',
        });
        return;
    }

    const formData = new FormData(form);
    if (confirmClearAll) {
        formData.set('confirm_clear_all', '1');
    }

    try {
        const response = await requestClient.post(`/businesses/${window.currentBusinessSlug}/clients/${businessSlug}/modules/assign`, formData);
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message,
        });
        bootstrap.Modal.getInstance(document.getElementById(`modulesModal-${businessSlug}`))?.hide();
    } catch (error) {
        // The server asks for explicit confirmation before clearing every
        // module a business already has (see ClientController::
        // assignModules()) - offer that confirmation here instead of
        // just showing the rejection and leaving the admin stuck.
        if (error.status === 400 && !confirmClearAll) {
            const { isConfirmed } = await Swal.fire({
                icon: 'warning',
                title: 'Remove all modules?',
                text: error.message || 'No modules were selected - this will remove every module from this business.',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove all',
            });
            if (isConfirmed) {
                btn_loader(btn, false);
                return window.assignModules(btn, businessSlug, true);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to assign modules.',
            });
        }
    } finally {
        btn_loader(btn, false);
    }
};
