<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\BasicAuthGate;
use Illuminate\Http\Request;

it('authorises correct credentials', function () {
    config(['health-route.access.basic_auth' => ['username' => 'monitor', 'password' => 'secret']]);

    $request = Request::create('/up');
    $request->headers->set('PHP_AUTH_USER', 'monitor');
    $request->headers->set('PHP_AUTH_PW', 'secret');

    expect((new BasicAuthGate)->authorizes($request))->toBeTrue();
});

it('denies wrong credentials', function () {
    config(['health-route.access.basic_auth' => ['username' => 'monitor', 'password' => 'secret']]);

    $request = Request::create('/up');
    $request->headers->set('PHP_AUTH_USER', 'monitor');
    $request->headers->set('PHP_AUTH_PW', 'wrong');

    expect((new BasicAuthGate)->authorizes($request))->toBeFalse();
});

it('never authorises when nothing is configured, even with no credentials sent', function () {
    config(['health-route.access.basic_auth' => ['username' => null, 'password' => null]]);

    $request = Request::create('/up');

    expect((new BasicAuthGate)->authorizes($request))->toBeFalse();
});

it('never authorises when only the username is configured', function () {
    config(['health-route.access.basic_auth' => ['username' => 'monitor', 'password' => null]]);

    $request = Request::create('/up');
    $request->headers->set('PHP_AUTH_USER', 'monitor');

    expect((new BasicAuthGate)->authorizes($request))->toBeFalse();
});
