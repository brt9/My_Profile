<section id="sobre" class="section section-alt">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Sobre e competências</span>
                <h2>Backend sólido.<br>Experiência cuidadosa.</h2>
            </div>
            <p>O que faço, como contribuo e quais tecnologias uso para transformar necessidades operacionais em produtos web confiáveis.</p>
        </div>

        <div class="about-grid">
            <article class="panel">
                <h3>Meu perfil</h3>
                <p class="about-copy">{{ $portfolio['about'] }}</p>
            </article>

            <aside class="panel">
                <h3>Atuação atual</h3>
                <div class="current-role">
                    <span class="status-dot" aria-hidden="true"></span>
                    <div>
                        <strong>{{ $portfolio['current_role'] ?? $portfolio['role'] }}</strong>
                        <p>{{ $portfolio['company'] }}<br>{{ $portfolio['location'] }}</p>
                    </div>
                </div>
            </aside>
        </div>

        <div class="competency-grid" aria-label="Competências, tecnologias e evidências">
            @foreach ($portfolio['competencies'] as $competency)
                <article class="competency-card">
                    <div class="competency-card-copy">
                        <h3>{{ $competency['title'] }}</h3>
                        <p>{{ $competency['description'] }}</p>
                    </div>
                    <div class="chip-list" aria-label="Tecnologias de {{ $competency['title'] }}">
                        @foreach ($competency['items'] as $skill)
                            <x-technology-badge :name="$skill" />
                        @endforeach
                    </div>
                    <a class="evidence-link" href="{{ $competency['href'] }}">
                        <span aria-hidden="true">↗</span> {{ $competency['evidence'] }}
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
