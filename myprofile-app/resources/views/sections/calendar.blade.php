@php
    $isPortfolioAdmin = auth()->check()
        && filled(config('portfolio.admin_email'))
        && hash_equals(mb_strtolower((string) config('portfolio.admin_email')), mb_strtolower((string) auth()->user()->email));
@endphp

<section id="agenda" class="section section-alt">
    <div class="container-shell">
        <div class="section-header">
            <div>
                <span class="section-kicker">Agenda</span>
                <h2>Próximos 7 dias.</h2>
            </div>
            <p>Os compromissos são mantidos no banco local. A sincronização com o Google é opcional e nunca impede a exibição do último dado salvo.</p>
        </div>

        @if (session('calendar_status'))
            <p class="integration-flash" role="status">{{ session('calendar_status') }}</p>
        @endif

        <div class="calendar-shell">
            <div class="calendar-toolbar">
                <div class="calendar-view-label">
                    <strong>Próximos 7 dias</strong>
                    <span>{{ $calendar['range_label'] }}</span>
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

            <div class="calendar-table-wrap">
                <table class="calendar-table">
                    <thead><tr><th scope="col">Dia</th><th scope="col">Compromissos</th></tr></thead>
                    <tbody>
                        @foreach ($calendar['days'] as $day)
                            <tr class="{{ $day['is_today'] ? 'is-today' : '' }}">
                                <th scope="row"><strong>{{ $day['weekday'] }}</strong><span>{{ $day['label'] }}</span></th>
                                <td>
                                    @if (count($day['events']) === 0)
                                        <span class="calendar-empty-day">Sem compromisso</span>
                                    @elseif (count($day['events']) === 1)
                                        @php($event = $day['events'][0])
                                        <div class="calendar-single-event category-{{ $event['category'] }}" aria-label="{{ $event['title'] }}, {{ $event['time'] }}">
                                            <strong>{{ $event['title'] }}</strong>
                                            <time>{{ $event['time'] }}</time>
                                        </div>
                                    @else
                                        <div class="calendar-gantt" aria-label="{{ count($day['events']) }} compromissos neste dia">
                                            <div class="calendar-gantt-hours" aria-hidden="true"><span>00h</span><span>06h</span><span>12h</span><span>18h</span><span>24h</span></div>
                                            <div class="calendar-track" style="--event-rows: {{ count($day['events']) }}">
                                                @foreach ($day['events'] as $event)
                                                    <div class="calendar-event category-{{ $event['category'] }}"
                                                        style="--event-start: {{ $event['offset'] }}%; --event-width: {{ $event['width'] }}%; --event-row: {{ $loop->index }}"
                                                        tabindex="0"
                                                        aria-label="{{ $event['title'] }}, {{ $event['time'] }}">
                                                        <strong>{{ $event['title'] }}</strong>
                                                        <span>{{ $event['time'] }}</span>
                                                    </div>
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

            @if ($isPortfolioAdmin)
                <div class="calendar-manager" data-calendar-manager data-endpoint="{{ route('calendar.events.store') }}">
                    <div class="calendar-manager-heading">
                        <div>
                            <span class="section-kicker">Administração local</span>
                            <h3>Novo compromisso</h3>
                        </div>
                        <span class="calendar-manager-badge">Banco local</span>
                    </div>

                    <form class="calendar-event-form" data-calendar-event-form novalidate>
                        <input type="hidden" name="event_id">
                        <label class="calendar-field calendar-field-wide">
                            <span>Título público</span>
                            <input name="title" type="text" maxlength="100" required placeholder="Ex.: Reunião de projeto">
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
    </div>
</section>
