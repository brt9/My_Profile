import { spawn } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';

const url = process.env.PORTFOLIO_URL ?? 'http://127.0.0.1:8085/';
const chrome = process.env.CHROME_PATH
    ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const profile = await mkdtemp(path.join(os.tmpdir(), 'portfolio-responsive-'));

const child = spawn(chrome, [
    '--headless=new',
    '--disable-gpu',
    '--no-sandbox',
    '--remote-debugging-port=0',
    `--user-data-dir=${profile}`,
    'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function debuggingPort() {
    for (let attempt = 0; attempt < 40; attempt += 1) {
        try {
            const text = await readFile(path.join(profile, 'DevToolsActivePort'), 'utf8');
            return Number(text.split(/\r?\n/)[0]);
        } catch {
            await sleep(100);
        }
    }

    throw new Error('Chrome não iniciou a porta de depuração.');
}

const port = await debuggingPort();
const targets = await fetch(`http://127.0.0.1:${port}/json/list`).then((response) => response.json());
const pageTarget = targets.find((target) => target.type === 'page');
if (!pageTarget) throw new Error('Nenhuma página disponível no Chrome.');
const socket = new WebSocket(pageTarget.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
});

let sequence = 0;
const pending = new Map();

socket.addEventListener('message', ({ data }) => {
    const message = JSON.parse(data);
    if (!message.id || !pending.has(message.id)) return;
    const { resolve, reject } = pending.get(message.id);
    pending.delete(message.id);
    message.error ? reject(new Error(message.error.message)) : resolve(message.result);
});

function command(method, params = {}) {
    sequence += 1;
    socket.send(JSON.stringify({ id: sequence, method, params }));
    return new Promise((resolve, reject) => pending.set(sequence, { resolve, reject }));
}

const sizes = [
    [320, 800],
    [375, 844],
    [390, 844],
    [768, 900],
    [1024, 900],
    [1440, 900],
];

let failed = false;

await command('Page.enable');
await command('Runtime.enable');

for (const [width, height] of sizes) {
    await command('Emulation.setDeviceMetricsOverride', {
        width,
        height,
        deviceScaleFactor: 1,
        mobile: false,
    });
    const navigation = await command('Page.navigate', { url });
    if (navigation.errorText) throw new Error(`Falha ao abrir ${url}: ${navigation.errorText}`);
    for (let attempt = 0; attempt < 40; attempt += 1) {
        const ready = await command('Runtime.evaluate', {
            expression: "document.readyState === 'complete' && Boolean(document.querySelector('[data-menu-toggle]'))",
            returnByValue: true,
        });
        if (ready.result.value === true) break;
        await sleep(250);
    }

    const { result } = await command('Runtime.evaluate', {
        expression: `(() => {
            const menu = document.querySelector('[data-menu-toggle]');
            const footer = document.querySelector('.site-footer');
            const footerLinks = [...document.querySelectorAll('.footer-links a, .footer-meta a')];
            const overflowElements = [...document.querySelectorAll('body *')]
                .filter(element => {
                    const rect = element.getBoundingClientRect();
                    return rect.right > window.innerWidth + 1 || rect.left < -1;
                })
                .slice(0, 6)
                .map(element => {
                    const rect = element.getBoundingClientRect();
                    return {
                        tag: element.tagName,
                        className: element.className,
                        parentClass: element.parentElement?.className ?? '',
                        text: element.textContent?.trim().slice(0, 80) ?? '',
                        left: Math.round(rect.left),
                        right: Math.round(rect.right),
                        width: Math.round(rect.width),
                    };
                });
            return {
                viewport: window.innerWidth,
                pageWidth: document.documentElement.scrollWidth,
                hasHorizontalOverflow: document.documentElement.scrollWidth > window.innerWidth,
                sections: document.querySelectorAll('main section').length,
                menuButtonVisible: menu ? getComputedStyle(menu).display !== 'none' : null,
                footerCentered: footer ? getComputedStyle(footer).textAlign === 'center' : false,
                footerTouchTargets: footerLinks.every(link => link.getBoundingClientRect().height >= 44),
                unsafeFooterLinks: footerLinks.filter(link => link.target === '_blank' && !link.rel.includes('noopener')).length,
                overflowElements
            };
        })()`,
        returnByValue: true,
    });

    const report = result.value;
    const expectedMenu = width <= 900;
    const valid = !report.hasHorizontalOverflow
        && report.sections >= 6
        && report.menuButtonVisible === expectedMenu
        && report.footerCentered
        && report.footerTouchTargets
        && report.unsafeFooterLinks === 0;
    failed ||= !valid;

    console.log(`${valid ? 'PASS' : 'FAIL'} ${width}x${height}`, report);
}

const telemetryEvaluation = await command('Runtime.evaluate', {
    expression: `(async () => {
        const originalFetch = window.fetch;
        const panel = window.telemetryPanel();

        window.fetch = async () => ({
            ok: true,
            json: async () => ({
                status: 'available',
                data: { cpu_load: 0, cpu_temp: null },
                meta: { collected_at: new Date().toISOString(), stale: false }
            })
        });
        await panel.load();
        const zeroValue = panel.value('cpu_load', '% carga');
        const unsupportedValue = panel.value('cpu_temp', '°C');

        window.fetch = async () => ({
            ok: true,
            json: async () => ({ status: 'unavailable', data: null, meta: { stale: true } })
        });
        await panel.load();
        const unavailableMessage = panel.message;

        window.fetch = async () => ({ ok: false, json: async () => ({}) });
        await panel.load();
        const serverErrorMessage = panel.message;

        window.fetch = async () => { throw new DOMException('timeout', 'AbortError'); };
        await panel.load();
        const timeoutMessage = panel.message;

        window.fetch = originalFetch;
        return { zeroValue, unsupportedValue, unavailableMessage, serverErrorMessage, timeoutMessage };
    })()`,
    awaitPromise: true,
    returnByValue: true,
});

const telemetryReport = telemetryEvaluation.result.value;
const telemetryValid = telemetryReport.zeroValue === '0.0% carga'
    && telemetryReport.unsupportedValue === 'Não suportado neste dispositivo'
    && telemetryReport.unavailableMessage === 'Telemetria indisponível no momento.'
    && telemetryReport.serverErrorMessage === 'Telemetria indisponível no momento.'
    && telemetryReport.timeoutMessage === 'Telemetria indisponível no momento.';
failed ||= !telemetryValid;
console.log(`${telemetryValid ? 'PASS' : 'FAIL'} telemetry UI states`, telemetryReport);

const historyEvaluation = await command('Runtime.evaluate', {
    expression: `(async () => {
        const element = document.querySelector('.telemetry-panel');
        const panel = window.Alpine?.$data(element);
        if (!panel) return { available: false };
        const originalFetch = window.fetch;
        window.fetch = async (input) => {
            if (String(input).includes('/api/telemetry/history')) {
                return {
                    ok: true,
                    json: async () => ({
                        status: 'available',
                        data: {
                            points: [
                                { at: '2026-06-20T12:00:00Z', value: 10, samples: 3 },
                                { at: '2026-06-20T12:05:00Z', value: null, samples: 0 },
                                { at: '2026-06-20T12:10:00Z', value: 20, samples: 3 }
                            ],
                            summary: { minimum: 10, average: 15, maximum: 20, samples: 6 }
                        },
                        meta: { unit: '%', gaps_interpolated: false }
                    })
                };
            }
            return originalFetch(input);
        };
        panel.status = 'available';
        panel.data.cpu_load = 10;
        await panel.openHistory(panel.metrics.find(metric => metric.key === 'cpu_load'));
        for (let attempt = 0; attempt < 40 && panel.history.loading; attempt += 1) {
            await new Promise(resolve => setTimeout(resolve, 50));
        }
        const report = {
            available: true,
            modalOpen: panel.history.open,
            chartCreated: Boolean(panel.chart),
            preservesGap: panel.history.points[1]?.value === null,
            tableRows: panel.availableHistoryPoints().length,
            dialogFocused: document.activeElement === panel.$refs.historyDialog
        };
        panel.closeHistory();
        window.fetch = originalFetch;
        return report;
    })()`,
    awaitPromise: true,
    returnByValue: true,
});
const historyReport = historyEvaluation.result.value;
const historyValid = historyReport.available
    && historyReport.modalOpen
    && historyReport.chartCreated
    && historyReport.preservesGap
    && historyReport.tableRows === 2
    && historyReport.dialogFocused;
failed ||= !historyValid;
console.log(`${historyValid ? 'PASS' : 'FAIL'} telemetry history modal`, historyReport);

const gameFallbackEvaluation = await command('Runtime.evaluate', {
    expression: `(() => {
        const cover = document.createElement('div');
        cover.dataset.gameCover = '';
        const image = document.createElement('img');
        image.dataset.gameImage = '';
        image.dataset.fallbackSrc = 'https://example.com/capsule.jpg';
        image.src = 'https://example.com/header.jpg';
        cover.append(image);
        document.body.append(cover);
        image.dispatchEvent(new Event('error'));
        const triedCapsule = image.dataset.fallbackAttempted === 'true' && image.src.includes('capsule.jpg');
        image.dispatchEvent(new Event('error'));
        const localFallback = cover.classList.contains('is-error') && !cover.querySelector('img');
        cover.remove();
        return { triedCapsule, localFallback };
    })()`,
    returnByValue: true,
});
const gameFallbackReport = gameFallbackEvaluation.result.value;
const gameFallbackValid = gameFallbackReport.triedCapsule && gameFallbackReport.localFallback;
failed ||= !gameFallbackValid;
console.log(`${gameFallbackValid ? 'PASS' : 'FAIL'} Steam image fallback`, gameFallbackReport);

const weatherConsentEvaluation = await command('Runtime.evaluate', {
    expression: `(async () => {
        const button = document.querySelector('[data-weather-locate]');
        const status = document.querySelector('[data-weather-status]');
        const original = navigator.geolocation;

        Object.defineProperty(navigator, 'geolocation', {
            configurable: true,
            value: { getCurrentPosition: (_success, error) => error({ code: 1 }) }
        });
        button.click();
        await new Promise(resolve => setTimeout(resolve, 0));
        const denied = status.textContent;

        Object.defineProperty(navigator, 'geolocation', {
            configurable: true,
            value: { getCurrentPosition: (_success, error) => error({ code: 3 }) }
        });
        button.click();
        await new Promise(resolve => setTimeout(resolve, 0));
        const timedOut = status.textContent;

        Object.defineProperty(navigator, 'geolocation', { configurable: true, value: original });
        return { denied, timedOut };
    })()`,
    awaitPromise: true,
    returnByValue: true,
});
const weatherConsentReport = weatherConsentEvaluation.result.value;
const weatherConsentValid = weatherConsentReport.denied.includes('Permissão negada')
    && weatherConsentReport.timedOut.includes('Tempo de localização esgotado');
failed ||= !weatherConsentValid;
console.log(`${weatherConsentValid ? 'PASS' : 'FAIL'} weather consent fallbacks`, weatherConsentReport);

if (process.env.RESPONSIVE_SCREENSHOT) {
    const selector = process.env.RESPONSIVE_SCREENSHOT_SELECTOR ?? '#estudos';
    const sectionEvaluation = await command('Runtime.evaluate', {
        expression: `(() => {
            const element = document.querySelector(${JSON.stringify(selector)});
            if (!element) return null;
            const rect = element.getBoundingClientRect();
            return {
                x: rect.left + window.scrollX,
                y: rect.top + window.scrollY,
                width: rect.width,
                height: rect.height,
            };
        })()`,
        returnByValue: true,
    });
    const clip = sectionEvaluation.result.value;
    if (!clip) throw new Error(`Seletor de captura não encontrado: ${selector}`);
    const capture = await command('Page.captureScreenshot', {
        format: 'png',
        fromSurface: true,
        captureBeyondViewport: true,
        clip: { ...clip, scale: 1 },
    });
    const screenshotPath = path.resolve(process.env.RESPONSIVE_SCREENSHOT);
    await writeFile(screenshotPath, Buffer.from(capture.data, 'base64'));
    console.log(`PASS screenshot ${selector}`, screenshotPath);
}

socket.close();
child.kill();
await new Promise((resolve) => child.once('exit', resolve));
await rm(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });

if (failed) process.exitCode = 1;
