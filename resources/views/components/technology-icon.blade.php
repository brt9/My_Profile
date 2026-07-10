@props(['name', 'fallback' => '</>'])

@php
    $brandIcons = [
        'laravel' => 'laravel.svg',
        'php' => 'php.svg',
        'javascript' => 'javascript.svg',
        'vue' => 'vue.svg',
        'alpine' => 'alpine.svg',
        'tailwind' => 'tailwind.svg',
        'bootstrap' => 'bootstrap.svg',
        'vite' => 'vite.svg',
        'postgresql' => 'postgresql.svg',
        'mysql' => 'mysql.svg',
        'powerbi' => 'power-bi.svg',
        'docker' => 'docker.svg',
        'git' => 'git.svg',
        'githubactions' => 'github-actions.svg',
        'nginx' => 'nginx.svg',
        'qgis' => 'qgis.svg',
        'powershell' => 'powershell.svg',
        'pwa' => 'pwa.svg',
    ];
@endphp

@if (isset($brandIcons[$name]))
    <img
        class="technology-logo"
        src="{{ asset('images/technologies/'.$brandIcons[$name]) }}"
        alt=""
        width="18"
        height="18"
        loading="lazy"
        decoding="async"
    >
@else
    <span class="technology-icon-fallback">{{ $fallback }}</span>
@endif
