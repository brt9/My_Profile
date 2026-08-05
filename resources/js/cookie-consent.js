const CONSENT_COOKIE = 'portfolio_cookie_consent';
const LOCATION_CONSENT = 'location-v1';
const ESSENTIAL_CONSENT = 'essential-v1';

const readCookie = (name) => document.cookie
    .split('; ')
    .find((part) => part.startsWith(`${name}=`))
    ?.split('=')
    .slice(1)
    .join('=') ?? null;

const writeConsent = (value) => {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${CONSENT_COOKIE}=${value}; Path=/; Max-Age=31536000; SameSite=Lax${secure}`;
};

export const registerCookieConsent = () => {
    const panel = document.querySelector('[data-cookie-consent]');
    const essentialButton = panel?.querySelector('[data-cookie-essential]');
    const locationButton = panel?.querySelector('[data-cookie-location]');
    const closeButton = panel?.querySelector('[data-cookie-consent-close]');
    const consentStatus = panel?.querySelector('[data-cookie-consent-status]');
    const map = document.querySelector('[data-visitor-map]');
    const mapStatus = map?.querySelector('[data-visitor-map-status]');
    const settingsButtons = [...document.querySelectorAll('[data-cookie-settings-open]')];
    const endpoint = map?.dataset.visitorMapEndpoint ?? panel?.dataset.visitorMapEndpoint;

    if (!panel || !essentialButton || !locationButton) return;

    const setStatus = (message) => {
        if (consentStatus) consentStatus.textContent = message;
    };

    const show = (trigger = null) => {
        panel.removeAttribute('hidden');
        closeButton?.toggleAttribute('hidden', !readCookie(CONSENT_COOKIE));
        setStatus(readCookie(CONSENT_COOKIE) === LOCATION_CONSENT
            ? 'Sua região aproximada está incluída no mapa. Você pode revogar essa autorização a qualquer momento.'
            : 'Sua localização não está sendo compartilhada.');
        window.setTimeout(() => {
            (readCookie(CONSENT_COOKIE) === LOCATION_CONSENT ? essentialButton : locationButton).focus();
        }, 0);
        panel.dataset.returnFocus = trigger ? settingsButtons.indexOf(trigger).toString() : '';
    };

    const hide = () => {
        panel.setAttribute('hidden', '');
        const index = Number(panel.dataset.returnFocus);
        if (Number.isInteger(index) && index >= 0) settingsButtons[index]?.focus();
        panel.dataset.returnFocus = '';
    };

    const removeVisitor = async () => {
        if (!endpoint) return;
        try {
            await fetch(endpoint, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            window.dispatchEvent(new CustomEvent('visitor-map:reload'));
        } catch {
            // Consent still changes locally even if the anonymous record was already absent.
        }
    };

    const chooseEssential = async () => {
        const previouslyShared = readCookie(CONSENT_COOKIE) === LOCATION_CONSENT;
        writeConsent(ESSENTIAL_CONSENT);
        essentialButton.disabled = true;
        if (previouslyShared) await removeVisitor();
        essentialButton.disabled = false;
        if (mapStatus) mapStatus.textContent = 'Sua região não está incluída. Você pode alterar essa escolha quando quiser.';
        hide();
    };

    const shareLocation = () => {
        if (!navigator.geolocation) {
            writeConsent(ESSENTIAL_CONSENT);
            setStatus('Seu navegador não oferece geolocalização. Somente os cookies necessários serão usados.');
            return;
        }

        locationButton.disabled = true;
        essentialButton.disabled = true;
        setStatus('Aguardando a permissão de localização do navegador…');

        navigator.geolocation.getCurrentPosition(async ({ coords }) => {
            try {
                writeConsent(LOCATION_CONSENT);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        latitude: coords.latitude,
                        longitude: coords.longitude,
                    }),
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 'recorded') throw new Error('location unavailable');

                if (mapStatus) mapStatus.textContent = 'Obrigado! Sua região aproximada agora faz parte do mapa.';
                window.dispatchEvent(new CustomEvent('visitor-map:reload'));
                hide();
            } catch {
                writeConsent(ESSENTIAL_CONSENT);
                setStatus('Não foi possível adicionar sua região agora. Nenhuma localização foi armazenada.');
            } finally {
                locationButton.disabled = false;
                essentialButton.disabled = false;
            }
        }, (error) => {
            writeConsent(ESSENTIAL_CONSENT);
            const messages = {
                1: 'Permissão negada. Sua localização não será compartilhada.',
                2: 'Localização indisponível. Nenhuma localização foi armazenada.',
                3: 'O pedido de localização expirou. Nenhuma localização foi armazenada.',
            };
            setStatus(messages[error.code] ?? 'Não foi possível obter sua localização.');
            locationButton.disabled = false;
            essentialButton.disabled = false;
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 });
    };

    essentialButton.addEventListener('click', chooseEssential);
    locationButton.addEventListener('click', shareLocation);
    closeButton?.addEventListener('click', hide);
    settingsButtons.forEach((button) => button.addEventListener('click', () => show(button)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hasAttribute('hidden') && readCookie(CONSENT_COOKIE)) hide();
    });

    if (!readCookie(CONSENT_COOKIE)) show();
};
