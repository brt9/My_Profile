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
const visitorWeather = weatherCard?.querySelector('[data-weather-scope="visitor"]');
const locateButton = visitorWeather?.querySelector('[data-weather-locate]');

const weatherText = (selector, value) => {
    const node = visitorWeather?.querySelector(selector);
    if (node) node.textContent = value;
};

const updateWeather = (weather) => {
    const previousSource = visitorWeather?.dataset.weatherSource;
    if (weather.source !== 'browser' || previousSource !== 'ip') {
        weatherText('[data-weather-title]', weather.label ?? 'Sua cidade');
    }
    weatherText('[data-weather-icon]', weather.emoji ?? '⛅');
    weatherText('[data-weather-temp]', Number.isFinite(Number(weather.temp)) ? `${Math.round(Number(weather.temp))}°` : '—');
    weatherText('[data-weather-condition]', weather.condition ?? 'Tempo variável');
    weatherText('[data-weather-origin]', `${weather.origin ?? 'Localização autorizada no navegador'}. Coordenadas não são armazenadas.`);
    weatherText('[data-weather-source]', weather.source === 'browser' ? 'Exata' : 'Aproximada');
    weatherText('[data-weather-feels]', Number.isFinite(Number(weather.feels_like)) ? `${Math.round(Number(weather.feels_like))}°` : '—');
    weatherText('[data-weather-humidity]', Number.isFinite(Number(weather.humidity)) ? `${weather.humidity}%` : '—');
    weatherText('[data-weather-wind]', Number.isFinite(Number(weather.wind_kmh)) ? `${weather.wind_kmh} km/h` : '—');
    if (visitorWeather) visitorWeather.dataset.weatherSource = weather.source ?? 'unknown';
    visitorWeather?.setAttribute('aria-busy', 'false');
};

const loadVisitorWeather = async () => {
    if (!weatherCard?.dataset.weatherVisitorEndpoint) return;

    try {
        const response = await fetch(weatherCard.dataset.weatherVisitorEndpoint, {
            headers: { 'Accept': 'application/json' },
        });
        const payload = await response.json();
        if (!response.ok || payload.status !== 'available') throw new Error('weather unavailable');

        updateWeather(payload.data);
        weatherText(
            '[data-weather-status]',
            payload.data.source === 'fallback'
                ? 'Sua cidade não pôde ser identificada. Você pode autorizar a localização exata.'
                : 'Clima aproximado pela cidade identificada no seu IP.',
        );
    } catch {
        visitorWeather?.setAttribute('aria-busy', 'false');
        weatherText('[data-weather-condition]', 'Clima local indisponível');
        weatherText('[data-weather-status]', 'Não foi possível identificar sua cidade. Você pode autorizar a localização exata.');
    }
};

void loadVisitorWeather();

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

const calendarManager = document.querySelector('[data-calendar-manager]');
const calendarForm = calendarManager?.querySelector('[data-calendar-event-form]');
const calendarStatus = calendarManager?.querySelector('[data-calendar-status]');
const calendarCancel = calendarManager?.querySelector('[data-calendar-cancel]');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const calendarShell = document.querySelector('[data-calendar-shell]');

const setCalendarView = (view) => {
    if (!calendarShell || !['week', 'month'].includes(view)) return;

    calendarShell.querySelectorAll('[data-calendar-view-panel]').forEach((panel) => {
        panel.toggleAttribute('hidden', panel.dataset.calendarViewPanel !== view);
    });
    calendarShell.querySelectorAll('[data-calendar-view-button]').forEach((button) => {
        const active = button.dataset.calendarViewButton === view;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', String(active));
    });

    const title = calendarShell.querySelector('[data-calendar-period-title]');
    const label = calendarShell.querySelector('[data-calendar-period-label]');
    if (title) title.textContent = view === 'month' ? 'Visão mensal' : 'Próximos 7 dias';
    if (label) label.textContent = view === 'month' ? calendarShell.dataset.monthLabel : calendarShell.dataset.weekLabel;
    localStorage.setItem('portfolio-calendar-view', view);
};

calendarShell?.querySelectorAll('[data-calendar-view-button]').forEach((button) => {
    button.addEventListener('click', () => setCalendarView(button.dataset.calendarViewButton));
});

setCalendarView(localStorage.getItem('portfolio-calendar-view') ?? 'week');

const setCalendarStatus = (message, isError = false) => {
    if (!calendarStatus) return;
    calendarStatus.textContent = message;
    calendarStatus.classList.toggle('is-error', isError);
};

const resetCalendarForm = () => {
    if (!(calendarForm instanceof HTMLFormElement)) return;
    calendarForm.reset();
    calendarForm.elements.event_id.value = '';
    calendarCancel?.setAttribute('hidden', '');
    const heading = calendarManager?.querySelector('.calendar-manager-heading h3');
    if (heading) heading.textContent = 'Adicionar à agenda';
};

calendarForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!(calendarForm instanceof HTMLFormElement) || !calendarManager?.dataset.endpoint) return;

    const fields = calendarForm.elements;
    const eventId = fields.event_id.value;
    const payload = {
        title: fields.title.value,
        category: fields.category.value,
        starts_at: fields.starts_at.value,
        ends_at: fields.ends_at.value,
        all_day: fields.all_day.checked,
        sync_google: fields.sync_google?.checked ?? false,
    };
    const submitButton = calendarForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    setCalendarStatus(eventId ? 'Atualizando compromisso…' : 'Salvando compromisso…');

    try {
        const response = await fetch(eventId ? `${calendarManager.dataset.endpoint}/${eventId}` : calendarManager.dataset.endpoint, {
            method: eventId ? 'PUT' : 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) {
            const firstError = Object.values(result.errors ?? {}).flat()[0];
            throw new Error(firstError ?? result.message ?? 'Não foi possível salvar o compromisso.');
        }

        setCalendarStatus('Compromisso salvo no banco local.');
        window.location.reload();
    } catch (error) {
        setCalendarStatus(error instanceof Error ? error.message : 'Não foi possível salvar o compromisso.', true);
    } finally {
        submitButton.disabled = false;
    }
});

calendarManager?.querySelectorAll('[data-calendar-edit]').forEach((button) => {
    button.addEventListener('click', () => {
        const item = button.closest('[data-calendar-item]');
        if (!(calendarForm instanceof HTMLFormElement) || !item) return;

        const fields = calendarForm.elements;
        fields.event_id.value = item.dataset.id ?? '';
        fields.title.value = item.dataset.title ?? '';
        fields.category.value = item.dataset.category ?? 'reuniao';
        fields.starts_at.value = item.dataset.startsAt ?? '';
        fields.ends_at.value = item.dataset.endsAt ?? '';
        fields.all_day.checked = item.dataset.allDay === '1';
        calendarCancel?.removeAttribute('hidden');
        const heading = calendarManager.querySelector('.calendar-manager-heading h3');
        if (heading) heading.textContent = 'Editar compromisso';
        setCalendarStatus('');
        calendarForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        fields.title.focus();
    });
});

calendarCancel?.addEventListener('click', () => {
    resetCalendarForm();
    setCalendarStatus('Edição cancelada.');
});

calendarManager?.querySelectorAll('[data-calendar-delete]').forEach((button) => {
    button.addEventListener('click', async () => {
        const item = button.closest('[data-calendar-item]');
        const id = item?.dataset.id;
        if (!id || !calendarManager.dataset.endpoint) return;
        if (!window.confirm(`Excluir “${item.dataset.title ?? 'este compromisso'}”?`)) return;

        button.disabled = true;
        setCalendarStatus('Excluindo compromisso…');
        try {
            const response = await fetch(`${calendarManager.dataset.endpoint}/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!response.ok) throw new Error('Não foi possível excluir o compromisso.');
            setCalendarStatus('Compromisso excluído.');
            window.location.reload();
        } catch (error) {
            setCalendarStatus(error instanceof Error ? error.message : 'Não foi possível excluir o compromisso.', true);
            button.disabled = false;
        }
    });
});
