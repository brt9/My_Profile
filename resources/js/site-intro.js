const INTRO_STORAGE_KEY = 'portfolio-intro-seen';
const INTRO_TITLE = 'pedrofelipe.dev';
const SCRAMBLE_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<>/{}[]#$%&*';
const RAIN_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&*+-=[]{}|;:,.<>?/\\';

const storageHasIntro = () => {
    try {
        return window.sessionStorage.getItem(INTRO_STORAGE_KEY) === 'true';
    } catch {
        return false;
    }
};

const rememberIntro = () => {
    try {
        window.sessionStorage.setItem(INTRO_STORAGE_KEY, 'true');
    } catch {
        // The intro remains functional when session storage is restricted.
    }
};

const randomCharacter = (characters) => (
    characters[Math.floor(Math.random() * characters.length)]
);

export const registerSiteIntro = () => {
    const intro = document.querySelector('[data-site-intro]');
    const canvas = intro?.querySelector('[data-site-intro-canvas]');
    const title = intro?.querySelector('[data-site-intro-title]');
    const status = intro?.querySelector('[data-site-intro-status]');
    const progressBar = intro?.querySelector('[data-site-intro-progress]');
    const context = canvas?.getContext('2d');

    if (!intro || !canvas || !title || !context) return false;

    if (storageHasIntro()) {
        intro.remove();
        return false;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const root = document.documentElement;
    const body = document.body;
    let rainFrame = null;
    let scrambleFrame = null;
    let resizeFrame = null;
    let finished = false;
    let drops = [];
    let rainFontSize = 14;
    let lastRainFrame = 0;
    let backgroundReady = false;
    let sequenceReady = false;
    let completionTimer = null;
    let readinessTimeout = null;

    const resizeCanvas = () => {
        const width = window.innerWidth;
        const height = window.innerHeight;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        rainFontSize = width < 760 ? 12 : 14;
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        context.setTransform(dpr, 0, 0, dpr, 0, 0);
        drops = Array.from(
            { length: Math.ceil(width / rainFontSize) },
            () => Math.floor(Math.random() * Math.ceil(height / rainFontSize)),
        );
        context.fillStyle = '#09090d';
        context.fillRect(0, 0, width, height);
    };

    const drawRain = (time) => {
        rainFrame = window.requestAnimationFrame(drawRain);
        if (time - lastRainFrame < 32) return;
        lastRainFrame = time;

        const width = window.innerWidth;
        const height = window.innerHeight;

        context.fillStyle = 'rgba(9, 9, 13, 0.13)';
        context.fillRect(0, 0, width, height);
        context.font = `${rainFontSize}px ui-monospace, SFMono-Regular, Consolas, monospace`;
        context.textAlign = 'center';

        drops.forEach((drop, column) => {
            const active = Math.random() < 0.045;
            context.fillStyle = active ? '#b7ffca' : '#35c96a';
            context.globalAlpha = active ? 0.95 : 0.24 + Math.random() * 0.22;
            context.fillText(
                randomCharacter(RAIN_CHARACTERS),
                column * rainFontSize + rainFontSize / 2,
                drop * rainFontSize,
            );

            drops[column] = drop * rainFontSize > height && Math.random() > 0.975
                ? 0
                : drop + 1;
        });

        context.globalAlpha = 1;
    };

    const finish = () => {
        if (finished) return;
        finished = true;
        if (completionTimer) window.clearTimeout(completionTimer);
        if (readinessTimeout) window.clearTimeout(readinessTimeout);
        rememberIntro();
        intro.classList.add('is-exiting');
        body.classList.remove('intro-active');
        root.classList.remove('intro-active');
        window.removeEventListener('keydown', handleSkipKey);
        window.removeEventListener('resize', queueResize);
        window.removeEventListener('site-background:ready', handleBackgroundReady);
        window.dispatchEvent(new CustomEvent('site-intro:complete'));

        window.setTimeout(() => {
            if (rainFrame) window.cancelAnimationFrame(rainFrame);
            if (scrambleFrame) window.cancelAnimationFrame(scrambleFrame);
            intro.remove();
        }, 560);
    };

    const finishWhenReady = () => {
        if (!sequenceReady || !backgroundReady || finished || completionTimer) return;
        if (status) status.textContent = 'BEM-VINDO';
        completionTimer = window.setTimeout(finish, 180);
    };

    const handleBackgroundReady = () => {
        backgroundReady = true;
        finishWhenReady();
    };

    const handleSkipKey = (event) => {
        if (event.key === 'Escape' || event.key === 'Enter' || event.key === ' ') finish();
    };

    const startScramble = () => {
        if (reducedMotion) {
            title.textContent = INTRO_TITLE;
            sequenceReady = true;
            if (status) {
                status.textContent = backgroundReady
                    ? 'BEM-VINDO'
                    : 'PREPARANDO EXPERIÊNCIA';
            }
            if (progressBar) progressBar.style.transform = 'scaleX(1)';
            finishWhenReady();
            return;
        }

        const startedAt = performance.now();
        let lastShuffleAt = 0;
        let finalRevealStartedAt = null;
        let finalRevealStartProgress = 0;

        const updateTitle = (time) => {
            const elapsed = time - startedAt;
            const baseProgress = Math.min(0.78, (elapsed / 1350) * 0.78);

            if (backgroundReady && elapsed >= 780 && finalRevealStartedAt === null) {
                finalRevealStartedAt = time;
                finalRevealStartProgress = baseProgress;
            }

            const finalRevealProgress = finalRevealStartedAt === null
                ? 0
                : Math.min(1, (time - finalRevealStartedAt) / 420);
            const progress = finalRevealStartedAt === null
                ? baseProgress
                : finalRevealStartProgress + (1 - finalRevealStartProgress) * finalRevealProgress;
            const resolvedCharacters = Math.floor((progress ** 0.82) * INTRO_TITLE.length);

            if (time - lastShuffleAt >= 82 || progress === 1) {
                lastShuffleAt = time;
                title.textContent = [...INTRO_TITLE].map((character, index) => {
                    if (character === '.' || index < resolvedCharacters) return character;
                    return randomCharacter(SCRAMBLE_CHARACTERS);
                }).join('');
            }

            if (progressBar) {
                progressBar.style.transform = `scaleX(${progress})`;
            }

            if (status) {
                status.textContent = finalRevealStartedAt !== null
                    ? 'FINALIZANDO'
                    : progress < 0.32
                    ? 'INICIALIZANDO'
                    : progress < 0.7
                        ? 'CARREGANDO INTERFACE'
                        : 'SINCRONIZANDO CENÁRIO';
            }

            if (progress < 1) {
                scrambleFrame = window.requestAnimationFrame(updateTitle);
                return;
            }

            title.textContent = INTRO_TITLE;
            sequenceReady = true;
            if (status) {
                status.textContent = backgroundReady
                    ? 'BEM-VINDO'
                    : 'PREPARANDO EXPERIÊNCIA';
            }
            finishWhenReady();
        };

        scrambleFrame = window.requestAnimationFrame(updateTitle);
    };

    const queueResize = () => {
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(resizeCanvas);
    };

    intro.classList.add('is-active');
    body.classList.add('intro-active');
    root.classList.add('intro-active');
    resizeCanvas();
    if (!reducedMotion) rainFrame = window.requestAnimationFrame(drawRain);
    window.addEventListener('site-background:ready', handleBackgroundReady);
    readinessTimeout = window.setTimeout(() => {
        backgroundReady = true;
        finishWhenReady();
    }, 2800);
    startScramble();

    intro.addEventListener('click', finish, { once: true });
    window.addEventListener('keydown', handleSkipKey);
    window.addEventListener('resize', queueResize, { passive: true });
    window.addEventListener('pagehide', () => {
        if (rainFrame) window.cancelAnimationFrame(rainFrame);
        if (scrambleFrame) window.cancelAnimationFrame(scrambleFrame);
        if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
        window.removeEventListener('resize', queueResize);
    }, { once: true });

    return true;
};
