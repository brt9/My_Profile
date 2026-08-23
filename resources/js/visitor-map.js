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

const clusterProjectedPoints = (points, projection, distance) => {
    const clusters = [];

    points.forEach((point) => {
        const projected = projection([Number(point.longitude), Number(point.latitude)]);
        if (!projected) return;

        const visitors = Math.max(1, Number(point.visitors) || 1);
        const nearby = clusters.find((cluster) => (
            Math.hypot(cluster.x - projected[0], cluster.y - projected[1]) < distance
        ));

        if (!nearby) {
            clusters.push({
                x: projected[0],
                y: projected[1],
                visitors,
                regions: 1,
            });
            return;
        }

        const combinedVisitors = nearby.visitors + visitors;
        nearby.x = ((nearby.x * nearby.visitors) + (projected[0] * visitors)) / combinedVisitors;
        nearby.y = ((nearby.y * nearby.visitors) + (projected[1] * visitors)) / combinedVisitors;
        nearby.visitors = combinedVisitors;
        nearby.regions += 1;
    });

    return clusters;
};

const markerRadius = (visitors) => Math.min(22, 8 + (Math.log2(visitors + 1) * 2.4));

export const registerVisitorMap = () => {
    const shell = document.querySelector('[data-visitor-map]');
    const canvas = shell?.querySelector('[data-visitor-map-canvas]');
    const context = canvas?.getContext('2d');
    const loading = shell?.querySelector('[data-visitor-map-loading]');
    const total = shell?.querySelector('[data-visitor-map-total]');
    const regions = shell?.querySelector('[data-visitor-map-regions]');
    const status = shell?.querySelector('[data-visitor-map-status]');
    const tooltip = shell?.querySelector('[data-visitor-map-tooltip]');

    if (!shell || !canvas || !context) return { reload: async () => {} };

    let points = [];
    let visibleClusters = [];
    let resizeFrame = null;

    const draw = () => {
        tooltip?.setAttribute('hidden', '');

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

        const clusterDistance = width < 520 ? 30 : 34;
        visibleClusters = clusterProjectedPoints(points, projection, clusterDistance)
            .map((cluster) => ({ ...cluster, radius: markerRadius(cluster.visitors) }));

        visibleClusters.forEach((cluster) => {
            const radius = cluster.radius;
            const glow = context.createRadialGradient(cluster.x, cluster.y, 1, cluster.x, cluster.y, radius * 2.15);
            glow.addColorStop(0, `${accent}cc`);
            glow.addColorStop(0.38, `${warm}8f`);
            glow.addColorStop(1, `${warm}00`);

            context.globalAlpha = 1;
            context.beginPath();
            context.arc(cluster.x, cluster.y, radius * 2.15, 0, Math.PI * 2);
            context.fillStyle = glow;
            context.fill();

            context.beginPath();
            context.arc(cluster.x, cluster.y, radius, 0, Math.PI * 2);
            context.fillStyle = accent;
            context.fill();
            context.strokeStyle = surface;
            context.lineWidth = 2;
            context.stroke();

            if (cluster.visitors > 1) {
                context.fillStyle = surface;
                context.font = `800 ${cluster.visitors > 999 ? 9 : 11}px system-ui, sans-serif`;
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                const label = cluster.visitors > 999 ? '999+' : cluster.visitors.toLocaleString('pt-BR');
                context.fillText(label, cluster.x, cluster.y + 0.5);
            }
        });

        context.globalAlpha = 1;
    };

    const queueDraw = () => {
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(draw);
    };

    const hideTooltip = () => {
        tooltip?.setAttribute('hidden', '');
        canvas.style.cursor = '';
    };

    const showClusterAt = (clientX, clientY) => {
        if (!tooltip) return;

        const bounds = canvas.getBoundingClientRect();
        const x = clientX - bounds.left;
        const y = clientY - bounds.top;
        const cluster = visibleClusters.find((item) => (
            Math.hypot(item.x - x, item.y - y) <= item.radius + 5
        ));

        if (!cluster) {
            hideTooltip();
            return;
        }

        const visitorLabel = cluster.visitors === 1 ? '1 visitante' : `${cluster.visitors.toLocaleString('pt-BR')} visitantes`;
        const regionLabel = cluster.regions === 1 ? '1 região aproximada' : `${cluster.regions} regiões próximas`;
        tooltip.textContent = `${visitorLabel} · ${regionLabel}`;
        const tooltipX = Math.min(bounds.width - 105, Math.max(105, cluster.x));
        tooltip.style.left = `${tooltipX}px`;
        tooltip.style.top = `${cluster.y}px`;
        tooltip.dataset.placement = cluster.y < 62 ? 'below' : 'above';
        tooltip.removeAttribute('hidden');
        canvas.style.cursor = 'pointer';
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
    canvas.addEventListener('pointermove', (event) => showClusterAt(event.clientX, event.clientY));
    canvas.addEventListener('pointerleave', hideTooltip);
    canvas.addEventListener('pointerdown', (event) => showClusterAt(event.clientX, event.clientY));
    window.addEventListener('visitor-map:reload', reload);
    window.addEventListener('pagehide', () => {
        observer.disconnect();
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        window.removeEventListener('visitor-map:reload', reload);
    }, { once: true });

    void reload();

    return { reload };
};
