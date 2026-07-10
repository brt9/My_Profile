@props(['game', 'loading' => 'lazy'])

@php
    $appId = (int) ($game['appid'] ?? 0);
    $name = (string) ($game['name'] ?? 'Jogo Steam');
    $header = $game['image'] ?? ($appId ? "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/header.jpg" : null);
    $capsule = $game['capsule'] ?? ($appId ? "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/capsule_616x353.jpg" : null);
@endphp

<div {{ $attributes->class(['game-cover', 'is-error' => !$header]) }} data-game-cover>
    <div class="game-cover-fallback" aria-hidden="true">
        <span>🎮</span>
        <strong>{{ $name }}</strong>
    </div>
    @if ($header)
        <img src="{{ $header }}"
            alt="Capa de {{ $name }}"
            width="616"
            height="353"
            loading="{{ $loading }}"
            decoding="async"
            data-game-image
            data-fallback-src="{{ $capsule }}">
    @endif
</div>
