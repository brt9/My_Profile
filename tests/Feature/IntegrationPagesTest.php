<?php

test('calendar case study has a dedicated public page', function () {
    config()->set('portfolio.integrations.calendar', true);

    $this->get(route('calendar.show'))
        ->assertOk()
        ->assertSee('Agenda pública com privacidade por padrão.')
        ->assertSee('Google Calendar API')
        ->assertSee('calendar-shell', false)
        ->assertSee('class="is-active"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('home').'#laboratorio', false);
});

test('steam api laboratory has a dedicated public page', function () {
    $this->get(route('steam.show'))
        ->assertOk()
        ->assertSee('Uma API externa transformada em experiência.')
        ->assertSee('Steam Web API')
        ->assertSee('steam-card', false)
        ->assertSee('class="is-active"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('home').'#laboratorio', false);
});

test('weather laboratory has a dedicated public page', function () {
    config()->set('portfolio.integrations.weather', true);

    $this->get(route('weather.show'))
        ->assertOk()
        ->assertSee('Clima em tempo real com privacidade.')
        ->assertSee('Open-Meteo')
        ->assertSee('weather-card', false)
        ->assertSee('class="is-active"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('home').'#laboratorio', false);
});

test('primary navigation links to the dedicated integration pages', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('nav-dropdown', false)
        ->assertSee('mobile-bottom-nav', false)
        ->assertSee('data-mobile-lab-menu', false)
        ->assertDontSee('data-menu-toggle', false)
        ->assertSee('Laboratório')
        ->assertSee('data-nav-section="sobre"', false)
        ->assertSee('data-nav-group="laboratorio"', false)
        ->assertSee('Trabalho em funcionamento.')
        ->assertSee(route('calendar.show'), false)
        ->assertSee(route('steam.show'), false)
        ->assertSee(route('weather.show'), false)
        ->assertSee('data-nav-owner="experiencia"', false)
        ->assertSee('id="lab" class="section section-alt" data-nav-owner="laboratorio"', false)
        ->assertSee('id="estudos" class="section section-alt duolingo-section" data-nav-owner="laboratorio"', false)
        ->assertDontSee('id="clima"', false)
        ->assertDontSee('<summary>Laboratório <span', false)
        ->assertDontSee('>Agenda</a>', false)
        ->assertDontSee('/#agenda', false);
});

test('mobile navigation keeps fixed geometry while sections change', function () {
    $styles = file_get_contents(resource_path('css/app.css'));
    $scripts = file_get_contents(resource_path('js/app.js'));

    expect($styles)
        ->toContain('grid-template-columns: repeat(4, minmax(0, 1fr));')
        ->and($styles)->toContain('height: 70px;')
        ->and($styles)->toContain('height: 54px;')
        ->and($styles)->toContain('overflow-x: clip;')
        ->and($styles)->toContain('width: min(calc(100% - 20px), 520px);')
        ->and($styles)->toContain('width: min(310px, calc(400% - 28px));')
        ->and($styles)->toContain('contain: size layout;')
        ->and($styles)->toContain('transform: translate3d(-50%, 0, 0);')
        ->and($styles)->toContain('top: calc(100dvh - 80px - env(safe-area-inset-bottom, 0px));')
        ->and($styles)->not->toContain('--mobile-viewport-bottom-offset')
        ->and($scripts)->not->toContain('visualViewport')
        ->and($scripts)->toContain('lockedNavigationSection')
        ->and($scripts)->toContain('laboratorySection.scrollIntoView')
        ->and($scripts)->toContain("window.history.replaceState(null, '', '#lab')")
        ->and($scripts)->toContain("window.addEventListener('scrollend', unlockNavigationSection")
        ->and($scripts)->toContain("mobileLabMenu?.removeAttribute('open')");
});
