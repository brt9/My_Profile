<section id="projetos" class="section section-alt projects-section" data-nav-owner="projetos">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Projetos</span>
                <h2>Trabalho em funcionamento.</h2>
            </div>
        </div>

        <div
            class="project-carousel"
            x-data="{
                current: 0,
                total: {{ count($portfolio['projects']) }},
                touchStartX: null,
                previous() {
                    this.current = (this.current - 1 + this.total) % this.total;
                },
                next() {
                    this.current = (this.current + 1) % this.total;
                },
                startSwipe(event) {
                    this.touchStartX = event.changedTouches[0]?.clientX ?? null;
                },
                finishSwipe(event) {
                    if (this.touchStartX === null) return;
                    const distance = (event.changedTouches[0]?.clientX ?? this.touchStartX) - this.touchStartX;
                    this.touchStartX = null;
                    if (Math.abs(distance) < 50) return;
                    distance < 0 ? this.next() : this.previous();
                },
            }"
            @keydown.left.prevent="previous()"
            @keydown.right.prevent="next()"
        >
            <div
                class="project-carousel-viewport"
                aria-live="polite"
                @touchstart.passive="startSwipe($event)"
                @touchend.passive="finishSwipe($event)"
            >
            @foreach ($portfolio['projects'] as $project)
                <div
                    class="project-carousel-slide"
                    x-show="current === {{ $loop->index }}"
                    x-transition:enter="project-slide-enter"
                    x-transition:enter-start="project-slide-enter-start"
                    x-transition:enter-end="project-slide-enter-end"
                    x-transition:leave="project-slide-leave"
                    x-transition:leave-start="project-slide-leave-start"
                    x-transition:leave-end="project-slide-leave-end"
                    :aria-hidden="current !== {{ $loop->index }}"
                    x-cloak
                >
                @if (!empty($project['url']))
                    <a
                        @class([
                            'project-showcase',
                            'project-showcase-link',
                            'project-showcase-warm' => $loop->even,
                        ])
                        href="{{ $project['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Abrir o site {{ $project['title'] }}"
                    >
                @else
                    <article @class(['project-showcase', 'project-showcase-warm' => $loop->even])>
                @endif

                    <div class="project-showcase-visual">
                        @if (!empty($project['image']))
                            <img
                                src="{{ asset($project['image']) }}"
                                alt="Imagem representando {{ $project['title'] }}"
                                class="project-cover-image"
                                width="1024"
                                height="1024"
                                decoding="async"
                            >
                        @else
                            <span class="project-cover-fallback" aria-hidden="true">&lt;/&gt;</span>
                        @endif
                    </div>

                    <div class="project-showcase-card">
                        <div class="project-showcase-head">
                            <div>
                                <span class="project-number">{{ $project['number'] }}</span>
                                <h3>{{ $project['title'] }}</h3>
                            </div>
                            <span class="project-status">{{ $project['status'] }}</span>
                        </div>

                        <dl class="project-story">
                            <div><dt>Contexto</dt><dd>{{ $project['context'] }}</dd></div>
                            <div><dt>O que faz</dt><dd>{{ $project['action'] }}</dd></div>
                            <div><dt>Resultado</dt><dd>{{ $project['result'] }}</dd></div>
                        </dl>

                        <div class="project-showcase-footer">
                            <div class="chip-list" aria-label="Tecnologias de {{ $project['title'] }}">
                                @foreach ($project['stack'] as $technology)
                                    <x-technology-badge :name="$technology" />
                                @endforeach
                            </div>
                            @if (!empty($project['url']))
                                <span class="project-site-cta" aria-hidden="true">↗</span>
                            @endif
                        </div>
                    </div>

                @if (!empty($project['url']))
                    </a>
                @else
                    </article>
                @endif
                </div>
            @endforeach
            </div>

            @if (count($portfolio['projects']) > 1)
                <div class="project-carousel-navigation" aria-label="Navegação dos projetos">
                    <button type="button" class="project-carousel-arrow" @click="previous()" aria-label="Ver projeto anterior">
                        <span aria-hidden="true">&lt;</span>
                    </button>

                    <div class="project-carousel-dots" role="group" aria-label="Selecionar projeto">
                        @foreach ($portfolio['projects'] as $project)
                            <button
                                type="button"
                                @click="current = {{ $loop->index }}"
                                :class="{ 'is-active': current === {{ $loop->index }} }"
                                :aria-current="current === {{ $loop->index }} ? 'true' : 'false'"
                                aria-label="Ver projeto {{ $loop->iteration }}: {{ $project['title'] }}"
                            ></button>
                        @endforeach
                    </div>

                    <button type="button" class="project-carousel-arrow" @click="next()" aria-label="Ver próximo projeto">
                        <span aria-hidden="true">&gt;</span>
                    </button>

                    <span class="sr-only" x-text="`Projeto ${current + 1} de ${total}`"></span>
                </div>
            @endif
        </div>
    </div>
</section>
