@php
    $duolingoProfileUrl = $duolingo['username']
        ? 'https://www.duolingo.com/profile/'.urlencode($duolingo['username'])
        : null;
    $duolingoPrimaryCourse = collect($duolingo['courses'])->first();
@endphp

<section id="estudos" class="section section-alt duolingo-section" data-nav-owner="laboratorio">
    <div class="container-shell">
        <div class="section-header duolingo-section-header">
            <div class="brand-section-title">
                <span class="brand-mark-large brand-mark-duolingo"><x-icons.duolingo variant="mascot" /></span>
                <div>
                    <span class="section-kicker">Duolingo · Idiomas</span>
                    <h2>Progresso no Duolingo.</h2>
                </div>
            </div>
            <p>Snapshots diários do perfil público, com XP, sequência e evolução por idioma. A home preserva o último dado válido quando a fonte fica indisponível.</p>
        </div>

        @if (!$duolingo['configured'])
            <div class="integration-empty duolingo-empty">
                <span class="brand-mark-large brand-mark-duolingo"><x-icons.duolingo variant="mascot" /></span>
                <div><strong>Perfil do Duolingo ainda não configurado.</strong><p>A integração precisa apenas do nome público de usuário; senha e cookie de sessão nunca são solicitados.</p></div>
            </div>
        @elseif (!$duolingo['courses'])
            <div class="integration-empty duolingo-empty">
                <span class="brand-mark-large brand-mark-duolingo"><x-icons.duolingo variant="mascot" /></span>
                <div><strong>Aguardando o primeiro snapshot de {{ '@'.$duolingo['username'] }}.</strong><p>O último dado válido será preservado caso a fonte externa falhe.</p></div>
            </div>
        @else
            <div class="duolingo-dashboard">
            <article class="duolingo-profile-panel">
                <div class="duolingo-profile-identity">
                    <span class="duolingo-avatar"><x-icons.duolingo /></span>
                    <div>
                        <span class="card-kicker">Perfil público conectado</span>
                        <h3>{{ '@'.$duolingo['username'] }}</h3>
                        <p>{{ $duolingo['stale'] ? 'Último snapshot preservado' : 'Sincronização ativa a cada 6 horas' }}</p>
                    </div>
                </div>

                <dl class="duolingo-profile-metrics">
                    <div><dt>Sequência</dt><dd><span aria-hidden="true">🔥</span> {{ number_format($duolingoPrimaryCourse['streak'], 0, ',', '.') }} dias</dd></div>
                    <div><dt>XP total</dt><dd>{{ number_format($duolingoPrimaryCourse['total_xp'], 0, ',', '.') }}</dd></div>
                    <div><dt>Idiomas</dt><dd>{{ count($duolingo['courses']) }}</dd></div>
                </dl>

                <a class="button duolingo-profile-button" href="{{ $duolingoProfileUrl }}" target="_blank" rel="noopener noreferrer">
                    <x-icons.duolingo /> Ver perfil <span aria-hidden="true">↗</span>
                </a>
            </article>

            <div class="duolingo-content-head">
                <div><span class="card-kicker">Progresso por idioma</span><h3>Cursos ativos</h3></div>
                @if ($duolingo['last_collected_at'])
                    <span>Atualizado em {{ $duolingo['last_collected_at']->timezone($portfolio['presentation_timezone'])->format('d/m/Y H:i') }}</span>
                @endif
            </div>

            <div class="duolingo-grid">
                @foreach ($duolingo['courses'] as $course)
                    <article class="duolingo-card">
                        <div class="card-head">
                            <div class="duolingo-course-title">
                                <span class="language-code" aria-hidden="true">{{ mb_strtoupper($course['language']) }}</span>
                                <div><span class="card-kicker">Curso ativo</span><h3>{{ $course['language_name'] }}</h3></div>
                            </div>
                            <span class="duolingo-live-badge"><span aria-hidden="true"></span> sincronizado</span>
                        </div>

                        <dl class="duolingo-metrics">
                            <div><dt>XP no curso</dt><dd>{{ number_format($course['course_xp'], 0, ',', '.') }}</dd></div>
                            @php
                                $chartPoints = array_slice($course['points'], -7);
                                $courseFirstPoint = collect($chartPoints)->first();
                                $courseLastPoint = collect($chartPoints)->last();
                                $courseXpChange = (int) ($courseLastPoint['xp'] ?? 0) - (int) ($courseFirstPoint['xp'] ?? 0);
                            @endphp
                            <div><dt>Evolução</dt><dd class="{{ $courseXpChange >= 0 ? 'is-positive' : 'is-negative' }}">{{ $courseXpChange > 0 ? '+' : '' }}{{ number_format($courseXpChange, 0, ',', '.') }} XP</dd></div>
                        </dl>

                        <div class="duolingo-progress-visual">
                        @if (count($course['points']) >= 2)
                            @php
                                $xpValues = collect($chartPoints)->pluck('xp');
                                $xpMin = (int) $xpValues->min();
                                $xpMax = (int) $xpValues->max();
                                $xpMid = (int) round(($xpMax + $xpMin) / 2);
                                $xpRange = max(1, $xpMax - $xpMin);
                                $xpFlat = $xpMax === $xpMin;
                                $pointCount = count($chartPoints);
                                $polyline = collect($chartPoints)->map(function ($point, $index) use ($pointCount, $xpMin, $xpRange, $xpFlat) {
                                    $x = 58 + (($index / ($pointCount - 1)) * 248);
                                    $y = $xpFlat ? 54 : 82 - ((($point['xp'] - $xpMin) / $xpRange) * 56);
                                    return round($x, 2).','.round($y, 2);
                                })->implode(' ');
                            @endphp
                            <div class="duolingo-chart-shell">
                                <div class="duolingo-chart-heading">
                                    <div><span>Evolução recente</span><strong>{{ $pointCount }} registros</strong></div>
                                </div>
                                <svg class="duolingo-chart" viewBox="0 0 320 112" role="img" aria-label="Gráfico de XP por data em {{ $course['language_name'] }} nos últimos {{ $pointCount }} registros">
                                    <defs><linearGradient id="duolingo-chart-fill-{{ $loop->index }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity=".24"/><stop offset="1" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
                                    <line class="duolingo-chart-gridline" x1="58" y1="26" x2="306" y2="26" />
                                    <line class="duolingo-chart-gridline" x1="58" y1="54" x2="306" y2="54" />
                                    <line class="duolingo-chart-gridline" x1="58" y1="82" x2="306" y2="82" />
                                    <line class="duolingo-chart-axis" x1="58" y1="18" x2="58" y2="82" />
                                    <line class="duolingo-chart-axis" x1="58" y1="82" x2="310" y2="82" />
                                    <text class="duolingo-chart-axis-title" x="12" y="14">XP</text>
                                    <text class="duolingo-chart-axis-label" x="49" y="29" text-anchor="end">{{ number_format($xpMax, 0, ',', '.') }}</text>
                                    <text class="duolingo-chart-axis-label" x="49" y="57" text-anchor="end">{{ number_format($xpMid, 0, ',', '.') }}</text>
                                    <text class="duolingo-chart-axis-label" x="49" y="85" text-anchor="end">{{ number_format($xpMin, 0, ',', '.') }}</text>
                                    <polyline points="58,82 {{ $polyline }} 306,82" fill="url(#duolingo-chart-fill-{{ $loop->index }})" stroke="none" />
                                    <polyline points="{{ $polyline }}" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                    @foreach ($chartPoints as $pointIndex => $point)
                                        @php
                                            $circleX = 58 + (($pointIndex / ($pointCount - 1)) * 248);
                                            $circleY = $xpFlat ? 54 : 82 - ((($point['xp'] - $xpMin) / $xpRange) * 56);
                                        @endphp
                                        <circle cx="{{ round($circleX, 2) }}" cy="{{ round($circleY, 2) }}" r="4.5" class="duolingo-chart-point">
                                            <title>{{ \Carbon\CarbonImmutable::parse($point['date'])->format('d/m/Y') }}: {{ number_format($point['xp'], 0, ',', '.') }} XP</title>
                                        </circle>
                                        <text class="duolingo-chart-axis-label duolingo-chart-date" x="{{ round($circleX, 2) }}" y="104" text-anchor="middle">{{ \Carbon\CarbonImmutable::parse($point['date'])->format('d/m') }}</text>
                                    @endforeach
                                </svg>
                            </div>
                        @else
                            <div class="duolingo-chart-placeholder">
                                <div class="placeholder-bars" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
                                <strong>Construindo sua linha de evolução</strong>
                                <p>O gráfico será liberado após o próximo snapshot diário.</p>
                            </div>
                        @endif
                        </div>
                    </article>
                @endforeach
            </div>
            </div>

            <p class="integration-disclaimer"><span aria-hidden="true">ⓘ</span> Fonte pública não oficial. Nenhuma senha, sessão ou payload bruto é armazenado.</p>
        @endif
    </div>
</section>
