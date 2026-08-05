@php($profile = $portfolio ?? config('portfolio'))
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-debug" content="{{ config('app.debug') ? 'true' : 'false' }}">
    <title>{{ $title ?? $profile['name'].' — '.$profile['role'] }}</title>
    <meta name="description" content="{{ $metaDescription ?? $profile['headline'] }}">
    <meta name="application-name" content="Pedro Felipe">
    <meta name="theme-color" content="#f7f6f3" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111116" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/icon-32.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/icon-180.png') }}?v=2">
    <script>
        (() => {
            let saved = null;
            try {
                saved = localStorage.getItem('portfolio-theme');
            } catch {
                // Storage can be unavailable in restricted browser contexts.
            }
            const dark = saved ? saved === 'dark' : true;
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';

        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body>
    @if (request()->routeIs('home'))
        <div class="site-intro" data-site-intro aria-hidden="true">
            <canvas class="site-intro-canvas" data-site-intro-canvas></canvas>
            <div class="site-intro-content">
                <span class="site-intro-kicker">PORTFÓLIO DIGITAL</span>
                <strong class="site-intro-title" data-site-intro-title>pedrofelipe.dev</strong>
                <div class="site-intro-progress" aria-hidden="true">
                    <i data-site-intro-progress></i>
                </div>
                <span class="site-intro-status" data-site-intro-status>INICIALIZANDO</span>
                <span class="site-intro-skip">Clique ou pressione Esc para pular</span>
            </div>
        </div>
    @endif
    <canvas class="site-globe-background" data-globe-background aria-hidden="true"></canvas>
    @include('partials.sandbox-warning')
    <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>

    <nav class="site-nav" aria-label="Navegação principal">
        <div class="container-shell nav-inner">
            <a href="/#inicio" class="brand" aria-label="Início">
                <svg class="site-brand-icon" viewBox="0 0 34 34" width="34" height="34" aria-hidden="true" focusable="false">
                    <rect x="4.5" y="5.5" width="25" height="23" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M5.5 11.5h23" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M13 16l-3.5 3 3.5 3M21 16l3.5 3-3.5 3M19 14l-4 10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>pedrofelipe<span>.dev</span></span>
            </a>

            <div class="desktop-nav" data-section-navigation>
                <a href="/#sobre" data-nav-section="sobre">Sobre</a>
                <a href="/#experiencia" data-nav-section="experiencia">Experiência</a>
                <a href="/#projetos" data-nav-section="projetos">Projetos</a>
                <details class="nav-dropdown" data-nav-group="laboratorio">
                    <summary
                        @class(['is-active' => request()->routeIs('calendar.show', 'steam.show', 'weather.show')])
                        data-lab-trigger
                        data-lab-url="/#lab"
                    >
                        Laboratório
                        <span class="nav-dropdown-chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="nav-dropdown-panel">
                        <a href="{{ route('calendar.show') }}" @class(['is-active' => request()->routeIs('calendar.show')]) @if(request()->routeIs('calendar.show')) aria-current="page" @endif>
                            <strong>Agenda integrada</strong>
                            <span>OAuth, filas e Google Calendar API</span>
                        </a>
                        <a href="{{ route('steam.show') }}" @class(['is-active' => request()->routeIs('steam.show')]) @if(request()->routeIs('steam.show')) aria-current="page" @endif>
                            <strong>Steam API</strong>
                            <span>Cache, dados públicos e resiliência</span>
                        </a>
                        <a href="{{ route('weather.show') }}" @class(['is-active' => request()->routeIs('weather.show')]) @if(request()->routeIs('weather.show')) aria-current="page" @endif>
                            <strong>Clima em tempo real</strong>
                            <span>Geolocalização, cache e Open-Meteo</span>
                        </a>
                    </div>
                </details>
            </div>

            <div class="nav-actions">
                <button type="button" class="icon-button theme-toggle" data-theme-toggle aria-label="Alternar tema">
                    <span class="theme-icon theme-icon-moon" aria-hidden="true">☾</span>
                    <span class="theme-icon theme-icon-sun" aria-hidden="true">☀</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="mobile-bottom-nav" data-section-navigation role="navigation" aria-label="Navegação móvel">
            <a href="/#sobre" class="mobile-bottom-item" data-nav-section="sobre">
                <svg class="mobile-bottom-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.5"></circle>
                    <path d="M5.5 19c.7-3.4 3-5.2 6.5-5.2s5.8 1.8 6.5 5.2"></path>
                </svg>
                <span>Sobre</span>
            </a>
            <a href="/#experiencia" class="mobile-bottom-item" data-nav-section="experiencia">
                <svg class="mobile-bottom-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3.5" y="7" width="17" height="12" rx="2"></rect>
                    <path d="M8.5 7V5.5h7V7M3.5 12.5h17M10 12.5v1h4v-1"></path>
                </svg>
                <span>Experiência</span>
            </a>
            <a href="/#projetos" class="mobile-bottom-item" data-nav-section="projetos">
                <svg class="mobile-bottom-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="4" y="4" width="6" height="6" rx="1"></rect>
                    <rect x="14" y="4" width="6" height="6" rx="1"></rect>
                    <rect x="4" y="14" width="6" height="6" rx="1"></rect>
                    <rect x="14" y="14" width="6" height="6" rx="1"></rect>
                </svg>
                <span>Projetos</span>
            </a>
            <details
                class="mobile-bottom-lab"
                data-nav-group="laboratorio"
                data-mobile-lab-menu
            >
                <summary
                    @class(['mobile-bottom-item', 'is-active' => request()->routeIs('calendar.show', 'steam.show', 'weather.show')])
                    data-nav-section="laboratorio"
                    data-lab-trigger
                    data-lab-url="/#lab"
                >
                    <svg class="mobile-bottom-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 3.5h6M10 3.5v5L5.4 17a2.3 2.3 0 0 0 2 3.5h9.2a2.3 2.3 0 0 0 2-3.5L14 8.5v-5"></path>
                        <path d="M7.6 15h8.8"></path>
                    </svg>
                    <span>Laboratório</span>
                </summary>
                <div class="mobile-lab-panel">
                    <strong>Laboratório</strong>
                    <span>Integrações e páginas próprias</span>
                    <a href="/#lab" data-lab-trigger data-lab-url="/#lab">Hardware monitorado</a>
                    <a href="{{ route('calendar.show') }}" @class(['is-active' => request()->routeIs('calendar.show')]) @if(request()->routeIs('calendar.show')) aria-current="page" @endif>Agenda integrada</a>
                    <a href="{{ route('steam.show') }}" @class(['is-active' => request()->routeIs('steam.show')]) @if(request()->routeIs('steam.show')) aria-current="page" @endif>Steam API</a>
                    <a href="{{ route('weather.show') }}" @class(['is-active' => request()->routeIs('weather.show')]) @if(request()->routeIs('weather.show')) aria-current="page" @endif>Clima em tempo real</a>
                </div>
            </details>
    </div>

    <main id="conteudo">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container-shell footer-inner">
            <div class="footer-identity">
                <strong>{{ $profile['name'] }}</strong>
                <span>{{ $profile['role'] }} · {{ $profile['location'] }}</span>
            </div>

            @if ($profile['social']['github'] || $profile['social']['linkedin'] || ($profile['social']['whatsapp'] ?? null) || $profile['email'])
                <nav class="footer-links" aria-label="Contatos profissionais">
                    @if ($profile['social']['github'])
                        <a href="{{ $profile['social']['github'] }}" target="_blank" rel="noopener noreferrer">GitHub ↗</a>
                    @endif
                    @if ($profile['social']['linkedin'])
                        <a href="{{ $profile['social']['linkedin'] }}" target="_blank" rel="noopener noreferrer">LinkedIn ↗</a>
                    @endif
                    @if ($profile['social']['whatsapp'] ?? null)
                        <a href="{{ $profile['social']['whatsapp'] }}" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a>
                    @endif
                    @if ($profile['email'])
                        <a href="mailto:{{ $profile['email'] }}">E-mail</a>
                    @endif
                </nav>
            @endif

            <div class="footer-meta">
                <p>© <span data-current-year>{{ date('Y') }}</span> {{ $profile['name'] }}.</p>
                <button type="button" class="footer-privacy-button" data-cookie-settings-open>Privacidade e cookies</button>
            </div>
        </div>
    </footer>

    @yield('modals')

    <aside
        class="cookie-consent"
        data-cookie-consent
        data-visitor-map-endpoint="{{ route('visitors.map.index') }}"
        hidden
        aria-labelledby="cookie-consent-title"
        aria-describedby="cookie-consent-description"
    >
        <div class="cookie-consent-copy">
            <span class="cookie-consent-kicker">Preferências de privacidade</span>
            <h2 id="cookie-consent-title">Escolha como deseja navegar.</h2>
            <p id="cookie-consent-description">
                Usamos cookies essenciais para o funcionamento do site. Com sua permissão, recursos opcionais também podem usar sua localização aproximada para gerar estatísticas anônimas de acesso. Nenhuma coordenada exata é armazenada.
            </p>
            <p class="cookie-consent-status" data-cookie-consent-status aria-live="polite"></p>
        </div>
        <div class="cookie-consent-actions">
            <button type="button" class="button button-secondary" data-cookie-essential>Aceitar apenas os necessários</button>
            <button type="button" class="button button-primary" data-cookie-location>Aceitar todos os cookies</button>
            <button type="button" class="cookie-consent-close" data-cookie-consent-close hidden aria-label="Fechar preferências">Fechar</button>
        </div>
    </aside>

    @stack('scripts')
</body>

</html>
