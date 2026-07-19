@extends('layouts.app')

@section('content')
    <header class="case-page-hero case-page-hero-steam">
        <div class="container-shell case-page-hero-grid">
            <div>
                <a href="{{ route('home') }}#laboratorio" class="case-page-back">← Voltar ao portfólio</a>
                <span class="section-kicker">Laboratório · Steam Web API</span>
                <h1>Uma API externa transformada em experiência.</h1>
                <p>Um experimento de produto que converte dados públicos de jogos em uma interface rápida, legível e resistente a indisponibilidades.</p>
            </div>

            <dl class="case-page-summary">
                <div><dt>Fonte</dt><dd>Steam Web API</dd></div>
                <div><dt>Stack</dt><dd>Laravel, HTTP Client, Blade</dd></div>
                <div><dt>Estratégia</dt><dd>Cache, normalização e fallback</dd></div>
            </dl>
        </div>
    </header>

    <section class="section section-alt case-page-story">
        <div class="container-shell">
            <div class="case-page-story-grid">
                <article>
                    <span class="case-story-number">01</span>
                    <h2>Coleta</h2>
                    <p>Clientes dedicados consultam perfil, biblioteca, jogos recentes e conquistas sem misturar detalhes da API com a apresentação.</p>
                </article>
                <article>
                    <span class="case-story-number">02</span>
                    <h2>Normalização</h2>
                    <p>Minutos, capas, nomes e conquistas são convertidos para um contrato estável antes de chegar à camada visual.</p>
                </article>
                <article>
                    <span class="case-story-number">03</span>
                    <h2>Resiliência</h2>
                    <p>Respostas válidas ficam em cache e continuam disponíveis durante falhas, timeouts ou limites temporários do provedor.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="demonstracao" class="section">
        <div class="container-shell">
            <div class="section-header">
                <div>
                    <span class="section-kicker">Demonstração ao vivo</span>
                    <h2>Dados públicos, interface própria.</h2>
                </div>
                <p>O resultado abaixo usa a integração real e mantém o último estado válido quando a Steam não responde.</p>
            </div>
            <div class="integration-grid integration-grid-single">
                @include('sections.steam')
            </div>
        </div>
    </section>
@endsection
