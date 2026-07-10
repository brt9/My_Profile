<?php

use Illuminate\Support\Facades\Blade;

test('game image reserves dimensions and provides a local fallback', function () {
    $html = Blade::render('<x-game-image :game="$game" />', [
        'game' => [
            'appid' => 10,
            'name' => 'Jogo de teste',
            'image' => 'https://example.com/missing.jpg',
            'capsule' => 'https://example.com/capsule.jpg',
        ],
    ]);

    expect($html)
        ->toContain('width="616"')
        ->toContain('height="353"')
        ->toContain('loading="lazy"')
        ->toContain('data-fallback-src="https://example.com/capsule.jpg"')
        ->toContain('game-cover-fallback')
        ->toContain('Jogo de teste');
});
