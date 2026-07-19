@extends('layouts.app')

@section('content')
    <header class="case-page-hero">
        <div class="container-shell case-page-hero-grid">
            <div>
                <a href="{{ route('home') }}#laboratorio" class="case-page-back">← Voltar ao portfólio</a>
                <span class="section-kicker">Estudo de caso · Google Calendar API</span>
                <h1>Agenda pública com privacidade por padrão.</h1>
                <p>Uma integração que combina OAuth 2.0, filas e persistência local para publicar disponibilidade sem expor informações privadas.</p>
            </div>

            <dl class="case-page-summary">
                <div><dt>Papel</dt><dd>Arquitetura e desenvolvimento full stack</dd></div>
                <div><dt>Stack</dt><dd>Laravel, MySQL, Google Calendar API</dd></div>
                <div><dt>Operação</dt><dd>Scheduler, filas e snapshots resilientes</dd></div>
            </dl>
        </div>
    </header>

    <section class="section section-alt case-page-story">
        <div class="container-shell">
            <div class="case-page-story-grid">
                <article>
                    <span class="case-story-number">01</span>
                    <h2>O problema</h2>
                    <p>Exibir disponibilidade profissional sem publicar descrições, participantes, links de reunião ou detalhes de compromissos privados.</p>
                </article>
                <article>
                    <span class="case-story-number">02</span>
                    <h2>A solução</h2>
                    <p>Os eventos passam por uma projeção segura. Apenas horário, categoria e títulos explicitamente autorizados chegam à interface pública.</p>
                </article>
                <article>
                    <span class="case-story-number">03</span>
                    <h2>Resiliência</h2>
                    <p>O banco local preserva o último snapshot válido. Se o Google falhar ou o OAuth expirar, a agenda continua disponível.</p>
                </article>
            </div>
        </div>
    </section>

    @if ($calendar)
        @include('sections.calendar')
    @else
        <section id="agenda" class="section">
            <div class="container-shell">
                <div class="integration-empty">
                    <strong>Demonstração temporariamente desativada.</strong>
                    <p>O estudo de caso continua disponível; a agenda ao vivo pode ser habilitada pela configuração segura do ambiente.</p>
                </div>
            </div>
        </section>
    @endif
@endsection
