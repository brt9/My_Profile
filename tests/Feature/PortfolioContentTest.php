<?php

test('professional content is structured around evidence and results', function () {
    $portfolio = config('portfolio');

    expect($portfolio['social']['github'])->toBe('https://github.com/brt9')
        ->and($portfolio['social']['linkedin'])->toBe('https://www.linkedin.com/in/pedrofelipebrt9')
        ->and($portfolio['competencies'])->toHaveCount(6)
        ->and($portfolio['current_roles'])->toHaveCount(2)
        ->and($portfolio['current_roles'][1]['role'])->toBe('Desenvolvedor Full Stack Freelancer')
        ->and($portfolio['projects'])->toHaveCount(3)
        ->and($portfolio['experience'])->toHaveCount(3)
        ->and($portfolio['language_note'])->toBe('Vivência internacional por 1 ano e 11 meses.')
        ->and($portfolio)->not->toHaveKey('automations');

    foreach ($portfolio['competencies'] as $competency) {
        expect($competency)
            ->toHaveKeys(['title', 'description', 'items'])
            ->not->toHaveKeys(['evidence', 'href'])
            ->and($competency['description'])->not->toBeEmpty()
            ->and($competency['items'])->not->toBeEmpty();
    }

    expect($portfolio['competencies'][0]['items'])->toHaveCount(7)
        ->and($portfolio['competencies'][1]['items'])->toHaveCount(7);

    foreach ($portfolio['projects'] as $project) {
        expect($project)
            ->toHaveKeys(['title', 'context', 'action', 'result', 'stack', 'status'])
            ->and($project['context'])->not->toBeEmpty()
            ->and($project['action'])->not->toBeEmpty()
            ->and($project['result'])->not->toBeEmpty();
    }

    expect($portfolio['projects'][0]['url'])->toBe('https://www.bardoti.xyz');
});

test('home presents the professional narrative without removed sections', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Competências e tecnologias', false)
        ->assertSeeInOrder([
            'Atuação atual',
            'Analista de Implantação',
            'Desenvolvedor Full Stack Freelancer',
            'Backend',
        ])
        ->assertSee('O que faz')
        ->assertSee('technology-badge', false)
        ->assertSee('technology-logo', false)
        ->assertSee('https://www.bardoti.xyz', false)
        ->assertSee('Abrir o site BardoTI', false)
        ->assertSee('Vivência internacional por 1 ano e 11 meses.')
        ->assertDontSee('Teixeira Construções / ETS')
        ->assertDontSee('Sistemas de gestão, telemetria e integrações resilientes')
        ->assertDontSee('evidence-link', false)
        ->assertDontSee('id="automacoes"', false)
        ->assertDontSee('id="contato"', false)
        ->assertSee('https://github.com/brt9', false)
        ->assertSee('https://www.linkedin.com/in/pedrofelipebrt9', false);

    $this->get('/')
        ->assertSee('Integrações em páginas próprias.')
        ->assertSee(route('calendar.show'), false)
        ->assertSee(route('steam.show'), false)
        ->assertSee(route('weather.show'), false)
        ->assertDontSee('calendar-shell', false)
        ->assertDontSee('steam-card', false)
        ->assertDontSee('weather-card', false);
});

test('home sections follow the defined visual order', function () {
    $template = file_get_contents(resource_path('views/home.blade.php'));
    $needles = [
        "@include('sections.about')",
        "@include('sections.projects')",
        "@include('sections.experience')",
        'id="github"',
        "@include('sections.pc')",
        "@include('sections.duolingo')",
        'id="laboratorio"',
    ];

    $positions = array_map(fn (string $needle): int|false => strpos($template, $needle), $needles);

    expect($positions)->not->toContain(false)
        ->and($positions)->toBe(collect($positions)->sort()->values()->all());
});
