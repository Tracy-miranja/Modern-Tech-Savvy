// Registers the static-asset-only service worker (public/sw.js) so the app
// is installable (Chrome/Edge desktop and mobile "Add to Home Screen").
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function (err) {
            console.error('Service worker registration failed:', err);
        });
    });
}
