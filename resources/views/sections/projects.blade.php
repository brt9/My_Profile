<section id="projetos" class="section">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Projetos</span>
                <h2>Trabalho em funcionamento.</h2>
            </div>
            <p>Produtos que mostram como aplico backend, interface, integrações e operação para resolver problemas reais.</p>
        </div>

        <div class="project-grid">
            @foreach ($portfolio['projects'] as $project)
                @if (!empty($project['url']))
                    <a
                        class="project-card project-card-link"
                        href="{{ $project['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Abrir o site {{ $project['title'] }}"
                    >
                @else
                    <article class="project-card">
                @endif
                    <div class="project-top">
                        <span class="project-number">{{ $project['number'] }}</span>
                        <span class="project-top-actions">
                            <span class="project-status">{{ $project['status'] }}</span>
                            @if (!empty($project['url']))
                                <span class="project-site-cta">Visitar site ↗</span>
                            @endif
                        </span>
                    </div>
                    <h3>{{ $project['title'] }}</h3>
                    <dl class="project-story">
                        <div><dt>Contexto</dt><dd>{{ $project['context'] }}</dd></div>
                        <div><dt>O que faz</dt><dd>{{ $project['action'] }}</dd></div>
                        <div><dt>Resultado</dt><dd>{{ $project['result'] }}</dd></div>
                    </dl>
                    <div class="chip-list" aria-label="Tecnologias de {{ $project['title'] }}">
                        @foreach ($project['stack'] as $technology)
                            <x-technology-badge :name="$technology" />
                        @endforeach
                    </div>
                @if (!empty($project['url']))
                    </a>
                @else
                    </article>
                @endif
            @endforeach
        </div>
    </div>
</section>
