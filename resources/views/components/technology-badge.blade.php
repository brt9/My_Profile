@props(['name'])

@php
    $normalized = str($name)->lower()->ascii()->replace([' ', '.', '/', '&', '#'], '')->toString();
    $icons = [
        'laravel' => ['key' => 'laravel', 'glyph' => 'L'],
        'laravelapi' => ['key' => 'laravel', 'glyph' => 'L'],
        'php' => ['key' => 'php', 'glyph' => 'php'],
        'php8' => ['key' => 'php', 'glyph' => 'php'],
        'javascript' => ['key' => 'javascript', 'glyph' => 'JS'],
        'blade' => ['key' => 'laravel', 'glyph' => '◆'],
        'vuejs' => ['key' => 'vue', 'glyph' => 'V'],
        'alpinejs' => ['key' => 'alpine', 'glyph' => '▲'],
        'tailwindcss' => ['key' => 'tailwind', 'glyph' => '≈'],
        'bootstrap' => ['key' => 'bootstrap', 'glyph' => 'B'],
        'vite' => ['key' => 'vite', 'glyph' => 'ϟ'],
        'apis' => ['key' => 'api', 'glyph' => '{}'],
        'apisrest' => ['key' => 'api', 'glyph' => '{}'],
        'integracoes' => ['key' => 'integration', 'glyph' => '↔'],
        'testes' => ['key' => 'test', 'glyph' => '✓'],
        'pest' => ['key' => 'test', 'glyph' => '✓'],
        'postgresql' => ['key' => 'postgresql', 'glyph' => 'PG'],
        'mysql' => ['key' => 'mysql', 'glyph' => 'MY'],
        'sql' => ['key' => 'database', 'glyph' => 'DB'],
        'powerbi' => ['key' => 'powerbi', 'glyph' => '▥'],
        'dashboards' => ['key' => 'dashboard', 'glyph' => '▦'],
        'homologacao' => ['key' => 'check', 'glyph' => '✓'],
        'suporte' => ['key' => 'support', 'glyph' => '◎'],
        'documentacao' => ['key' => 'docs', 'glyph' => '▤'],
        'treinamento' => ['key' => 'training', 'glyph' => '◇'],
        'nginx' => ['key' => 'nginx', 'glyph' => 'N'],
        'docker' => ['key' => 'docker', 'glyph' => '▣'],
        'git' => ['key' => 'git', 'glyph' => '⑂'],
        'githubactions' => ['key' => 'githubactions', 'glyph' => 'GH'],
        'cicd' => ['key' => 'pipeline', 'glyph' => '∞'],
        'qgis' => ['key' => 'qgis', 'glyph' => 'Q'],
        'geojson' => ['key' => 'geo', 'glyph' => '⌖'],
        'powershell' => ['key' => 'powershell', 'glyph' => '>_'],
        'ia' => ['key' => 'ai', 'glyph' => '✦'],
        'pwa' => ['key' => 'pwa', 'glyph' => 'PWA'],
        'qrcode' => ['key' => 'qr', 'glyph' => '▦'],
    ];
    $icon = $icons[$normalized] ?? ['key' => 'code', 'glyph' => '</>'];
@endphp

<span {{ $attributes->class(['chip', 'technology-badge']) }} data-technology="{{ $icon['key'] }}">
    <span class="technology-icon" aria-hidden="true">
        <x-technology-icon :name="$icon['key']" :fallback="$icon['glyph']" />
    </span>
    <span>{{ $name }}</span>
</span>
