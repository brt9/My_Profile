<?php

test('public and authenticated shells expose pwa metadata', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('icons/icon-32.png', false)
        ->assertSee('icons/icon-180.png', false)
        ->assertSee('data-sandbox-warning', false)
        ->assertSee('myprofile:escape-sandbox', false);

    $this->get('/login')
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('manifest.webmanifest', false)
        ->assertSee('icons/icon-32.png', false)
        ->assertSee('icons/icon-180.png', false)
        ->assertSee('data-sandbox-warning', false)
        ->assertSee('myprofile:escape-sandbox', false);
});

test('pwa manifest icons and offline worker are complete', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->toHaveKeys(['name', 'short_name', 'start_url', 'scope', 'display', 'icons'])
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['icons'])->toHaveCount(3);

    foreach ([32, 180, 192, 512] as $size) {
        $path = public_path("icons/icon-{$size}.png");
        $dimensions = getimagesize($path);

        expect($path)->toBeFile()
            ->and($dimensions[0])->toBe($size)
            ->and($dimensions[1])->toBe($size);
    }

    expect(public_path('icons/icon-maskable-512.png'))->toBeFile()
        ->and(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);

    $worker = file_get_contents(public_path('sw.js'));
    expect($worker)
        ->toContain('/offline.html', "'/api/'", "'/admin/'", "'/auth/'", "'/dashboard'");
});
