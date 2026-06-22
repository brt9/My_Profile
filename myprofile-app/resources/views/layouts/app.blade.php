@php($profile = $portfolio ?? config('portfolio'))
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $profile['name'].' — '.$profile['role'] }}</title>
    <meta name="description" content="{{ $metaDescription ?? $profile['headline'] }}">
    <meta name="theme-color" content="#6d4aff">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <script>
        (() => {
            const saved = localStorage.getItem('portfolio-theme');
            const dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';
        })();
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body>
    <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>

    <nav class="site-nav" aria-label="Navegação principal">
        <div class="container-shell nav-inner">
            <a href="/#inicio" class="brand" aria-label="Início">
                <span class="brand-mark" aria-hidden="true">PF</span>
                <span>pedrofelipe<span>.dev</span></span>
            </a>

            <div class="desktop-nav">
                <a href="/#sobre">Sobre</a>
                <a href="/#projetos">Projetos</a>
                <a href="/#experiencia">Experiência</a>
                <a href="/#agenda">Agenda</a>
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

        <div id="mobile-menu" class="mobile-menu container-shell" data-mobile-menu hidden>
            <a href="/#sobre">Sobre</a>
            <a href="/#projetos">Projetos</a>
            <a href="/#experiencia">Experiência</a>
            <a href="/#agenda">Agenda</a>
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
