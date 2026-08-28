<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\LocalEnvironmentBypassGate;
use Illuminate\Http\Request;

it('authorises in the local environment when enabled', function () {
    config(['health-route.access.bypass_when_local' => true]);
    app()->detectEnvironment(fn () => 'local');

    expect((new LocalEnvironmentBypassGate)->authorizes(Request::create('/up')))->toBeTrue();
});

it('never authorises when disabled, even in the local environment', function () {
    config(['health-route.access.bypass_when_local' => false]);
    app()->detectEnvironment(fn () => 'local');

    expect((new LocalEnvironmentBypassGate)->authorizes(Request::create('/up')))->toBeFalse();
});

it('does not authorise outside the local environment even when enabled', function () {
    config(['health-route.access.bypass_when_local' => true]);
    app()->detectEnvironment(fn () => 'production');

    expect((new LocalEnvironmentBypassGate)->authorizes(Request::create('/up')))->toBeFalse();
});

it('ignores a spoofed client IP - only the application environment is checked', function () {
    config(['health-route.access.bypass_when_local' => true]);
    app()->detectEnvironment(fn () => 'production');

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '127.0.0.1']);

    expect((new LocalEnvironmentBypassGate)->authorizes($request))->toBeFalse();
});
