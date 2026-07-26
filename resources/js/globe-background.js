import {
    geoContains,
    geoDistance,
    geoGraticule10,
    geoOrthographic,
    geoPath,
    timer,
} from 'd3';
import { feature } from 'topojson-client';
import worldLandTopology from 'world-atlas/land-110m.json';

const WORLD_LAND = feature(worldLandTopology, worldLandTopology.objects.land);
const MATRIX_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*+-=[]{}|;:,.<>?/\\';

const cssColor = (name, fallback) => (
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback
);

const createLandDots = (land) => {
    const dots = [];

    for (let latitude = -84; latitude <= 84; latitude += 3) {
        for (let longitude = -180; longitude < 180; longitude += 3) {
            if (geoContains(land, [longitude, latitude])) {
                dots.push([longitude, latitude]);
            }
        }
    }

    return dots;
};

const randomMatrixCharacter = () => (
    MATRIX_CHARACTERS[Math.floor(Math.random() * MATRIX_CHARACTERS.length)]
);

const createMatrixStrands = (width, height) => {
    const mobile = width < 760;
    const spacing = mobile ? 18 : 24;
    const strands = [];

    for (let x = spacing / 2; x < width; x += spacing) {
        if (Math.random() > (mobile ? 0.78 : 0.72)) continue;

        const fontSize = mobile
            ? 10 + Math.random() * 2
            : 11 + Math.random() * 3;
        const length = 10 + Math.floor(Math.random() * 14);

        strands.push({
            x,
            y: Math.random() * height,
            speed: 14 + Math.random() * 22,
            fontSize,
            length,
            layer: Math.floor(Math.random() * 3),
            characters: Array.from({ length }, randomMatrixCharacter),
        });
    }

    return strands;
};

export const registerGlobeBackground = () => {
    const canvas = document.querySelector('[data-globe-background]');
    const context = canvas?.getContext('2d');

    if (!canvas || !context) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const projection = geoOrthographic().clipAngle(90);
    const path = geoPath(projection, context);
    const rotation = [-28, -12, 0];
    let dimensions = { width: 0, height: 0, radius: 0 };
    const land = WORLD_LAND;
    let landDots = [];
    let rotationTimer = null;
    let resizeFrame = null;
    let idleHandle = null;
    let matrixStrands = [];
    let lastMatrixFrame = performance.now();

    const resize = () => {
        const width = window.innerWidth;
        const height = window.innerHeight;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const radius = width < 760
            ? Math.min(Math.max(width * 0.31, 216), height * 0.61)
            : Math.min(Math.max(width * 0.34, 240), height * 0.68);

        dimensions = { width, height, radius };
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        context.setTransform(dpr, 0, 0, dpr, 0, 0);
        matrixStrands = createMatrixStrands(width, height);
        lastMatrixFrame = performance.now();
        projection
            .scale(radius)
            .translate([width / 2, height / 2])
            .rotate(rotation);
    };

    const drawMatrix = (center, radius, elapsed) => {
        const { width, height } = dimensions;
        const isDark = document.documentElement.classList.contains('dark');
        const matrixColor = isDark ? '#70ff9b' : '#147a43';

        context.save();
        context.beginPath();
        context.rect(0, 0, width, height);
        context.arc(center[0], center[1], radius + 8, 0, Math.PI * 2);
        context.clip('evenodd');
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        context.shadowBlur = 2;
        context.shadowColor = matrixColor;

        matrixStrands.forEach((strand) => {
            if (!reducedMotion.matches) {
                strand.y += strand.speed * elapsed / 1000;

                if (strand.y - strand.length * strand.fontSize > height) {
                    strand.y = -Math.random() * height * 0.45;
                    strand.characters = Array.from({ length: strand.length }, randomMatrixCharacter);
                }

                if (Math.random() < elapsed * 0.00035) {
                    const characterIndex = Math.floor(Math.random() * strand.length);
                    strand.characters[characterIndex] = randomMatrixCharacter();
                }
            }

            context.font = `${strand.fontSize}px ui-monospace, SFMono-Regular, Consolas, monospace`;
            strand.characters.forEach((character, index) => {
                const y = strand.y - index * strand.fontSize;
                if (y < -strand.fontSize || y > height + strand.fontSize) return;

                const trail = 1 - index / strand.length;
                const layerOpacity = 0.16 + strand.layer * 0.045;
                context.globalAlpha = Math.max(0.025, trail * layerOpacity);
                context.fillStyle = index === 0 ? '#d7ffe4' : matrixColor;
                context.fillText(character, strand.x, y);
            });
        });

        context.restore();
    };

    const render = () => {
        const { width, height, radius } = dimensions;
        const center = projection.translate();
        const textColor = cssColor('--text', '#f4f2ed');
        const accentColor = cssColor('--accent', '#9a82ff');
        const surfaceColor = cssColor('--surface', '#18181e');
        const now = performance.now();
        const matrixElapsed = Math.min(64, Math.max(0, now - lastMatrixFrame));
        lastMatrixFrame = now;

        context.clearRect(0, 0, width, height);
        drawMatrix(center, radius, matrixElapsed);

        context.save();
        context.beginPath();
        context.arc(center[0], center[1], radius, 0, Math.PI * 2);
        context.fillStyle = surfaceColor;
        context.globalAlpha = 0.22;
        context.fill();
        context.globalAlpha = 0.42;
        context.strokeStyle = textColor;
        context.lineWidth = 1;
        context.stroke();

        context.beginPath();
        path(geoGraticule10());
        context.globalAlpha = 0.12;
        context.strokeStyle = textColor;
        context.lineWidth = 0.7;
        context.stroke();

        context.beginPath();
        path(land);
        context.globalAlpha = 0.3;
        context.strokeStyle = textColor;
        context.lineWidth = 0.8;
        context.stroke();

        const visibleCenter = [-rotation[0], -rotation[1]];
        context.fillStyle = accentColor;
        context.globalAlpha = 0.58;

        landDots.forEach((dot) => {
            if (geoDistance(dot, visibleCenter) > Math.PI / 2) return;

            const projected = projection(dot);
            if (!projected) return;

            context.beginPath();
            context.arc(projected[0], projected[1], 1.15, 0, Math.PI * 2);
            context.fill();
        });

        context.restore();
    };

    const startRotation = () => {
        rotationTimer?.stop();
        if (reducedMotion.matches) return;

        rotationTimer = timer((elapsed) => {
            if (document.hidden) return;
            rotation[0] = -28 + elapsed * 0.0035;
            projection.rotate(rotation);
            render();
        });
    };

    const queueResize = () => {
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(() => {
            resize();
            render();
        });
    };

    resize();
    render();
    startRotation();

    const prepareLandDots = () => {
        landDots = createLandDots(land);
        render();
    };

    if ('requestIdleCallback' in window) {
        idleHandle = window.requestIdleCallback(prepareLandDots, { timeout: 1200 });
    } else {
        idleHandle = window.setTimeout(prepareLandDots, 0);
    }

    window.addEventListener('resize', queueResize, { passive: true });
    reducedMotion.addEventListener('change', startRotation);

    window.addEventListener('pagehide', () => {
        rotationTimer?.stop();
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        if ('cancelIdleCallback' in window) window.cancelIdleCallback(idleHandle);
        else window.clearTimeout(idleHandle);
        window.removeEventListener('resize', queueResize);
        reducedMotion.removeEventListener('change', startRotation);
    }, { once: true });
};
