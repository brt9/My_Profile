@extends('layouts.app')

@section('content')
    <header id="inicio" class="hero">
        <div class="container-shell">
            <div class="hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow">{{ $portfolio['role'] }}</span>
                    <h1>Pedro Felipe,<br><span class="gradient-text">código que resolve.</span></h1>
                    <p class="hero-lead">{{ $portfolio['headline'] }}</p>

                    <div class="badge-list" aria-label="Tecnologias principais">
                        <span class="badge">🐘 PHP & Laravel</span>
                        <span class="badge">⚡ JavaScript</span>
                        <span class="badge">🧩 APIs & integrações</span>
                        <span class="badge">📍 {{ $portfolio['location'] }}</span>
                    </div>

                    <div class="hero-actions">
                        <a href="#projetos" class="button button-primary">Explorar projetos <span aria-hidden="true">↓</span></a>
                        <a href="#contato" class="button button-secondary">Entrar em contato</a>
                    </div>
                </div>

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
    @include('sections.pc')

    <section id="integracoes" class="section section-alt">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">Integrações</span>
                    <h2>Dados reais, não só cards.</h2>
                </div>
                <p>Recursos do projeto consumindo serviços externos com cache, tolerância a falhas e apresentação responsiva.</p>
            </div>

            <div class="integration-grid">
                @include('sections.weather')
                @include('sections.steam')
                @include('sections.github')
            </div>
        </div>
    </section>

    @if ($calendar)
        @include('sections.calendar')
    @endif

    @if ($duolingo)
        @include('sections.duolingo')
    @endif

    <section id="contato" class="section">
        <div class="container-shell">
            <div class="contact-panel">
                <span class="section-kicker">Contato</span>
                <h2>Tem um problema interessante<br>para resolver?</h2>
                <p>Estou aberto a conversas sobre desenvolvimento web, Laravel, integrações e produtos digitais. Veja o código no GitHub ou fale comigo pelo LinkedIn.</p>

                <div class="contact-actions">
                    @if ($portfolio['email'])
                        <a href="mailto:{{ $portfolio['email'] }}" class="button button-primary">Enviar e-mail</a>
                    @endif
                    @if ($portfolio['social']['linkedin'])
                        <a href="{{ $portfolio['social']['linkedin'] }}" target="_blank" rel="noopener noreferrer" class="button button-secondary">LinkedIn ↗</a>
                    @endif
                    @if ($portfolio['social']['github'])
                        <a href="{{ $portfolio['social']['github'] }}" target="_blank" rel="noopener noreferrer" class="button button-secondary">GitHub ↗</a>
                    @endif
                    @unless ($portfolio['email'] || $portfolio['social']['linkedin'] || $portfolio['social']['github'])
                        <a href="#inicio" class="button button-primary">Conhecer meu trabalho ↑</a>
                    @endunless
                </div>
            </div>
        </div>
    </section>
@endsection
