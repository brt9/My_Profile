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

    <section id="github" class="section" data-nav-owner="experiencia">
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

    @if ($duolingo)
        @include('sections.duolingo')
    @endif

    <section id="laboratorio" class="section" data-nav-owner="laboratorio">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">Estudos de caso</span>
                    <h2>Integrações em páginas próprias.</h2>
                </div>
                <p>Projetos técnicos documentados com contexto, arquitetura, decisões de segurança e demonstrações em funcionamento.</p>
            </div>

            <div class="case-study-grid">
                <a href="{{ route('calendar.show') }}" class="case-study-card">
                    <span class="case-study-index">01 · Google Calendar API</span>
                    <h3>Agenda, OAuth e sincronização resiliente</h3>
                    <p>Uma agenda pública com projeção segura de eventos, CRUD local e sincronização assíncrona com o Google.</p>
                    <ul class="case-study-tags" aria-label="Tecnologias da agenda">
                        <li>OAuth 2.0</li>
                        <li>Laravel Queue</li>
                        <li>MySQL</li>
                    </ul>
                    <span class="case-study-link">Ler estudo de caso <span aria-hidden="true">→</span></span>
                </a>

                <a href="{{ route('steam.show') }}" class="case-study-card">
                    <span class="case-study-index">02 · Steam Web API</span>
                    <h3>Dados públicos convertidos em produto</h3>
                    <p>Biblioteca, atividade recente e conquistas normalizadas com cache, tolerância a falhas e interface responsiva.</p>
                    <ul class="case-study-tags" aria-label="Tecnologias da integração Steam">
                        <li>REST API</li>
                        <li>Cache</li>
                        <li>Resiliência</li>
                    </ul>
                    <span class="case-study-link">Explorar laboratório <span aria-hidden="true">→</span></span>
                </a>

                <a href="{{ route('weather.show') }}" class="case-study-card">
                    <span class="case-study-index">03 · Open-Meteo API</span>
                    <h3>Clima, geolocalização e dados persistidos</h3>
                    <p>Condições meteorológicas de Natal e da cidade do visitante com consentimento, cache e fallback para falhas externas.</p>
                    <ul class="case-study-tags" aria-label="Tecnologias da integração de clima">
                        <li>Open-Meteo</li>
                        <li>Geolocalização</li>
                        <li>Cache</li>
                    </ul>
                    <span class="case-study-link">Ver clima em tempo real <span aria-hidden="true">→</span></span>
                </a>
            </div>
        </div>
    </section>
@endsection
