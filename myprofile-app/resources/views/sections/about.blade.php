<section id="sobre" class="section section-alt">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Sobre</span>
                <h2>Backend sólido.<br>Experiência cuidadosa.</h2>
            </div>
            <p>Uma apresentação objetiva do que faço, como trabalho e quais tecnologias uso para entregar produtos web.</p>
        </div>

        <div class="about-grid">
            <article class="panel">
                <h3>Meu perfil</h3>
                <p class="about-copy">{{ $portfolio['about'] }}</p>
            </article>

            <aside class="panel">
                <h3>Atualmente</h3>
                <div class="current-role">
                    <span class="status-dot" aria-hidden="true"></span>
                    <div>
                        <strong>{{ $portfolio['role'] }}</strong>
                        <p>{{ $portfolio['company'] }}<br>{{ $portfolio['location'] }}</p>
                    </div>
                </div>
            </aside>
        </div>

        <div class="competency-grid" aria-label="Competências com evidências">
            @foreach ($portfolio['competencies'] as $competency)
                <article class="competency-card">
                    <h3>{{ $competency['title'] }}</h3>
                    <div class="chip-list">
                        @foreach ($competency['items'] as $skill)
                            <span class="chip">{{ $skill }}</span>
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

<section id="projetos" class="section">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Projetos</span>
                <h2>Trabalho em funcionamento.</h2>
            </div>
            <p>Recursos presentes neste repositório que demonstram backend, integrações, interface e operação.</p>
        </div>

        <div class="project-grid">
            @foreach ($portfolio['projects'] as $project)
                <article class="project-card">
                    <div class="project-top">
                        <span class="project-number">{{ $project['number'] }}</span>
                        <span class="project-status">{{ $project['status'] }}</span>
                    </div>
                    <h3>{{ $project['title'] }}</h3>
                    <dl class="project-story">
                        <div><dt>Contexto</dt><dd>{{ $project['context'] }}</dd></div>
                        <div><dt>Ação</dt><dd>{{ $project['action'] }}</dd></div>
                        <div><dt>Resultado</dt><dd>{{ $project['result'] }}</dd></div>
                    </dl>
                    <div class="chip-list">
                        @foreach ($project['stack'] as $technology)
                            <span class="chip">{{ $technology }}</span>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="automacoes" class="section section-alt">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Automações</span>
                <h2>Menos operação manual.<br>Mais previsibilidade.</h2>
            </div>
            <p>Problema, solução e responsabilidade apresentados com evidências disponíveis neste projeto.</p>
        </div>

        <div class="automation-grid">
            @foreach ($portfolio['automations'] as $automation)
                <article class="automation-card">
                    <h3>{{ $automation['title'] }}</h3>
                    <dl>
                        <div><dt>Antes</dt><dd>{{ $automation['before'] }}</dd></div>
                        <div><dt>Solução</dt><dd>{{ $automation['solution'] }}</dd></div>
                        <div><dt>Resultado</dt><dd>{{ $automation['result'] }}</dd></div>
                        <div><dt>Responsabilidade</dt><dd>{{ $automation['responsibility'] }}</dd></div>
                    </dl>
                    <div class="chip-list">
                        @foreach ($automation['stack'] as $technology)
                            <span class="chip">{{ $technology }}</span>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section id="experiencia" class="section">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Experiência</span>
                <h2>Trajetória profissional.</h2>
            </div>
            <p>Experiência organizada por impacto, contexto e tecnologias — uma leitura rápida para recrutadores e clientes.</p>
        </div>

        <div class="panel">
            <div class="timeline">
                @foreach ($portfolio['experience'] as $experience)
                    <article class="timeline-item">
                        <span class="timeline-period">{{ $experience['period'] }}</span>
                        <h3>{{ $experience['role'] }}</h3>
                        <div class="timeline-company">{{ $experience['company'] }}</div>
                        <p>{{ $experience['description'] }}</p>
                        <div class="chip-list">
                            @foreach ($experience['stack'] as $technology)
                                <span class="chip">{{ $technology }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
