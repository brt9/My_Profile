@php
    $isPortfolioAdmin = auth()->check()
        && filled(config('portfolio.admin_email'))
        && hash_equals(mb_strtolower((string) config('portfolio.admin_email')), mb_strtolower((string) auth()->user()->email));
    $calendarCategoryLabels = [
        'reuniao' => 'Reunião',
        'tarefa' => 'Tarefa',
        'estudo' => 'Estudo',
        'entrega' => 'Entrega',
        'projeto' => 'Projeto',
        'ocupado' => 'Compromisso',
    ];
    $calendarSourceLabels = ['google' => 'Google Agenda', 'local' => 'Agenda local'];
    $calendarStatusLabels = ['confirmado' => 'Confirmado', 'provisorio' => 'Provisório'];
@endphp

<section id="agenda" class="section">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Disponibilidade</span>
                <h2>Agenda de trabalho e estudos.</h2>
            </div>
            <p>Consulte reuniões, blocos de desenvolvimento, estudos e entregas pela semana ou pelo mês. Somente título público e horário são exibidos.</p>
        </div>

        @if (session('calendar_status'))
            <p class="integration-flash" role="status">{{ session('calendar_status') }}</p>
        @endif

        <div class="calendar-shell" data-calendar-shell data-week-label="{{ $calendar['range_label'] }}" data-month-label="{{ $calendar['month_label'] }}">
            <div class="calendar-toolbar">
                <div class="calendar-view-label">
                    <strong data-calendar-period-title>Próximos 7 dias</strong>
                    <span data-calendar-period-label>{{ $calendar['range_label'] }}</span>
                </div>

                <div class="calendar-view-switch" role="group" aria-label="Visualização da agenda">
                    <button type="button" class="is-active" data-calendar-view-button="week" aria-pressed="true">Semana</button>
                    <button type="button" data-calendar-view-button="month" aria-pressed="false">Mês</button>
                </div>

                @if ($calendar['last_synced_at'])
                    <span class="sync-note">{{ $calendar['status'] === 'connected' ? 'Google sincronizado' : 'Último snapshot Google' }} em {{ $calendar['last_synced_at']->timezone($portfolio['presentation_timezone'])->format('d/m H:i') }}</span>
                @endif
            </div>

            @if ($calendar['status'] === 'reauth_required')
                <div class="integration-empty calendar-warning">
                    <strong>A autorização do Google expirou ou foi revogada.</strong>
                    <p>Os compromissos locais e o último snapshot continuam disponíveis. Reconecte apenas para voltar a sincronizar.</p>
                </div>
            @endif

            <div class="calendar-table-wrap" data-calendar-view-panel="week">
                <table class="calendar-table">
                    <thead><tr><th scope="col">Dia</th><th scope="col">Compromissos</th></tr></thead>
                    <tbody>
                        @foreach ($calendar['days'] as $day)
                            <tr class="{{ $day['is_today'] ? 'is-today' : '' }}">
                                <th scope="row"><strong>{{ $day['weekday'] }}</strong><span>{{ $day['label'] }}</span></th>
                                <td>
                                    @if (count($day['events']) === 0)
                                        <span class="calendar-empty-day">Disponível</span>
                                    @else
                                        <div class="calendar-gantt" aria-label="{{ count($day['events']) }} {{ count($day['events']) === 1 ? 'compromisso' : 'compromissos' }} neste dia">
                                            <div class="calendar-gantt-hours" aria-hidden="true"><span>00h</span><span>06h</span><span>12h</span><span>18h</span><span>24h</span></div>
                                            <div class="calendar-track" style="--event-rows: {{ count($day['events']) }}">
                                                @foreach ($day['events'] as $event)
                                                    <button type="button"
                                                        class="calendar-event category-{{ $event['category'] }} {{ $event['all_day'] ? 'is-all-day' : '' }}"
                                                        style="--event-start: {{ $event['offset'] }}%; --event-width: {{ $event['width'] }}%; --event-row: {{ $loop->index }}"
                                                        data-calendar-event-open
                                                        data-event-title="{{ $event['title'] }}"
                                                        data-event-date="{{ $event['detail_date'] }}"
                                                        data-event-time="{{ $event['detail_time'] }}"
                                                        data-event-duration="{{ $event['duration'] }}"
                                                        data-event-category="{{ $event['category'] }}"
                                                        data-event-category-label="{{ $calendarCategoryLabels[$event['category']] ?? ucfirst($event['category']) }}"
                                                        data-event-source="{{ $calendarSourceLabels[$event['source']] ?? ucfirst($event['source']) }}"
                                                        data-event-status="{{ $calendarStatusLabels[$event['status']] ?? ucfirst($event['status']) }}"
                                                        aria-haspopup="dialog"
                                                        aria-label="Ver detalhes de {{ $event['title'] }}, {{ $event['time'] }}">
                                                        <strong>{{ $event['title'] }}</strong>
                                                        <span>{{ $event['time'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="calendar-month-wrap" data-calendar-view-panel="month" hidden>
                <div class="calendar-month-weekdays" aria-hidden="true">
                    @foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $weekday)
                        <span>{{ $weekday }}</span>
                    @endforeach
                </div>
                <div class="calendar-month-grid" aria-label="Agenda de {{ $calendar['month_label'] }}">
                    @foreach ($calendar['month_days'] as $day)
                        <article class="calendar-month-day {{ !$day['is_current_month'] ? 'is-outside' : '' }} {{ $day['is_today'] ? 'is-today' : '' }}">
                            <time datetime="{{ $day['date'] }}">{{ $day['day'] }}</time>
                            <div class="calendar-month-events">
                                @foreach (array_slice($day['events'], 0, 3) as $event)
                                    <button type="button"
                                        class="calendar-month-event category-{{ $event['category'] }}"
                                        data-calendar-event-open
                                        data-event-title="{{ $event['title'] }}"
                                        data-event-date="{{ $event['detail_date'] }}"
                                        data-event-time="{{ $event['detail_time'] }}"
                                        data-event-duration="{{ $event['duration'] }}"
                                        data-event-category="{{ $event['category'] }}"
                                        data-event-category-label="{{ $calendarCategoryLabels[$event['category']] ?? ucfirst($event['category']) }}"
                                        data-event-source="{{ $calendarSourceLabels[$event['source']] ?? ucfirst($event['source']) }}"
                                        data-event-status="{{ $calendarStatusLabels[$event['status']] ?? ucfirst($event['status']) }}"
                                        aria-haspopup="dialog"
                                        aria-label="Ver detalhes de {{ $event['title'] }}, {{ $event['time'] }}">
                                        <span>{{ $event['time'] }}</span>
                                        <strong>{{ $event['title'] }}</strong>
                                    </button>
                                @endforeach
                                @if (count($day['events']) > 3)
                                    <span class="calendar-month-more">+{{ count($day['events']) - 3 }} compromisso(s)</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            @if ($isPortfolioAdmin)
                <div class="calendar-manager" data-calendar-manager data-endpoint="{{ route('calendar.events.store') }}">
                    <div class="calendar-manager-heading">
                        <div>
                            <span class="section-kicker">Administração local</span>
                            <h3>Adicionar à agenda</h3>
                        </div>
                        <span class="calendar-manager-badge">Banco local</span>
                    </div>

                    <p class="calendar-manager-copy">Cadastre reuniões, tarefas, períodos de estudo ou entregas. O registro local funciona mesmo sem conexão com o Google.</p>

                    <form class="calendar-event-form" data-calendar-event-form novalidate>
                        <input type="hidden" name="event_id">
                        <label class="calendar-field calendar-field-wide">
                            <span>Título público</span>
                            <input name="title" type="text" maxlength="100" required placeholder="Ex.: Reunião de implantação">
                        </label>
                        <label class="calendar-field">
                            <span>Categoria</span>
                            <select name="category" required>
                                <option value="reuniao">Reunião</option>
                                <option value="tarefa">Tarefa</option>
                                <option value="estudo">Estudo</option>
                                <option value="entrega">Entrega</option>
                                <option value="projeto">Projeto</option>
                                <option value="ocupado">Ocupado</option>
                            </select>
                        </label>
                        <label class="calendar-field">
                            <span>Início</span>
                            <input name="starts_at" type="datetime-local" required>
                        </label>
                        <label class="calendar-field">
                            <span>Fim</span>
                            <input name="ends_at" type="datetime-local" required>
                        </label>
                        <label class="calendar-check">
                            <input name="all_day" type="checkbox" value="1">
                            <span>Dia inteiro</span>
                        </label>
                        @if ($calendar['write_enabled'])
                            <label class="calendar-check">
                                <input name="sync_google" type="checkbox" value="1">
                                <span>Também enviar ao Google</span>
                            </label>
                        @endif
                        <div class="calendar-form-actions">
                            <button class="button button-primary" type="submit">Salvar compromisso</button>
                            <button class="text-button" type="button" data-calendar-cancel hidden>Cancelar edição</button>
                        </div>
                        <p class="calendar-form-status" data-calendar-status role="status" aria-live="polite"></p>
                    </form>

                    @if (count($calendar['manageable_events']) > 0)
                        <div class="calendar-managed-list" aria-label="Compromissos administráveis">
                            @foreach ($calendar['manageable_events'] as $event)
                                <article class="calendar-managed-item"
                                    data-calendar-item
                                    data-id="{{ $event['id'] }}"
                                    data-title="{{ $event['title'] }}"
                                    data-category="{{ $event['category'] }}"
                                    data-starts-at="{{ $event['starts_at'] }}"
                                    data-ends-at="{{ $event['ends_at'] }}"
                                    data-all-day="{{ $event['all_day'] ? '1' : '0' }}">
                                    <div>
                                        <strong>{{ $event['title'] }}</strong>
                                        <span>{{ \Carbon\CarbonImmutable::parse($event['starts_at'])->format('d/m H:i') }} · {{ ucfirst($event['category']) }}</span>
                                    </div>
                                    <div class="calendar-item-actions">
                                        <button class="text-button" type="button" data-calendar-edit>Editar</button>
                                        <button class="text-button is-danger" type="button" data-calendar-delete>Excluir</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="admin-integration-actions" aria-label="Administração do Google Agenda">
                    @if (!$calendar['configured'])
                        <span>Google opcional: configure o cliente OAuth no <code>.env</code> para ativar a sincronização.</span>
                    @elseif (!$calendar['connected'] || $calendar['status'] === 'reauth_required')
                        <a class="button button-secondary" href="{{ route('calendar.connect') }}">Conectar Google Agenda</a>
                    @else
                        <a class="button button-secondary" href="{{ route('calendar.connect') }}">Atualizar autorização</a>
                        <form method="POST" action="{{ route('calendar.sync') }}">@csrf<button class="button button-secondary" type="submit">Sincronizar agora</button></form>
                        <form method="POST" action="{{ route('calendar.revoke') }}">@csrf @method('DELETE')<button class="text-button" type="submit">Revogar acesso</button></form>
                    @endif
                </div>
            @endif
        </div>

        <dialog class="calendar-event-dialog" data-calendar-event-dialog aria-labelledby="calendar-event-dialog-title">
            <form method="dialog" class="calendar-event-dialog-card">
                <button type="submit" class="calendar-event-dialog-close" aria-label="Fechar detalhes do compromisso">×</button>

                <div class="calendar-event-dialog-heading">
                    <span class="calendar-event-dialog-category" data-calendar-dialog-category>Compromisso</span>
                    <h3 id="calendar-event-dialog-title" data-calendar-dialog-title></h3>
                    <p>Detalhes públicos do compromisso selecionado.</p>
                </div>

                <dl class="calendar-event-dialog-details">
                    <div><dt>Data</dt><dd data-calendar-dialog-date></dd></div>
                    <div><dt>Horário</dt><dd data-calendar-dialog-time></dd></div>
                    <div><dt>Duração</dt><dd data-calendar-dialog-duration></dd></div>
                    <div><dt>Origem</dt><dd data-calendar-dialog-source></dd></div>
                </dl>

                <div class="calendar-event-dialog-footer">
                    <span class="calendar-event-dialog-status"><span aria-hidden="true"></span><span data-calendar-dialog-status></span></span>
                    <button type="submit" class="button button-secondary">Fechar</button>
                </div>
            </form>
        </dialog>
    </div>
</section>
