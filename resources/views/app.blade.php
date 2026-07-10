<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-debug" content="{{ config('app.debug') ? 'true' : 'false' }}">
        <title inertia>{{ config('app.name', 'Pedro Felipe') }}</title>
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
        @routes
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @include('partials.sandbox-warning')
        @inertia
    </body>
</html>
