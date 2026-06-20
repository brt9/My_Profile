const root = document.documentElement;

window.loadTelemetryChart = async () => {
    const { default: Chart } = await import('chart.js/auto');

    return Chart;
};
const themeButton = document.querySelector('[data-theme-toggle]');
const menuButton = document.querySelector('[data-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

const applyTheme = (theme) => {
    root.classList.toggle('dark', theme === 'dark');
    root.dataset.theme = theme;
    themeButton?.setAttribute('aria-label', theme === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
};

applyTheme(localStorage.getItem('portfolio-theme') ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

themeButton?.addEventListener('click', () => {
    const nextTheme = root.classList.contains('dark') ? 'light' : 'dark';
    localStorage.setItem('portfolio-theme', nextTheme);
    applyTheme(nextTheme);
});

const closeMenu = () => {
    mobileMenu?.setAttribute('hidden', '');
    menuButton?.setAttribute('aria-expanded', 'false');
};

menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!isOpen));
    mobileMenu?.toggleAttribute('hidden', isOpen);
});

mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

document.querySelectorAll('[data-current-year]').forEach((node) => {
    node.textContent = String(new Date().getFullYear());
});

const weatherCard = document.querySelector('[data-weather-card]');
const locateButton = weatherCard?.querySelector('[data-weather-locate]');
const weatherStatus = weatherCard?.querySelector('[data-weather-status]');

const weatherText = (selector, value) => {
    const node = weatherCard?.querySelector(selector);
    if (node) node.textContent = value;
};

const updateWeather = (weather) => {
    weatherText('[data-weather-title]', `Clima em ${weather.label ?? 'sua localização'}`);
    weatherText('[data-weather-icon]', weather.emoji ?? '⛅');
    weatherText('[data-weather-temp]', Number.isFinite(Number(weather.temp)) ? `${Math.round(Number(weather.temp))}°` : '—');
    weatherText('[data-weather-condition]', weather.condition ?? 'Tempo variável');
    weatherText('[data-weather-origin]', `${weather.origin ?? 'Localização autorizada no navegador'}. Coordenadas não são armazenadas.`);
    weatherText('[data-weather-feels]', Number.isFinite(Number(weather.feels_like)) ? `${Math.round(Number(weather.feels_like))}°` : '—');
    weatherText('[data-weather-humidity]', Number.isFinite(Number(weather.humidity)) ? `${weather.humidity}%` : '—');
    weatherText('[data-weather-wind]', Number.isFinite(Number(weather.wind_kmh)) ? `${weather.wind_kmh} km/h` : '—');
};

locateButton?.addEventListener('click', () => {
    if (!navigator.geolocation) {
        weatherText('[data-weather-status]', 'Geolocalização não suportada. Mantendo a localização atual.');
        return;
    }

    locateButton.disabled = true;
    weatherText('[data-weather-status]', 'Aguardando sua permissão de localização…');

    navigator.geolocation.getCurrentPosition(async ({ coords }) => {
        try {
            const response = await fetch(weatherCard.dataset.weatherEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ latitude: coords.latitude, longitude: coords.longitude }),
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'available') throw new Error('weather unavailable');
            updateWeather(payload.data);
            weatherText('[data-weather-status]', 'Clima atualizado com a localização autorizada.');
        } catch {
            weatherText('[data-weather-status]', 'Não foi possível atualizar o clima. Mantendo a localização anterior.');
        } finally {
            locateButton.disabled = false;
        }
    }, (error) => {
        const messages = {
            1: 'Permissão negada. Mantendo a localização anterior.',
            2: 'Localização indisponível. Mantendo a localização anterior.',
            3: 'Tempo de localização esgotado. Mantendo a localização anterior.',
        };
        weatherText('[data-weather-status]', messages[error.code] ?? 'Não foi possível obter sua localização.');
        locateButton.disabled = false;
    }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 });
});

document.addEventListener('error', (event) => {
    const image = event.target;
    if (!(image instanceof HTMLImageElement) || !image.matches('[data-game-image]')) return;

    const fallback = image.dataset.fallbackSrc;
    if (fallback && image.dataset.fallbackAttempted !== 'true' && image.src !== fallback) {
        image.dataset.fallbackAttempted = 'true';
        image.src = fallback;
        return;
    }

    image.closest('[data-game-cover]')?.classList.add('is-error');
    image.remove();
}, true);
