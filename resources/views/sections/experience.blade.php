<section id="experiencia" class="section section-alt">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Experiência</span>
                <h2>Trajetória profissional.</h2>
            </div>
            <p>Experiências organizadas por contexto, responsabilidade e impacto para facilitar uma leitura objetiva da minha carreira.</p>
        </div>

        @if (($professional['source'] ?? 'portfolio') === 'linkedin_pdf')
            <p class="content-source">Conteúdo profissional revisado a partir do perfil PDF do LinkedIn em {{ $professional['updated_at']?->format('d/m/Y') }}.</p>
        @endif

        <div class="panel">
            <div class="timeline">
                @foreach ($professional['experiences'] as $experience)
                    <article class="timeline-item">
                        <span class="timeline-period">{{ $experience['period'] }}</span>
                        <h3>{{ $experience['role'] }}</h3>
                        <div class="timeline-company">{{ $experience['company'] }}</div>
                        @if (!empty($experience['location']))<div class="timeline-location">{{ $experience['location'] }}</div>@endif
                        <p>{{ $experience['description'] }}</p>
                        @if (!empty($experience['stack']))
                            <div class="chip-list" aria-label="Tecnologias usadas em {{ $experience['company'] }}">
                                @foreach ($experience['stack'] as $technology)
                                    <x-technology-badge :name="$technology" />
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>

        <div class="profile-detail-grid">
            <article class="panel profile-detail-card">
                <span class="section-kicker">Formação</span>
                @foreach ($professional['education'] as $education)
                    <h3>{{ $education['course'] }}</h3>
                    <p>{{ $education['institution'] }}<br>{{ $education['period'] }}</p>
                @endforeach
            </article>
            <article class="panel profile-detail-card">
                <span class="section-kicker">Idiomas</span>
                <div class="profile-language-list">
                    @foreach ($professional['languages'] as $language)
                        <div><strong>{{ $language['name'] }}</strong><span>{{ $language['level'] }}</span></div>
                    @endforeach
                </div>
                @if (!empty($professional['language_note']))
                    <p class="profile-language-note">{{ $professional['language_note'] }}</p>
                @endif
            </article>
        </div>
    </div>
</section>
