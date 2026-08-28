<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\SharedSecretHeaderGate;
use Illuminate\Http\Request;

it('authorises the correct header value', function () {
    config(['health-route.access.token' => ['header' => 'X-Health-Token', 'value' => 'super-secret']]);

    $request = Request::create('/up');
    $request->headers->set('X-Health-Token', 'super-secret');

    expect((new SharedSecretHeaderGate)->authorizes($request))->toBeTrue();
});

it('denies the wrong header value', function () {
    config(['health-route.access.token' => ['header' => 'X-Health-Token', 'value' => 'super-secret']]);

    $request = Request::create('/up');
    $request->headers->set('X-Health-Token', 'wrong');

    expect((new SharedSecretHeaderGate)->authorizes($request))->toBeFalse();
});

it('never authorises when nothing is configured, even with an empty header sent', function () {
    config(['health-route.access.token' => ['header' => 'X-Health-Token', 'value' => null]]);

    $request = Request::create('/up');
    $request->headers->set('X-Health-Token', '');

    expect((new SharedSecretHeaderGate)->authorizes($request))->toBeFalse();
});
