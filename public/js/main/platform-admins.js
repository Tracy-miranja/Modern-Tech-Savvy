import RequestClient from "/js/client/RequestClient.js";

const requestClient = new RequestClient();

// Deliberately NOT going through BusinessesService here - its post()
// unwraps to response.data only (built for callers that just want the
// data payload), which silently drops the `message` field every
// success/error response actually carries. RequestClient itself already
// returns the full {message, data} body, so call it directly.

if (!window.currentBusinessSlug) {
    console.warn("currentBusinessSlug not defined, falling back to 'krest'");
    window.currentBusinessSlug = 'krest';
}

document.getElementById('submitCreatekrestAdminBtn')?.addEventListener('click', async function () {
    const form = document.getElementById('createkrestAdminForm');
    const formData = new FormData(form);

    if (!formData.get('name') || !formData.get('email')) {
        Swal.fire('Error', 'Name and email are required.', 'error');
        return;
    }

    try {
        const response = await requestClient.post(
            `/business/${window.currentBusinessSlug}/platform-admins`,
            formData
        );

        await Swal.fire({
            icon: 'success',
            title: 'krest Admin Created',
            text: response.message,
        });

        window.location.reload();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to create krest admin.',
        });
    }
});

window.revokekrestAdmin = async function (userId, email) {
    const confirmed = await Swal.fire({
        icon: 'warning',
        title: 'Revoke krest-admin access?',
        text: `${email} will lose access to Clients management and impersonation.`,
        showCancelButton: true,
        confirmButtonText: 'Revoke',
    });

    if (!confirmed.isConfirmed) return;

    try {
        const response = await requestClient.post(
            `/business/${window.currentBusinessSlug}/platform-admins/${userId}/revoke`,
            {}
        );

        Swal.fire({ icon: 'success', title: 'Revoked', text: response.message });
        document.getElementById(`krest-admin-row-${userId}`)?.remove();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to revoke krest-admin access.',
        });
    }
};