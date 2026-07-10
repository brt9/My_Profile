@extends('layouts.app')

@section('content')
    <header id="inicio" class="hero">
        <div class="container-shell">
            <div class="hero-grid">
                <div class="hero-heading">
                    <span class="eyebrow">{{ $portfolio['role'] }}</span>
                    <h1>Pedro Felipe,<br><span class="gradient-text">código que resolve.</span></h1>

                    <div class="hero-visual" aria-label="Identidade visual de Pedro Felipe">
                        <div class="hero-orbit" aria-hidden="true"></div>
                        <div class="avatar-card">
                            @if (!empty($portfolio['photo']))
                                <img src="{{ asset($portfolio['photo']) }}" alt="Foto de {{ $portfolio['name'] }}" class="avatar-image" width="512" height="512" fetchpriority="high">
                            @else
                                <span class="avatar-monogram">PF</span>
                            @endif
                        </div>
                        <span class="floating-label top">&lt;full-stack /&gt;</span>
                        <span class="floating-label bottom">laravel · php · js</span>
                    </div>
                </div>

                <div class="hero-body">
                    <p class="hero-lead">{{ $portfolio['headline'] }}</p>

                    <div class="badge-list" aria-label="Tecnologias principais">
                        <span class="badge">🐘 PHP & Laravel</span>
                        <span class="badge">⚡ JavaScript</span>
                        <span class="badge">🧩 APIs & integrações</span>
                        <span class="badge">📍 {{ $portfolio['location'] }}</span>
                    </div>

                    <div class="hero-actions">
                        <a href="#projetos" class="button button-primary">Explorar projetos <span aria-hidden="true">↓</span></a>
                        <a href="#experiencia" class="button button-secondary">Ver trajetória</a>
                    </div>
                </div>
            </div>

            <div class="stats-grid" aria-label="Resumo profissional">
                @foreach ($portfolio['stats'] as $stat)
                    <div class="stat">
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </header>

    @include('sections.about')
    @include('sections.projects')
    @include('sections.experience')

    <section id="github" class="section">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">GitHub</span>
                    <h2>Código público e evolução.</h2>
                </div>
                <p>Projetos, tecnologias e histórico anual de contribuições reunidos logo após a trajetória profissional.</p>
            </div>
            <div class="integration-grid integration-grid-single">
                @include('sections.github')
            </div>
        </div>
    </section>

    @include('sections.pc')

    @if ($calendar)
        @include('sections.calendar')
    @endif

    @if ($duolingo)
        @include('sections.duolingo')
    @endif

    <section id="steam" class="section">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">Steam</span>
                    <h2>Jogos também geram dados.</h2>
                </div>
                <p>A atividade pública da Steam transforma biblioteca, tempo de jogo e conquistas recentes em uma leitura compacta.</p>
            </div>
            <div class="integration-grid integration-grid-single">
                @include('sections.steam')
            </div>
        </div>
    </section>

    <section id="clima" class="section section-alt">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">Clima</span>
                    <h2>Natal em tempo real.</h2>
                </div>
                <p>O último dado válido permanece disponível no banco mesmo quando a fonte externa está temporariamente indisponível.</p>
            </div>
            <div class="integration-grid integration-grid-single">
                @include('sections.weather')
            </div>
        </div>
    </section>
@endsection
