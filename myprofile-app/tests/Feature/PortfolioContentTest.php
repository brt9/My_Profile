<?php

test('professional content is structured around evidence and results', function () {
    $portfolio = config('portfolio');

    expect($portfolio['social']['github'])->toBe('https://github.com/brt9')
        ->and($portfolio['social']['linkedin'])->toBe('https://www.linkedin.com/in/pedrofelipebrt9')
        ->and($portfolio['competencies'])->toHaveCount(6)
        ->and($portfolio['projects'])->toHaveCount(3)
        ->and($portfolio['automations'])->toHaveCount(3);

    foreach ($portfolio['competencies'] as $competency) {
        expect($competency)
            ->toHaveKeys(['title', 'items', 'evidence', 'href'])
            ->and($competency['items'])->not->toBeEmpty()
            ->and($competency['href'])->toStartWith('#');
    }

    foreach ($portfolio['projects'] as $project) {
        expect($project)
            ->toHaveKeys(['title', 'context', 'action', 'result', 'stack', 'status'])
            ->and($project['context'])->not->toBeEmpty()
            ->and($project['action'])->not->toBeEmpty()
            ->and($project['result'])->not->toBeEmpty();
    }

    foreach ($portfolio['automations'] as $automation) {
        expect($automation)
            ->toHaveKeys(['title', 'before', 'solution', 'result', 'stack', 'responsibility'])
            ->and($automation['responsibility'])->not->toBeEmpty();
    }
});

test('home presents professional narrative and real social links', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Competências com evidências', false)
        ->assertSee('Contexto')
        ->assertSee('Responsabilidade')
        ->assertSee('Menos operação manual')
        ->assertSee('https://github.com/brt9', false)
        ->assertSee('https://www.linkedin.com/in/pedrofelipebrt9', false);
});
