<?php

test('calendar case study has a dedicated public page', function () {
    config()->set('portfolio.integrations.calendar', true);

    $this->get(route('calendar.show'))
        ->assertOk()
        ->assertSee('Agenda pública com privacidade por padrão.')
        ->assertSee('Google Calendar API')
        ->assertSee('calendar-shell', false)
        ->assertSee('<summary class="is-active">Laboratório</summary>', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('home').'#laboratorio', false);
});

test('steam api laboratory has a dedicated public page', function () {
    $this->get(route('steam.show'))
        ->assertOk()
        ->assertSee('Uma API externa transformada em experiência.')
        ->assertSee('Steam Web API')
        ->assertSee('steam-card', false)
        ->assertSee('<summary class="is-active">Laboratório</summary>', false)
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
        ->assertSee('<summary class="is-active">Laboratório</summary>', false)
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('home').'#laboratorio', false);
});

test('primary navigation links to the dedicated integration pages', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('nav-dropdown', false)
        ->assertSee('Laboratório')
        ->assertSee('data-nav-section="sobre"', false)
        ->assertSee('data-nav-group="laboratorio"', false)
        ->assertSee('Projetos que demonstram minhas habilidades')
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
