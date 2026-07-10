<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('home remains useful when all external integrations fail', function () {
    Cache::flush();
    config()->set('services.steam.key', 'test-key');
    config()->set('services.steam.id', 'test-id');
    config()->set('portfolio.integrations.weather', true);
    config()->set('portfolio.integrations.allow_in_tests', true);
    Http::fake(fn () => throw new ConnectionException('provider unavailable'));

    $this->get('/')
        ->assertOk()
        ->assertSee('Pedro Felipe')
        ->assertSee('Trabalho em funcionamento')
        ->assertSee('Saúde da estação em tempo real')
        ->assertSee('Natal em tempo real')
        ->assertDontSee('id="contato"', false);
});
