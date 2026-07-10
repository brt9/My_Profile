export function registerPwa() {
    if (!('serviceWorker' in navigator)) return;

    const isLocalDevelopment = ['127.0.0.1', 'localhost', '::1'].includes(window.location.hostname)
        || document.querySelector('meta[name="app-debug"]')?.content === 'true';

    if (isLocalDevelopment) {
        window.addEventListener('load', () => {
            void navigator.serviceWorker.getRegistrations()
                .then((registrations) => Promise.all(registrations.map((registration) => registration.unregister())))
                .then(() => ('caches' in window ? caches.keys() : []))
                .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
                .catch(() => undefined);
        }, { once: true });

        return;
    }

    if (!import.meta.env.PROD) return;

    window.addEventListener('load', () => {
        void navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' })
            .then((registration) => registration.update())
            .catch(() => undefined);
    }, { once: true });
}
