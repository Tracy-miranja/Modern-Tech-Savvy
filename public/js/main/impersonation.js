function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

async function loadSwitchableBusinesses() {
    const currentSlug = window.currentBusinessSlug;
    const res = await fetch(`/businesses/${currentSlug}/clients/managed-businesses`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const json = await res.json();
    const select = document.getElementById('switchClientSelect');
    if (!select || !json.data) return;

    json.data.forEach(biz => {
        const opt = document.createElement('option');
        opt.value = biz.slug;
        opt.textContent = biz.company_name;
        select.appendChild(opt);
    });
}

document.getElementById('switchClientSelect')?.addEventListener('change', async function () {
    if (!this.value) return;
    const currentSlug = window.currentBusinessSlug;

    const res = await fetch(`/businesses/${currentSlug}/clients/${this.value}/impersonate`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data?.redirect_url) window.location.href = json.data.redirect_url;
});

// Peer "Switch Business" (app-header.blade.php): moves between businesses
// the CURRENT account is itself attached to (owner or employee). Distinct
// from impersonateBusiness() above, which is a super-admin stepping into an
// arbitrary client's business.
window.switchBusiness = async function (businessSlug) {
    const res = await fetch(`/businesses/switch/${businessSlug}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data?.redirect_url) {
        window.location.href = json.data.redirect_url;
    } else if (json.message) {
        alert(json.message);
    }
};

// FIX: explicitly attach to window so inline onclick="" can find it
window.switchBackToAdmin = async function () {
    const currentSlug = window.currentBusinessSlug;

    const res = await fetch(`/businesses/${currentSlug}/switch-back`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data?.redirect_url) window.location.href = json.data.redirect_url;
};

// Reverses EmployeeController::loginAsEmployee() - restores the admin's own
// Auth session (see the "Logged in as ..." banner in layouts/app.blade.php).
window.stopImpersonatingEmployee = async function () {
    const res = await fetch(`/employees/stop-impersonating`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.data?.redirect_url) window.location.href = json.data.redirect_url;
};

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('impersonation-banner')) {
        loadSwitchableBusinesses();
    }
});
