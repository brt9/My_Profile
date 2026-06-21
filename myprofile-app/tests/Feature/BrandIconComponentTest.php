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
