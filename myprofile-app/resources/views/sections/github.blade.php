<article class="github-card">
    <div class="card-head">
        <div class="integration-brand-heading">
            <span class="brand-mark-medium brand-mark-github"><x-icons.github /></span>
            <div>
                <span class="card-kicker">GitHub API</span>
                <h3>Código aberto e ritmo de contribuição</h3>
            </div>
        </div>
        <a href="{{ $portfolio['social']['github'] }}" target="_blank" rel="noopener noreferrer" class="github-profile-link"><x-icons.github /> @brt9 <span aria-hidden="true">↗</span></a>
    </div>

    @if ($github)
        @php($contributionCalendar = $github['activity']['calendar'] ?? null)
        <div data-github-activity x-data="githubActivityCalendar(
            {{ \Illuminate\Support\Js::from($contributionCalendar) }},
            {{ \Illuminate\Support\Js::from(route('github.contributions')) }},
            {{ (int) ($github['profile']['created_year'] ?? 2008) }},
            {{ now()->utc()->year }}
        )">
        <div class="github-summary">
            <div class="github-profile">
                <span class="github-avatar-wrap">
                    <span class="github-avatar-fallback" aria-hidden="true">PF</span>
                    <img src="{{ $github['profile']['avatar'] }}" alt="Avatar de {{ $github['profile']['login'] }}" loading="lazy">
                    <span class="github-avatar-badge" aria-hidden="true"><x-icons.github /></span>
                </span>
                <div>
                    <strong>{{ $github['profile']['name'] ?: $github['profile']['login'] }}</strong>
                    <span>{{ '@'.$github['profile']['login'] }}</span>
                </div>
            </div>

            <div class="github-metrics">
                <div><strong>{{ $github['profile']['public_repositories'] }}</strong><span>repositórios</span></div>
                <div>
                    <strong x-text="formatNumber(calendar.total)">{{ $contributionCalendar['total'] ?? $github['activity']['recent_events'] }}</strong>
                    <span x-text="`contribuições em ${calendar.year}`">contribuições em {{ $contributionCalendar['year'] }}</span>
                </div>
                <div><strong>{{ $github['profile']['followers'] }}</strong><span>seguidores</span></div>
                <div><strong>{{ $github['activity']['main_language'] ?? '—' }}</strong><span>linguagem principal</span></div>
            </div>
        </div>

        @if ($contributionCalendar)
            <section class="github-activity" aria-labelledby="github-activity-title">
                <div class="github-activity-head">
                    <div>
                        <strong id="github-activity-title" x-text="`${formatNumber(calendar.total)} ${calendar.summary_label}`">{{ number_format($contributionCalendar['total'], 0, ',', '.') }} {{ $contributionCalendar['summary_label'] }}</strong>
                        <span x-text="`${calendar.active_days} dias com atividade registrada`">{{ $contributionCalendar['active_days'] }} dias com atividade registrada</span>
                    </div>
                    <div class="github-activity-actions">
                        <span class="github-activity-source" x-text="sourceLabel()">
                            {{ $contributionCalendar['source'] === 'github_profile' ? 'Perfil público do GitHub' : 'Eventos públicos da API' }}
                        </span>
                        <div class="github-year-nav" aria-label="Selecionar ano do calendário">
                            <button type="button" @click="previousYear()" :disabled="loading || calendar.year <= minYear" aria-label="Mostrar ano anterior">←</button>
                            <strong x-text="calendar.year">{{ $contributionCalendar['year'] }}</strong>
                            <button type="button" @click="nextYear()" :disabled="loading || calendar.year >= maxYear" aria-label="Mostrar próximo ano">→</button>
                        </div>
                    </div>
                </div>

                <div
                    class="github-calendar-scroll"
                    :class="{ 'is-loading': loading }"
                    :aria-busy="loading"
                    tabindex="0"
                    role="img"
                    :aria-label="`${calendar.total} ${calendar.summary_label}, com atividades distribuídas em ${calendar.active_days} dias`"
                >
                    <div class="github-calendar-shell" :style="`--github-week-count: ${calendar.weeks.length}`">
                        <div class="github-calendar-years" aria-hidden="true">
                            <template x-for="week in calendar.weeks" :key="`year-${week.start}`">
                                <span x-text="week.year"></span>
                            </template>
                        </div>
                        <div class="github-calendar-months" aria-hidden="true">
                            <template x-for="week in calendar.weeks" :key="`month-${week.start}`">
                                <span x-text="week.month"></span>
                            </template>
                        </div>
                        <div class="github-calendar-body">
                            <div class="github-calendar-weekdays" aria-hidden="true">
                                <span></span><span>Seg</span><span></span><span>Qua</span><span></span><span>Sex</span><span></span>
                            </div>
                            <div class="github-calendar-weeks" aria-hidden="true">
                                <template x-for="week in calendar.weeks" :key="week.start">
                                    <div class="github-calendar-week">
                                        <template x-for="day in week.days" :key="day.date">
                                            <span
                                                class="github-contribution-day"
                                                :class="[`is-level-${day.level}`, { 'is-outside': day.outside }]"
                                                :title="day.label"
                                            ></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="github-activity-foot">
                    <span x-text="sourceNote()">{{ $contributionCalendar['source'] === 'github_profile' ? 'Contribuições exibidas publicamente pelo GitHub.' : 'Fallback baseado somente nos eventos públicos disponíveis.' }}</span>
                    <span class="github-activity-legend" aria-label="Intensidade: menos para mais">
                        Menos
                        @for ($level = 0; $level <= 4; $level++)
                            <i class="github-contribution-day is-level-{{ $level }}" aria-hidden="true"></i>
                        @endfor
                        Mais
                    </span>
                </div>
                <p class="github-calendar-error" x-show="error" x-text="error" role="status" x-cloak></p>
            </section>
        @endif
        </div>

        <div class="github-repositories-heading">
            <strong>Projetos em destaque</strong>
            <span>Atualizados recentemente</span>
        </div>
        <div class="github-repositories">
            @foreach ($github['repositories'] as $repository)
                <a href="{{ $repository['url'] }}" target="_blank" rel="noopener noreferrer" class="github-repository">
                    <div>
                        <strong>{{ $repository['name'] }}</strong>
                        <p>{{ $repository['description'] ?: 'Repositório público no GitHub.' }}</p>
                    </div>
                    <div class="repo-meta">
                        @if ($repository['language'])<span>{{ $repository['language'] }}</span>@endif
                        <span>★ {{ $repository['stars'] }}</span>
                        <span>⑂ {{ $repository['forks'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="integration-note">A atividade do GitHub está temporariamente indisponível. O perfil continua acessível pelo link acima.</p>
    @endif
</article>

@push('scripts')
    <script>
        function githubActivityCalendar(initialCalendar, endpoint, minYear, maxYear) {
            return {
                calendar: initialCalendar,
                endpoint,
                minYear,
                maxYear,
                loading: false,
                error: '',
                formatNumber(value) {
                    return Number(value ?? 0).toLocaleString('pt-BR');
                },
                sourceLabel() {
                    return this.calendar.source === 'github_profile' ? 'Perfil público do GitHub' : 'Eventos públicos da API';
                },
                sourceNote() {
                    return this.calendar.source === 'github_profile'
                        ? 'Contribuições exibidas publicamente pelo GitHub.'
                        : 'Fallback baseado somente nos eventos públicos disponíveis.';
                },
                previousYear() {
                    return this.loadYear(this.calendar.year - 1);
                },
                nextYear() {
                    return this.loadYear(this.calendar.year + 1);
                },
                async loadYear(year) {
                    if (this.loading || year < this.minYear || year > this.maxYear || year === this.calendar.year) return;
                    this.loading = true;
                    this.error = '';
                    try {
                        const response = await fetch(`${this.endpoint}?year=${year}`, { cache: 'no-store' });
                        if (!response.ok) throw new Error('Ano indisponível');
                        const payload = await response.json();
                        if (!payload.data?.weeks) throw new Error('Calendário inválido');
                        this.calendar = payload.data;
                    } catch (_) {
                        this.error = 'Não foi possível carregar esse ano agora.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
