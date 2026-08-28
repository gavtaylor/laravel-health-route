<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\StaticIpAllowlistGate;
use Illuminate\Http\Request;

it('authorises an allowed IP', function () {
    config(['health-route.access.allowed_ips' => ['203.0.113.5']]);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.5']);

    expect((new StaticIpAllowlistGate)->authorizes($request))->toBeTrue();
});

it('denies an IP outside the allowlist', function () {
    config(['health-route.access.allowed_ips' => ['203.0.113.5']]);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '198.51.100.1']);

    expect((new StaticIpAllowlistGate)->authorizes($request))->toBeFalse();
});

it('never authorises when the allowlist is empty', function () {
    config(['health-route.access.allowed_ips' => []]);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.5']);

    expect((new StaticIpAllowlistGate)->authorizes($request))->toBeFalse();
});
