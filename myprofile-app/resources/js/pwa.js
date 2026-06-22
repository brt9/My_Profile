export function registerPwa() {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator)) return;

    window.addEventListener('load', () => {
        void navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => undefined);
    }, { once: true });
}
