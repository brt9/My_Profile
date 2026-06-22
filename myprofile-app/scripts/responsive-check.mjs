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
    && telemetryReport.unsupportedValue === 'Sensor indisponível'
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

const githubYearEvaluation = await command('Runtime.evaluate', {
    expression: `(async () => {
        const element = document.querySelector('[data-github-activity]');
        const calendar = window.Alpine?.$data(element);
        if (!calendar) return { available: false };
        const initialYear = calendar.calendar.year;
        await calendar.previousYear();
        for (let attempt = 0; attempt < 80 && calendar.loading; attempt += 1) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        const months = [...element.querySelectorAll('.github-calendar-months span')]
            .map(item => item.textContent.trim())
            .filter(Boolean);
        return {
            available: true,
            initialYear,
            selectedYear: calendar.calendar.year,
            total: calendar.calendar.total,
            cells: element.querySelectorAll('.github-calendar-week .github-contribution-day').length,
            hasJanuary: months.includes('Jan'),
            hasDecember: months.includes('Dez'),
            hasError: Boolean(calendar.error),
        };
    })()`,
    awaitPromise: true,
    returnByValue: true,
});
const githubYearReport = githubYearEvaluation.result.value;
const githubYearValid = githubYearReport.available
    && githubYearReport.selectedYear === githubYearReport.initialYear - 1
    && githubYearReport.total > 0
    && githubYearReport.cells >= 365
    && githubYearReport.hasJanuary
    && githubYearReport.hasDecember
    && !githubYearReport.hasError;
failed ||= !githubYearValid;
console.log(`${githubYearValid ? 'PASS' : 'FAIL'} GitHub yearly navigation`, githubYearReport);

const calendarModalEvaluation = await command('Runtime.evaluate', {
    expression: `(async () => {
        const trigger = document.querySelector('[data-calendar-view-panel="week"] [data-calendar-event-open]');
        const dialog = document.querySelector('[data-calendar-event-dialog]');
        if (!trigger || !(dialog instanceof HTMLDialogElement)) return { available: false };

        trigger.click();
        await new Promise(resolve => setTimeout(resolve, 0));
        const dialogRect = dialog.getBoundingClientRect();
        const report = {
            available: true,
            modalOpen: dialog.open,
            horizontalCenterDelta: Math.round(Math.abs((dialogRect.left + dialogRect.width / 2) - window.innerWidth / 2)),
            verticalCenterDelta: Math.round(Math.abs((dialogRect.top + dialogRect.height / 2) - window.innerHeight / 2)),
            title: dialog.querySelector('[data-calendar-dialog-title]')?.textContent.trim() ?? '',
            date: dialog.querySelector('[data-calendar-dialog-date]')?.textContent.trim() ?? '',
            time: dialog.querySelector('[data-calendar-dialog-time]')?.textContent.trim() ?? '',
            duration: dialog.querySelector('[data-calendar-dialog-duration]')?.textContent.trim() ?? '',
            source: dialog.querySelector('[data-calendar-dialog-source]')?.textContent.trim() ?? '',
        };
        dialog.close();
        await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
        report.closed = !dialog.open;
        report.focusRestored = document.activeElement === trigger;

        return report;
    })()`,
    awaitPromise: true,
    returnByValue: true,
});
const calendarModalReport = calendarModalEvaluation.result.value;
const calendarModalValid = calendarModalReport.available
    && calendarModalReport.modalOpen
    && calendarModalReport.horizontalCenterDelta <= 8
    && calendarModalReport.verticalCenterDelta <= 1
    && calendarModalReport.title.length > 0
    && calendarModalReport.date.length > 0
    && calendarModalReport.time.length > 0
    && calendarModalReport.duration.length > 0
    && calendarModalReport.source.length > 0
    && calendarModalReport.closed
    && calendarModalReport.focusRestored;
failed ||= !calendarModalValid;
console.log(`${calendarModalValid ? 'PASS' : 'FAIL'} calendar event modal`, calendarModalReport);

const pwaEvaluation = await command('Runtime.evaluate', {
    expression: `(async () => {
        const manifestLink = document.querySelector('link[rel="manifest"]');
        const manifest = manifestLink
            ? await fetch(manifestLink.href).then(response => response.json())
            : null;
        const registration = await Promise.race([
            navigator.serviceWorker?.ready ?? Promise.resolve(null),
            new Promise(resolve => setTimeout(() => resolve(null), 5000)),
        ]);

        return {
            hasManifest: Boolean(manifest),
            display: manifest?.display ?? null,
            iconSizes: manifest?.icons?.map(icon => icon.sizes) ?? [],
            hasWorker: Boolean(registration?.active),
            scope: registration?.scope ?? null,
        };
    })()`,
    awaitPromise: true,
    returnByValue: true,
});
const pwaReport = pwaEvaluation.result.value;
const pwaValid = pwaReport.hasManifest
    && pwaReport.display === 'standalone'
    && pwaReport.iconSizes.includes('192x192')
    && pwaReport.iconSizes.includes('512x512')
    && pwaReport.hasWorker
    && pwaReport.scope === 'http://127.0.0.1:8085/';
failed ||= !pwaValid;
console.log(`${pwaValid ? 'PASS' : 'FAIL'} PWA manifest and service worker`, pwaReport);

if (process.env.RESPONSIVE_SCREENSHOT) {
    const selector = process.env.RESPONSIVE_SCREENSHOT_SELECTOR ?? '#estudos';
    if (process.env.RESPONSIVE_THEME === 'dark') {
        await command('Runtime.evaluate', {
            expression: "document.documentElement.classList.add('dark')",
        });
        await sleep(250);
    }
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
