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
    @include('partials.sandbox-warning')
    <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>

    <nav class="site-nav" aria-label="Navegação principal">
        <div class="container-shell nav-inner">
            <a href="/#inicio" class="brand" aria-label="Início">
                <span class="brand-mark" aria-hidden="true">PF</span>
                <span>pedrofelipe<span>.dev</span></span>
            </a>

            <div class="desktop-nav" data-section-navigation>
                <a href="/#sobre" data-nav-section="sobre">Sobre</a>
                <a href="/#projetos" data-nav-section="projetos">Projetos</a>
                <a href="/#experiencia" data-nav-section="experiencia">Experiência</a>
                <details class="nav-dropdown" data-nav-group="laboratorio">
                    <summary @class(['is-active' => request()->routeIs('calendar.show', 'steam.show', 'weather.show')])>Laboratório</summary>
                    <div class="nav-dropdown-panel">
                        <a href="/#laboratorio" data-nav-section="laboratorio">
                            <strong>Visão geral</strong>
                            <span>Projetos que demonstram minhas habilidades</span>
                        </a>
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
                <button type="button" class="icon-button menu-toggle" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu">
                    <span class="sr-only">Abrir menu</span>
                    <span aria-hidden="true">☰</span>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="mobile-menu container-shell" data-mobile-menu data-section-navigation hidden>
            <a href="/#sobre" data-nav-section="sobre">Sobre</a>
            <a href="/#projetos" data-nav-section="projetos">Projetos</a>
            <a href="/#experiencia" data-nav-section="experiencia">Experiência</a>
            <div class="mobile-nav-group" data-nav-group="laboratorio">
                <span>Laboratório</span>
                <a href="/#laboratorio" data-nav-section="laboratorio">Visão geral</a>
                <a href="{{ route('calendar.show') }}" @class(['is-active' => request()->routeIs('calendar.show')]) @if(request()->routeIs('calendar.show')) aria-current="page" @endif>Agenda integrada</a>
                <a href="{{ route('steam.show') }}" @class(['is-active' => request()->routeIs('steam.show')]) @if(request()->routeIs('steam.show')) aria-current="page" @endif>Steam API</a>
                <a href="{{ route('weather.show') }}" @class(['is-active' => request()->routeIs('weather.show')]) @if(request()->routeIs('weather.show')) aria-current="page" @endif>Clima em tempo real</a>
            </div>
        </div>
    </nav>

    <main id="conteudo">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container-shell footer-inner">
            <div class="footer-identity">
                <strong>{{ $profile['name'] }}</strong>
                <span>{{ $profile['role'] }} · {{ $profile['location'] }}</span>
            </div>

            @if ($profile['social']['github'] || $profile['social']['linkedin'] || $profile['email'])
                <nav class="footer-links" aria-label="Contatos profissionais">
                    @if ($profile['social']['github'])
                        <a href="{{ $profile['social']['github'] }}" target="_blank" rel="noopener noreferrer">GitHub ↗</a>
                    @endif
                    @if ($profile['social']['linkedin'])
                        <a href="{{ $profile['social']['linkedin'] }}" target="_blank" rel="noopener noreferrer">LinkedIn ↗</a>
                    @endif
                    @if ($profile['email'])
                        <a href="mailto:{{ $profile['email'] }}">E-mail</a>
                    @endif
                </nav>
            @endif

            <div class="footer-meta">
                <p>© <span data-current-year>{{ date('Y') }}</span> {{ $profile['name'] }}. Feito com Laravel.</p>
            </div>
        </div>
    </footer>

    @yield('modals')
    @stack('scripts')
</body>

</html>
