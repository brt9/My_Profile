<article class="steam-card">
    <div class="card-head">
        <div>
            <span class="card-kicker">Steam Web API</span>
            <h3>Jogos, biblioteca e conquistas</h3>
        </div>
        @if ($steamProfile)
            <a href="{{ $steamProfile }}" target="_blank" rel="noopener noreferrer" class="text-link">Ver perfil ↗</a>
        @else
            <span class="project-status">Aguardando chaves</span>
        @endif
    </div>

    @if ($currentGame)
        <div class="steam-now-playing">
            <x-game-image :game="$currentGame" class="steam-now-cover" />
            <div class="steam-now-details">
                <small>Jogando agora</small>
                <strong>{{ $currentGame['name'] }}</strong>
                <a href="steam://run/{{ $currentGame['appid'] }}" class="text-link">Abrir na Steam</a>
            </div>
        </div>
    @endif

    @if (($steamSummary['game_count'] ?? 0) > 0)
        <div class="steam-stats">
            <div><strong>{{ number_format($steamSummary['game_count']) }}</strong><span>jogos na biblioteca</span></div>
            <div><strong>{{ number_format(($steamSummary['total_minutes'] ?? 0) / 60, 0) }}h</strong><span>tempo registrado</span></div>
        </div>
    @endif

    @if (!empty($recentGames))
        <h4 class="integration-subtitle">Jogados recentemente</h4>
        <div class="steam-games">
            @foreach (array_slice($recentGames, 0, 3) as $game)
                <div class="steam-game">
                    <x-game-image :game="$game" />
                    <div>
                        <strong>{{ $game['name'] }}</strong>
                        <small>{{ number_format(($game['playtime'] ?? 0) / 60, 1) }} h registradas</small>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($featuredAchievements))
        <h4 class="integration-subtitle">Conquistas recentes</h4>
        <div class="achievement-list">
            @foreach ($featuredAchievements as $achievement)
                <div class="achievement">
                    @if ($achievement['icon'])
                        <img src="{{ $achievement['icon'] }}" alt="" loading="lazy">
                    @endif
                    <div>
                        <strong>{{ $achievement['name'] }}</strong>
                        @if ($achievement['unlock_time'])
                            <small>{{ \Carbon\Carbon::createFromTimestamp($achievement['unlock_time'])->locale('pt_BR')->diffForHumans() }}</small>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif ($steamEnabled && empty($recentGames))
        <p class="steam-empty">A integração está configurada, mas a Steam não retornou atividade pública.</p>
    @elseif (! $steamEnabled)
        <p class="steam-empty">As variáveis <code>STEAM_API_KEY</code> e <code>STEAM_ID</code> estão vazias no <code>.env</code>. Preencha as duas para ativar biblioteca, jogos recentes e conquistas.</p>
    @endif
</article>
