<article
    class="weather-card"
    data-weather-card
    data-weather-endpoint="{{ route('weather.location') }}"
    data-weather-visitor-endpoint="{{ route('weather.visitor') }}"
>
    <div class="card-head">
        <div>
            <span class="card-kicker">Open-Meteo</span>
            <h3>Tempo agora</h3>
        </div>
        <span class="project-status">API + banco</span>
    </div>

    <div class="weather-locations">
    <section class="weather-location weather-location-primary" data-weather-scope="natal" aria-labelledby="weather-natal-title">
        <div class="weather-location-head">
            <div>
                <span class="weather-location-label">Localização principal</span>
                <h4 id="weather-natal-title">Natal, RN</h4>
            </div>
            <span class="weather-location-badge">Sempre visível</span>
        </div>

        <div class="weather-main">
            <span class="weather-icon" aria-hidden="true">{{ $weatherNatal['emoji'] ?? '⛅' }}</span>
            <div>
                <div class="weather-temp">{{ isset($weatherNatal['temp']) ? number_format($weatherNatal['temp'], 0) : '—' }}°</div>
                <div class="weather-meta">{{ $weatherNatal['condition'] ?? 'Aguardando a primeira captura' }}</div>
            </div>
        </div>

        <div class="weather-details">
            <div><small>Sensação</small><strong>{{ isset($weatherNatal['feels_like']) ? number_format($weatherNatal['feels_like'], 0).'°' : '—' }}</strong></div>
            <div><small>Umidade</small><strong>{{ isset($weatherNatal['humidity']) ? $weatherNatal['humidity'].'%' : '—' }}</strong></div>
            <div><small>Vento</small><strong>{{ isset($weatherNatal['wind_kmh']) ? $weatherNatal['wind_kmh'].' km/h' : '—' }}</strong></div>
        </div>

        @if (!empty($weatherNatal['captured_at']))
            <p class="weather-captured-at">
                {{ !empty($weatherNatal['is_stale']) ? 'API indisponível ou cache expirado. Último dado capturado' : 'Dado capturado' }}
                em {{ \Carbon\CarbonImmutable::parse($weatherNatal['captured_at'])->timezone($portfolio['presentation_timezone'])->format('d/m/Y \à\s H:i') }}.
            </p>
        @else
            <p class="weather-captured-at">Ainda não existe uma captura salva para Natal.</p>
        @endif
    </section>

    <section class="weather-location weather-location-visitor" data-weather-scope="visitor" aria-labelledby="weather-visitor-title" aria-busy="true">
        <div class="weather-location-head">
            <div>
                <span class="weather-location-label">Localização do visitante</span>
                <h4 id="weather-visitor-title" data-weather-title>Sua cidade</h4>
            </div>
            <span class="weather-location-badge" data-weather-source>Aproximada</span>
        </div>

        <div class="weather-main">
            <span class="weather-icon" data-weather-icon aria-hidden="true">⛅</span>
            <div>
                <div class="weather-temp" data-weather-temp>—°</div>
                <div class="weather-meta" data-weather-condition>Identificando sua cidade…</div>
            </div>
        </div>

        <div class="weather-details">
            <div><small>Sensação</small><strong data-weather-feels>—</strong></div>
            <div><small>Umidade</small><strong data-weather-humidity>—</strong></div>
            <div><small>Vento</small><strong data-weather-wind>—</strong></div>
        </div>

        <p class="weather-origin" data-weather-origin>A localização aproximada é identificada pelo IP e não é armazenada.</p>

        <div class="weather-consent">
            <button type="button" class="text-button" data-weather-locate>Usar minha localização exata</button>
            <p class="integration-note" data-weather-status role="status" aria-live="polite">Buscando o clima da sua cidade…</p>
        </div>
    </section>
    </div>
</article>
