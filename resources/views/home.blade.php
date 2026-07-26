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

                    <div class="hero-actions">
                        <a href="#experiencia" class="button button-primary">Ver trajetória <span aria-hidden="true">↓</span></a>
                        <a href="#projetos" class="button button-secondary">Explorar projetos</a>
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
    @include('sections.experience')

    <section id="github" class="section github-section" data-nav-owner="experiencia">
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

    @include('sections.projects')

    @include('sections.pc')

    @if ($duolingo)
        @include('sections.duolingo')
    @endif

@endsection
