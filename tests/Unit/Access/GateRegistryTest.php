<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\GateRegistry;
use Illuminate\Http\Request;

it('authorises everyone when nothing is configured', function () {
    $registry = app(GateRegistry::class);

    expect($registry->authorizes(Request::create('/up')))->toBeTrue();
});

it('denies everyone once a method is configured and none match', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'secret',
    ]);

    $registry = app(GateRegistry::class);

    expect($registry->authorizes(Request::create('/up')))->toBeFalse();
});

it('authorises when at least one configured method matches', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'secret',
    ]);

    $request = Request::create('/up');
    $request->headers->set('X-Health-Token', 'secret');

    $registry = app(GateRegistry::class);

    expect($registry->authorizes($request))->toBeTrue();
});
