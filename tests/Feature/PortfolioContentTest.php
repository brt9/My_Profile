<?php

test('professional content is structured around evidence and results', function () {
    $portfolio = config('portfolio');

    expect($portfolio['social']['github'])->toBe('https://github.com/brt9')
        ->and($portfolio['social']['linkedin'])->toBe('https://www.linkedin.com/in/pedrofelipebrt9')
        ->and($portfolio['social']['whatsapp'])->toBe('https://wa.me/558498102246')
        ->and($portfolio['competencies'])->toHaveCount(6)
        ->and($portfolio['current_roles'])->toHaveCount(2)
        ->and($portfolio['current_roles'][1]['role'])->toBe('Desenvolvedor Full Stack Freelancer')
        ->and($portfolio['hardware'])->toHaveCount(14)
        ->and($portfolio['projects'])->toHaveCount(2)
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
        ->and($portfolio['competencies'][1]['items'])->toHaveCount(7)
        ->and($portfolio['hardware'][3]['value'])->toBe('Corsair Dominator 4×16GB · 64GB DDR5-6200');

    foreach ($portfolio['hardware'] as $part) {
        expect($part)
            ->toHaveKeys(['label', 'value', 'image'])
            ->and(public_path($part['image']))->toBeFile();
    }

    foreach ($portfolio['projects'] as $project) {
        expect($project)
            ->toHaveKeys(['title', 'context', 'action', 'result', 'stack', 'status'])
            ->and($project['context'])->not->toBeEmpty()
            ->and($project['action'])->not->toBeEmpty()
            ->and($project['result'])->not->toBeEmpty();
    }

    expect($portfolio['projects'][0]['url'])->toBe('https://www.bardoti.xyz')
        ->and($portfolio['projects'][1]['number'])->toBe('02')
        ->and($portfolio['projects'][1]['url'])->toBe('https://etsconstrucoes.com/')
        ->and($portfolio['education'][0]['diploma_url'])->toContain('media.licdn.com');
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
        ->assertSee('Foto de Intel Core i5-14600K', false)
        ->assertSee('Foto de Logitech G915 X Lightspeed', false)
        ->assertSee('Foto de Logitech G Pro X Superlight 2', false)
        ->assertSee('Monitores · 2 unidades', false)
        ->assertSee('Foto de Dell P2419H 24″', false)
        ->assertSee('Foto de Logitech Brio 500', false)
        ->assertSee('Foto de HyperX QuadCast S', false)
        ->assertDontSee('Ver produto', false)
        ->assertSee('https://www.bardoti.xyz', false)
        ->assertSee('Abrir o site BardoTI', false)
        ->assertSee('https://etsconstrucoes.com/', false)
        ->assertSee('Abrir o site Plataforma de RH e ponto', false)
        ->assertDontSee('Portfólio resiliente')
        ->assertSee('Ver diploma')
        ->assertSee('media.licdn.com', false)
        ->assertSee('Vivência internacional por 1 ano e 11 meses.')
        ->assertDontSee('Teixeira Construções / ETS')
        ->assertDontSee('Sistemas de gestão, telemetria e integrações resilientes')
        ->assertDontSee('evidence-link', false)
        ->assertDontSee('id="automacoes"', false)
        ->assertDontSee('id="contato"', false)
        ->assertSee('data-site-intro', false)
        ->assertSee('pedrofelipe.dev', false)
        ->assertSee('/#lab', false)
        ->assertSee('https://github.com/brt9', false)
        ->assertSee('https://www.linkedin.com/in/pedrofelipebrt9', false)
        ->assertSee('https://wa.me/558498102246', false);

    $this->get('/')
        ->assertDontSee('Estudos de caso')
        ->assertDontSee('Integrações em páginas próprias.')
        ->assertDontSee('case-study-grid', false)
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
        "@include('sections.experience')",
        'id="github"',
        "@include('sections.projects')",
        "@include('sections.pc')",
        "@include('sections.duolingo')",
    ];

    $positions = array_map(fn (string $needle): int|false => strpos($template, $needle), $needles);

    expect($positions)->not->toContain(false)
        ->and($positions)->toBe(collect($positions)->sort()->values()->all());
});

test('intro waits for the mobile background first paint on every page opening', function () {
    $intro = file_get_contents(resource_path('js/site-intro.js'));
    $background = file_get_contents(resource_path('js/globe-background.js'));

    expect($intro)
        ->not->toContain('sessionStorage')
        ->and($intro)->toContain('}, 8000);')
        ->and($background)->toContain('timeout: 90')
        ->and($background)->toContain('processedRows < 8')
        ->and($background)->toContain('window.requestAnimationFrame(resolveReady)');
});
