// Minimal PWA service worker - Krest-branded, deliberately conservative.
// intercepts same-origin GET requests to actual static asset paths
// (/assets, /media, /build); everything else (every dynamic page and
// every AJAX/data endpoint under business/*, employees/*, myaccount/*,
// leave-entitlements/*, etc.) falls straight through untouched, so
// nothing the app fetches can ever be served stale from a cache.
const CACHE_VERSION = 'krest-hrm-static-v1';
const STATIC_PATH_PATTERNS = [/^\/assets\//, /^\/media\//, /^\/build\//];

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

function isStaticAsset(url) {
    return STATIC_PATH_PATTERNS.some((pattern) => pattern.test(url.pathname));
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || !isStaticAsset(url)) {
        // Not a static asset - let the browser handle it normally
        // (network-only, no caching, no interception).
        return;
    }

    event.respondWith(
        caches.open(CACHE_VERSION).then((cache) =>
            cache.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }
                return fetch(request).then((response) => {
                    if (response.ok) {
                        cache.put(request, response.clone());
                    }
                    return response;
                });
            })
        )
    );
});
