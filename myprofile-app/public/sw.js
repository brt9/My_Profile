const CACHE_VERSION = 'pedrofelipe-pwa-v2';
const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-32.png?v=2',
    '/icons/icon-180.png?v=2',
    '/icons/icon-192.png?v=2',
    '/icons/icon-512.png?v=2',
    '/icons/icon-maskable-512.png?v=2',
];
const PRIVATE_PATH_PREFIXES = [
    '/api/',
    '/admin/',
    '/auth/',
    '/login',
    '/register',
    '/dashboard',
    '/profile',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (PRIVATE_PATH_PREFIXES.some((prefix) => url.pathname.startsWith(prefix))) return;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html')),
        );
        return;
    }

    if (!['font', 'image', 'manifest', 'script', 'style'].includes(request.destination)) return;

    event.respondWith(
        caches.match(request).then((cached) => cached ?? fetch(request).then((response) => {
            if (response.ok) {
                const copy = response.clone();
                void caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
            }

            return response;
        })),
    );
});
