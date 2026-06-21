<article class="github-card">
    <div class="card-head">
        <div class="integration-brand-heading">
            <span class="brand-mark-medium brand-mark-github"><x-icons.github /></span>
            <div>
                <span class="card-kicker">GitHub API</span>
                <h3>Projetos e atividade pública</h3>
            </div>
        </div>
        <a href="{{ $portfolio['social']['github'] }}" target="_blank" rel="noopener noreferrer" class="github-profile-link"><x-icons.github /> @brt9 <span aria-hidden="true">↗</span></a>
    </div>

    @if ($github)
        <div class="github-summary">
            <div class="github-profile">
                <span class="github-avatar-wrap">
                    <img src="{{ $github['profile']['avatar'] }}" alt="Avatar de {{ $github['profile']['login'] }}" loading="lazy">
                    <span class="github-avatar-badge" aria-hidden="true"><x-icons.github /></span>
                </span>
                <div>
                    <strong>{{ $github['profile']['name'] ?: $github['profile']['login'] }}</strong>
                    <span>{{ '@'.$github['profile']['login'] }}</span>
                </div>
            </div>

            <div class="github-metrics">
                <div><strong>{{ $github['profile']['public_repositories'] }}</strong><span>repositórios</span></div>
                <div><strong>{{ $github['profile']['followers'] }}</strong><span>seguidores</span></div>
                <div><strong>{{ $github['activity']['recent_events'] }}</strong><span>eventos recentes</span></div>
                <div><strong>{{ $github['activity']['main_language'] ?? '—' }}</strong><span>linguagem principal</span></div>
            </div>
        </div>

        <div class="github-repositories">
            @foreach ($github['repositories'] as $repository)
                <a href="{{ $repository['url'] }}" target="_blank" rel="noopener noreferrer" class="github-repository">
                    <div>
                        <strong>{{ $repository['name'] }}</strong>
                        <p>{{ $repository['description'] ?: 'Repositório público no GitHub.' }}</p>
                    </div>
                    <div class="repo-meta">
                        @if ($repository['language'])<span>{{ $repository['language'] }}</span>@endif
                        <span>★ {{ $repository['stars'] }}</span>
                        <span>⑂ {{ $repository['forks'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="integration-note">A atividade do GitHub está temporariamente indisponível. O perfil continua acessível pelo link acima.</p>
    @endif
</article>
