const CACHE_NAME = 'smart-elogbook-static-v2';
const MAX_STATIC_ASSETS = 50;

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key.startsWith('smart-elogbook-static-') && key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Page loads and redirects go straight to the network, untouched.
    if (request.mode === 'navigate') return;

    const isStaticBuildAsset = url.pathname.startsWith('/build/')
        && ['script', 'style', 'font', 'image'].includes(request.destination);
    if (!isStaticBuildAsset) return;

    event.respondWith((async () => {
        const cache = await caches.open(CACHE_NAME);
        const cached = await cache.match(request);
        if (cached) return cached;

        const response = await fetch(request);
        const contentType = response.headers.get('content-type') || '';
        const isExpectedAssetType = request.destination === 'script'
            ? /(?:java|ecma)script/i.test(contentType)
            : request.destination === 'style'
                ? /text\/css/i.test(contentType)
                : request.destination === 'font'
                    ? /font|woff|ttf|otf/i.test(contentType)
                    : /^image\//i.test(contentType);

        if (response.ok && !response.redirected && isExpectedAssetType) {
            await cache.put(request, response.clone());
            const keys = await cache.keys();
            const assets = keys.filter((key) => new URL(key.url).pathname.startsWith('/build/'));
            await Promise.all(assets.slice(0, Math.max(0, assets.length - MAX_STATIC_ASSETS)).map((key) => cache.delete(key)));
        }
        return response;
    })());
});