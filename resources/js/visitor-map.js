import {
    geoGraticule10,
    geoNaturalEarth1,
    geoPath,
} from 'd3';
import { feature } from 'topojson-client';
import worldLandTopology from 'world-atlas/land-110m.json';

const WORLD_LAND = feature(worldLandTopology, worldLandTopology.objects.land);

const cssColor = (name, fallback) => (
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback
);

export const registerVisitorMap = () => {
    const shell = document.querySelector('[data-visitor-map]');
    const canvas = shell?.querySelector('[data-visitor-map-canvas]');
    const context = canvas?.getContext('2d');
    const loading = shell?.querySelector('[data-visitor-map-loading]');
    const total = shell?.querySelector('[data-visitor-map-total]');
    const regions = shell?.querySelector('[data-visitor-map-regions]');
    const status = shell?.querySelector('[data-visitor-map-status]');

    if (!shell || !canvas || !context) return { reload: async () => {} };

    let points = [];
    let resizeFrame = null;

    const draw = () => {
        const bounds = canvas.getBoundingClientRect();
        const width = Math.max(280, Math.round(bounds.width));
        const height = Math.max(220, Math.round(bounds.height));
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const projection = geoNaturalEarth1().fitExtent([[18, 22], [width - 18, height - 22]], { type: 'Sphere' });
        const path = geoPath(projection, context);

        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        context.setTransform(dpr, 0, 0, dpr, 0, 0);
        context.clearRect(0, 0, width, height);

        const text = cssColor('--text', '#f4f2ed');
        const muted = cssColor('--muted', '#aaa6a0');
        const surface = cssColor('--surface-soft', '#202027');
        const accent = cssColor('--accent', '#9a82ff');
        const warm = cssColor('--warm', '#ff9a7f');

        context.beginPath();
        path({ type: 'Sphere' });
        context.fillStyle = surface;
        context.globalAlpha = 0.72;
        context.fill();
        context.globalAlpha = 0.24;
        context.strokeStyle = text;
        context.lineWidth = 1;
        context.stroke();

        context.beginPath();
        path(geoGraticule10());
        context.globalAlpha = 0.12;
        context.strokeStyle = muted;
        context.lineWidth = 0.7;
        context.stroke();

        context.beginPath();
        path(WORLD_LAND);
        context.globalAlpha = 0.22;
        context.fillStyle = text;
        context.fill();
        context.globalAlpha = 0.28;
        context.strokeStyle = text;
        context.lineWidth = 0.7;
        context.stroke();

        const largest = Math.max(1, ...points.map((point) => Number(point.visitors) || 1));
        points.forEach((point) => {
            const projected = projection([Number(point.longitude), Number(point.latitude)]);
            if (!projected) return;

            const count = Math.max(1, Number(point.visitors) || 1);
            const radius = 4.5 + Math.sqrt(count / largest) * 7;
            const glow = context.createRadialGradient(projected[0], projected[1], 1, projected[0], projected[1], radius * 2.6);
            glow.addColorStop(0, `${accent}cc`);
            glow.addColorStop(0.38, `${warm}8f`);
            glow.addColorStop(1, `${warm}00`);

            context.globalAlpha = 1;
            context.beginPath();
            context.arc(projected[0], projected[1], radius * 2.6, 0, Math.PI * 2);
            context.fillStyle = glow;
            context.fill();

            context.beginPath();
            context.arc(projected[0], projected[1], radius * 0.55, 0, Math.PI * 2);
            context.fillStyle = accent;
            context.fill();
            context.strokeStyle = surface;
            context.lineWidth = 2;
            context.stroke();
        });

        context.globalAlpha = 1;
    };

    const queueDraw = () => {
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(draw);
    };

    const reload = async () => {
        if (!shell.dataset.visitorMapEndpoint) return;
        loading?.removeAttribute('hidden');

        try {
            const response = await fetch(shell.dataset.visitorMapEndpoint, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'available') throw new Error('map unavailable');

            points = Array.isArray(payload.data?.points) ? payload.data.points : [];
            if (total) total.textContent = Number(payload.data?.total_visitors || 0).toLocaleString('pt-BR');
            if (regions) regions.textContent = Number(payload.data?.regions || 0).toLocaleString('pt-BR');
            canvas.setAttribute(
                'aria-label',
                `Mapa-múndi com ${payload.data?.total_visitors || 0} visitantes em ${payload.data?.regions || 0} regiões aproximadas`,
            );
            queueDraw();
        } catch {
            if (status) status.textContent = 'O mapa de visitantes está temporariamente indisponível.';
        } finally {
            loading?.setAttribute('hidden', '');
        }
    };

    const observer = new ResizeObserver(queueDraw);
    observer.observe(canvas);
    window.addEventListener('visitor-map:reload', reload);
    window.addEventListener('pagehide', () => {
        observer.disconnect();
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        window.removeEventListener('visitor-map:reload', reload);
    }, { once: true });

    void reload();

    return { reload };
};
