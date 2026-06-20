<section id="lab" class="section">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Lab pessoal</span>
                <h2>Meu setup também<br>gera dados.</h2>
            </div>
            <p>Uma área técnica para demonstrar coleta, persistência, histórico e degradação segura sem misturar o conteúdo profissional principal.</p>
        </div>

        @php
            $parts = [
                ['label' => 'Processador', 'value' => 'Intel Core i5-14600K'],
                ['label' => 'Placa de vídeo', 'value' => 'RTX 4060 Ti 8GB'],
                ['label' => 'Memória', 'value' => '64GB DDR5-6200'],
                ['label' => 'Armazenamento', 'value' => 'NVMe Kingston 1TB'],
                ['label' => 'Refrigeração', 'value' => 'Corsair H100i LCD'],
                ['label' => 'Sistema', 'value' => 'Windows + Docker'],
            ];
        @endphp

        <div class="lab-grid">
            <article class="panel">
                <h3>Estação de desenvolvimento</h3>
                <p>Configuração preparada para Laravel, containers, múltiplos serviços locais e jogos em 1080p/1440p.</p>
                <div class="setup-summary">
                    @foreach ($parts as $part)
                        <div class="setup-part">
                            <small>{{ $part['label'] }}</small>
                            <strong>{{ $part['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </article>

            <article
                class="panel telemetry-panel"
                x-data="telemetryPanel()"
                x-init="init()"
                @keydown.escape.window="closeHistory()"
            >
                <div class="telemetry-head">
                    <div>
                        <span class="card-kicker">API + PostgreSQL</span>
                        <h3>Telemetria ao vivo e histórica</h3>
                    </div>
                    <span class="live-status" :class="`is-${status}`" x-text="statusLabel()"></span>
                </div>

                <div class="metric-grid telemetry-metric-grid">
                    <template x-for="metric in metrics" :key="metric.key">
                        <button
                            type="button"
                            class="metric metric-button"
                            :disabled="!supported(metric.key)"
                            :aria-label="supported(metric.key) ? `Abrir histórico de ${metric.label}` : `${metric.label}: não suportado`"
                            @click="openHistory(metric)"
                        >
                            <small x-text="metric.label"></small>
                            <strong x-text="metricValue(metric)" :class="metricClass(metric.key)">Carregando…</strong>
                            <span class="metric-action" x-show="supported(metric.key) && status !== 'loading'">Ver histórico ↗</span>
                        </button>
                    </template>
                </div>

                <p class="integration-note" aria-live="polite" x-text="message"></p>

                <div
                    class="telemetry-modal-backdrop"
                    x-show="history.open"
                    x-transition.opacity
                    @click.self="closeHistory()"
                    x-cloak
                >
                    <section
                        class="telemetry-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="telemetry-history-title"
                        x-ref="historyDialog"
                        tabindex="-1"
                    >
                        <div class="telemetry-modal-head">
                            <div>
                                <span class="card-kicker">Histórico sem interpolação</span>
                                <h3 id="telemetry-history-title" x-text="history.label"></h3>
                            </div>
                            <button type="button" class="icon-button" @click="closeHistory()" aria-label="Fechar histórico">×</button>
                        </div>

                        <div class="history-filters" aria-label="Intervalo do histórico">
                            <template x-for="range in ranges" :key="range">
                                <button
                                    type="button"
                                    :class="{ 'is-active': history.range === range }"
                                    :aria-pressed="history.range === range"
                                    @click="changeRange(range)"
                                    x-text="range"
                                ></button>
                            </template>
                        </div>

                        <p class="integration-note" x-show="history.loading">Carregando histórico…</p>
                        <p class="integration-note" role="alert" x-show="history.error" x-text="history.error"></p>

                        <div class="history-chart-wrap" x-show="!history.loading && !history.error">
                            <canvas x-ref="historyChart" role="img" :aria-label="`Gráfico de ${history.label} nas últimas ${history.range}`"></canvas>
                        </div>

                        <dl class="history-summary" x-show="history.summary">
                            <div><dt>Mínimo</dt><dd x-text="summaryValue('minimum')"></dd></div>
                            <div><dt>Média</dt><dd x-text="summaryValue('average')"></dd></div>
                            <div><dt>Máximo</dt><dd x-text="summaryValue('maximum')"></dd></div>
                            <div><dt>Amostras</dt><dd x-text="history.summary?.samples ?? '—'"></dd></div>
                        </dl>

                        <div class="history-table-wrap" x-show="history.points.length">
                            <table class="history-table">
                                <caption>Últimos pontos disponíveis; lacunas não são preenchidas.</caption>
                                <thead><tr><th scope="col">Horário</th><th scope="col">Valor</th></tr></thead>
                                <tbody>
                                    <template x-for="point in availableHistoryPoints()" :key="point.at">
                                        <tr>
                                            <td x-text="formatDate(point.at)"></td>
                                            <td x-text="`${point.value}${history.unit}`"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </article>
        </div>
    </div>
</section>

@push('scripts')
    <script>
        function telemetryPanel() {
            return {
                data: {},
                status: 'loading',
                collectedAt: null,
                machineStatus: 'offline',
                message: 'Carregando telemetria…',
                timer: null,
                controller: null,
                loading: false,
                chart: null,
                previousFocus: null,
                ranges: ['1h', '6h', '12h', '24h'],
                metrics: [
                    { key: 'cpu_load', label: 'Uso da CPU', suffix: '%', digits: 1 },
                    { key: 'cpu_temp', label: 'Temperatura da CPU', suffix: '°C', digits: 1 },
                    { key: 'gpu_load', label: 'Uso da GPU', suffix: '%', digits: 1 },
                    { key: 'gpu_temp', label: 'Temperatura da GPU', suffix: '°C', digits: 1 },
                    { key: 'memory_usage', label: 'Memória', suffix: '%', digits: 1 },
                    { key: 'disk_usage', label: 'Disco principal', suffix: '%', digits: 1 },
                    { key: 'uptime_seconds', label: 'Tempo ligado', suffix: 's', digits: 0 },
                    { key: 'pump_rpm', label: 'Bomba AIO', suffix: ' RPM', digits: 0 },
                    { key: 'coolant_temp', label: 'Líquido', suffix: '°C', digits: 1 },
                ],
                history: {
                    open: false,
                    loading: false,
                    error: '',
                    metric: '',
                    label: '',
                    unit: '',
                    range: '6h',
                    points: [],
                    summary: null,
                },
                async load() {
                    if (this.loading) return;
                    this.loading = true;
                    this.controller = new AbortController();
                    const timeout = setTimeout(() => this.controller.abort(), 8000);
                    try {
                        const response = await fetch('{{ url('/api/telemetry/latest') }}', {
                            cache: 'no-store',
                            signal: this.controller.signal,
                        });
                        if (!response.ok) throw new Error('Sem resposta');
                        const payload = await response.json();
                        this.status = payload.status ?? 'error';
                        this.data = payload.data ?? {};
                        this.collectedAt = payload.meta?.collected_at ?? null;
                        this.machineStatus = payload.meta?.machine_status ?? 'offline';
                        this.message = this.statusMessage();
                    } catch (_) {
                        this.status = 'error';
                        this.message = 'Telemetria indisponível no momento.';
                    } finally {
                        clearTimeout(timeout);
                        this.controller = null;
                        this.loading = false;
                    }
                },
                supported(key) {
                    const raw = this.data[key];
                    return !['loading', 'unavailable', 'error'].includes(this.status)
                        && raw !== null && raw !== undefined && raw !== '';
                },
                value(key, suffix, digits = 1) {
                    const raw = this.data[key];
                    if (this.status === 'loading') return 'Carregando…';
                    if (this.status === 'unavailable' || this.status === 'error') return '—';
                    if (raw === null || raw === undefined || raw === '') return 'Não suportado neste dispositivo';
                    const numeric = Number(raw);
                    return Number.isFinite(numeric) ? `${numeric.toFixed(digits)}${suffix}` : 'Não suportado neste dispositivo';
                },
                metricValue(metric) {
                    if (metric.key !== 'uptime_seconds') return this.value(metric.key, metric.suffix, metric.digits);
                    if (!this.supported(metric.key)) return this.value(metric.key, metric.suffix, metric.digits);
                    const seconds = Number(this.data[metric.key]);
                    const days = Math.floor(seconds / 86400);
                    const hours = Math.floor((seconds % 86400) / 3600);
                    return days > 0 ? `${days}d ${hours}h` : `${hours}h`;
                },
                statusLabel() {
                    return ({ loading: 'carregando', available: 'online', stale: this.machineStatus === 'offline' ? 'offline' : 'defasado', unavailable: 'offline', error: 'erro' })[this.status] ?? 'offline';
                },
                metricClass(key) {
                    return this.supported(key) || this.status === 'loading' ? '' : 'metric-unsupported';
                },
                statusMessage() {
                    if (this.status === 'available' && this.collectedAt) {
                        return `Atualizado em ${new Date(this.collectedAt).toLocaleTimeString('pt-BR')}`;
                    }
                    if (this.status === 'stale' && this.collectedAt) {
                        return `Leitura ${this.machineStatus === 'offline' ? 'offline' : 'defasada'}. Última atualização em ${new Date(this.collectedAt).toLocaleString('pt-BR')}.`;
                    }
                    return 'Telemetria indisponível no momento.';
                },
                async openHistory(metric) {
                    if (!this.supported(metric.key)) return;
                    this.previousFocus = document.activeElement;
                    Object.assign(this.history, { open: true, metric: metric.key, label: metric.label, unit: metric.suffix.trim(), error: '' });
                    document.body.classList.add('modal-open');
                    await this.$nextTick();
                    this.$refs.historyDialog?.focus();
                    await this.loadHistory();
                },
                closeHistory() {
                    if (!this.history.open) return;
                    this.history.open = false;
                    this.chart?.destroy();
                    this.chart = null;
                    document.body.classList.remove('modal-open');
                    this.previousFocus?.focus();
                },
                async changeRange(range) {
                    if (this.history.range === range) return;
                    this.history.range = range;
                    await this.loadHistory();
                },
                async loadHistory() {
                    this.history.loading = true;
                    this.history.error = '';
                    try {
                        const params = new URLSearchParams({ metric: this.history.metric, range: this.history.range });
                        const response = await fetch(`{{ url('/api/telemetry/history') }}?${params}`, { cache: 'no-store' });
                        if (!response.ok) throw new Error('Histórico indisponível');
                        const payload = await response.json();
                        this.history.points = payload.data?.points ?? [];
                        this.history.summary = payload.data?.summary ?? null;
                        this.history.unit = payload.meta?.unit ?? this.history.unit;
                        if (payload.status !== 'available') {
                            this.history.error = 'Ainda não há dados suficientes para este período.';
                            return;
                        }
                        await this.$nextTick();
                        await this.renderChart();
                    } catch (_) {
                        this.history.error = 'Não foi possível carregar o histórico agora.';
                    } finally {
                        this.history.loading = false;
                    }
                },
                async renderChart() {
                    const Chart = await window.loadTelemetryChart();
                    this.chart?.destroy();
                    const styles = getComputedStyle(document.documentElement);
                    this.chart = new Chart(this.$refs.historyChart, {
                        type: 'line',
                        data: {
                            labels: this.history.points.map(point => this.formatDate(point.at)),
                            datasets: [{
                                label: `${this.history.label} (${this.history.unit})`,
                                data: this.history.points.map(point => point.value),
                                borderColor: styles.getPropertyValue('--accent').trim(),
                                backgroundColor: 'rgba(109, 74, 255, 0.12)',
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                spanGaps: false,
                                tension: 0.2,
                                fill: true,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: { intersect: false, mode: 'index' },
                            scales: { y: { beginAtZero: false } },
                        },
                    });
                },
                summaryValue(key) {
                    const value = this.history.summary?.[key];
                    return value === null || value === undefined ? '—' : `${value}${this.history.unit}`;
                },
                availableHistoryPoints() {
                    return this.history.points.filter(point => point.value !== null).slice(-12).reverse();
                },
                formatDate(value) {
                    return new Date(value).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                },
                init() {
                    this.load();
                    this.timer = setInterval(() => this.load(), 10000);
                },
                destroy() {
                    clearInterval(this.timer);
                    this.controller?.abort();
                    this.chart?.destroy();
                    document.body.classList.remove('modal-open');
                },
            };
        }
    </script>
@endpush
