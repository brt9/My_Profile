<section id="sobre" class="section section-alt" data-nav-owner="sobre">
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
                <div class="current-roles">
                    @foreach ($portfolio['current_roles'] as $currentRole)
                        <div class="current-role">
                            <span class="status-dot" aria-hidden="true"></span>
                            <div>
                                <strong>{{ $currentRole['role'] }}</strong>
                                <p>{{ $currentRole['company'] }}<br>{{ $currentRole['location'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>

        <div class="competency-grid" aria-label="Competências e tecnologias">
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
                </article>
            @endforeach
        </div>
    </div>
</section>
