@php($weather = $weatherVisitor ?? $weatherNatal ?? null)

<article class="weather-card" data-weather-card data-weather-endpoint="{{ route('weather.location') }}">
    <div class="card-head">
        <div>
            <span class="card-kicker">Open-Meteo</span>
            <h3 data-weather-title>{{ $weather ? 'Clima em '.($weather['label'] ?? 'Natal, RN') : 'Tempo agora' }}</h3>
        </div>
        <span class="project-status">API + cache</span>
    </div>

    <div class="weather-main">
        <span class="weather-icon" data-weather-icon aria-hidden="true">{{ $weather['emoji'] ?? '⛅' }}</span>
        <div>
            <div class="weather-temp" data-weather-temp>{{ isset($weather['temp']) ? number_format($weather['temp'], 0) : '—' }}°</div>
            <div class="weather-meta"><span data-weather-condition>{{ $weather['condition'] ?? 'Serviço temporariamente indisponível' }}</span></div>
        </div>
    </div>

    <p class="weather-origin" data-weather-origin>
        {{ $weather['origin'] ?? 'Localização padrão: Natal/RN' }}. Coordenadas não são armazenadas.
    </p>

    <div class="weather-details">
        <div><small>Sensação</small><strong data-weather-feels>{{ isset($weather['feels_like']) ? number_format($weather['feels_like'], 0).'°' : '—' }}</strong></div>
        <div><small>Umidade</small><strong data-weather-humidity>{{ isset($weather['humidity']) ? $weather['humidity'].'%' : '—' }}</strong></div>
        <div><small>Vento</small><strong data-weather-wind>{{ isset($weather['wind_kmh']) ? $weather['wind_kmh'].' km/h' : '—' }}</strong></div>
    </div>

    <div class="weather-consent">
        <button type="button" class="text-button" data-weather-locate>Usar minha localização exata</button>
        <p class="integration-note" data-weather-status role="status" aria-live="polite">A permissão só será solicitada após seu clique.</p>
    </div>
</article>
