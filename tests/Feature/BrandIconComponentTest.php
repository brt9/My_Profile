<?php

use Illuminate\Support\Facades\Blade;

test('brand icons render as local decorative assets', function () {
    $github = Blade::render('<x-icons.github class="github-test" />');
    $duolingo = Blade::render('<x-icons.duolingo class="duolingo-test" />');
    $duolingoMascot = Blade::render('<x-icons.duolingo variant="mascot" />');

    expect($github)->toContain('<svg', 'github-test', 'viewBox="0 0 24 24"', 'aria-hidden="true"')
        ->and($duolingo)->toContain('<img', 'duolingo-test', 'images/duolingo-face.png', 'aria-hidden="true"')
        ->and($duolingoMascot)->toContain('images/duolingo-mascot.png')
        ->and($github)->not->toContain('<img', ' src=')
        ->and($duolingo)->not->toContain('<svg');
});

test('technology badges use local original brand marks when available', function () {
    $laravel = Blade::render('<x-technology-badge name="Laravel" />');
    $php = Blade::render('<x-technology-badge name="PHP 8" />');
    $javascript = Blade::render('<x-technology-badge name="JavaScript" />');

    expect($laravel)->toContain('images/technologies/laravel.svg', 'technology-logo')
        ->and($php)->toContain('images/technologies/php.svg', 'technology-logo')
        ->and($javascript)->toContain('images/technologies/javascript.svg', 'technology-logo')
        ->and($laravel)->not->toContain('cdn.simpleicons.org')
        ->and($php)->not->toContain('cdn.simpleicons.org')
        ->and($javascript)->not->toContain('cdn.simpleicons.org');

    foreach (['laravel.svg', 'php.svg', 'javascript.svg', 'vue.svg', 'postgresql.svg', 'mysql.svg'] as $icon) {
        expect(public_path('images/technologies/'.$icon))->toBeFile();
    }
});
