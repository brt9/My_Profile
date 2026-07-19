@extends('layouts.app')

@section('content')
    <header class="case-page-hero">
        <div class="container-shell case-page-hero-grid">
            <div>
                <a href="{{ route('home') }}#laboratorio" class="case-page-back">← Voltar ao portfólio</a>
                <span class="section-kicker">Laboratório · Open-Meteo API</span>
                <h1>Clima em tempo real com privacidade.</h1>
                <p>Uma integração que combina dados meteorológicos, localização consentida e persistência para continuar útil mesmo quando serviços externos falham.</p>
            </div>

            <dl class="case-page-summary">
                <div><dt>Fonte</dt><dd>Open-Meteo</dd></div>
                <div><dt>Stack</dt><dd>Laravel, JavaScript, MySQL</dd></div>
                <div><dt>Estratégia</dt><dd>Consentimento, cache e fallback</dd></div>
            </dl>
        </div>
    </header>

    <section class="section section-alt case-page-story">
        <div class="container-shell">
            <div class="case-page-story-grid">
                <article>
                    <span class="case-story-number">01</span>
                    <h2>Privacidade</h2>
                    <p>A cidade aproximada vem do IP. Coordenadas exatas só são consultadas quando o visitante autoriza no navegador e não ficam armazenadas.</p>
                </article>
                <article>
                    <span class="case-story-number">02</span>
                    <h2>Persistência</h2>
                    <p>Capturas válidas de Natal ficam no banco e funcionam como fallback quando a fonte meteorológica está temporariamente indisponível.</p>
                </article>
                <article>
                    <span class="case-story-number">03</span>
                    <h2>Experiência</h2>
                    <p>A interface informa a origem da localização e permite trocar a estimativa por IP por uma consulta exata e consentida.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="demonstracao" class="section">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">Demonstração ao vivo</span>
                    <h2>Natal e sua cidade agora.</h2>
                </div>
                <p>O último dado válido permanece disponível mesmo quando a fonte externa está temporariamente indisponível.</p>
            </div>

            @if ($weatherEnabled)
                <div class="integration-grid integration-grid-single">
                    @include('sections.weather')
                </div>
            @else
                <div class="empty-state">
                    <strong>Integração meteorológica não configurada.</strong>
                    <span>Ative a integração para exibir a demonstração.</span>
                </div>
            @endif
        </div>
    </section>
@endsection
