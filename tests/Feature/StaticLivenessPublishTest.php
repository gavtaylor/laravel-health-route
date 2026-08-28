<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\HealthRouteServiceProvider;

it('publishes the static liveness stub to the public directory', function () {
    $target = public_path('ping');

    if (file_exists($target)) {
        unlink($target);
    }

    $this->artisan('vendor:publish', [
        '--provider' => HealthRouteServiceProvider::class,
        '--tag' => 'health-route-static',
    ])->assertExitCode(0);

    expect($target)->toBeFile();
    expect(file_get_contents($target))->toBe("pong\n");

    unlink($target);
});

it('never overwrites a static liveness file the app has already customised', function () {
    $target = public_path('ping');

    file_put_contents($target, 'a custom liveness response');

    $this->artisan('vendor:publish', [
        '--provider' => HealthRouteServiceProvider::class,
        '--tag' => 'health-route-static',
    ]);

    expect(file_get_contents($target))->toBe('a custom liveness response');

    unlink($target);
});

it('is never a registered framework route', function () {
    $this->get('/ping')->assertNotFound();
});
