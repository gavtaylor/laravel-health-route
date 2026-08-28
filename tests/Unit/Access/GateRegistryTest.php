<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\BasicAuthGate;
use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use GavTaylor\HealthRoute\Access\GateRegistry;
use GavTaylor\HealthRoute\Access\StaticIpAllowlistGate;
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

    expect(app(GateRegistry::class)->authorizes($request))->toBeTrue();
});

it('uses a custom access gate when configured', function () {
    config([
        'health-route.access.gates' => [AlwaysAllowGate::class],
    ]);

    expect(app(GateRegistry::class)->authorizes(Request::create('/up')))->toBeTrue();
});

it('treats a custom gate in the active list as configured, denying by default', function () {
    config([
        'health-route.access.gates' => [AlwaysDenyGate::class],
    ]);

    expect(app(GateRegistry::class)->authorizes(Request::create('/up')))->toBeFalse();
});

it('stays public when narrowed to a bundled gate that has no config set', function () {
    // Narrowing the active list to a subset of the bundled gates, with
    // nothing in access.* actually configured, must behave exactly like
    // the full default list would: public.
    config([
        'health-route.access.gates' => [BasicAuthGate::class],
    ]);

    expect(app(GateRegistry::class)->authorizes(Request::create('/up')))->toBeTrue();
});

it('ignores config for a gate that has been excluded from the active list', function () {
    // Regression test: basic_auth credentials are configured, but
    // BasicAuthGate has been narrowed out of the active gate list in
    // favour of a different gate. A caller presenting perfectly valid
    // basic-auth credentials must not be locked out by a gate that was
    // never meant to run - the excluded gate's config must not count
    // towards "something is configured" at all.
    config([
        'health-route.access.basic_auth' => ['username' => 'monitor', 'password' => 'secret'],
        'health-route.access.gates' => [StaticIpAllowlistGate::class],
    ]);

    $request = Request::create('/up');
    $request->headers->set('PHP_AUTH_USER', 'monitor');
    $request->headers->set('PHP_AUTH_PW', 'secret');

    // StaticIpAllowlistGate is the only active gate and has no allowlist
    // configured, so nothing is actually active: public.
    expect(app(GateRegistry::class)->authorizes($request))->toBeTrue();
    expect(app(GateRegistry::class)->authorizes(Request::create('/up')))->toBeTrue();
});

it('still gates on an active method even when an excluded gate also has config set', function () {
    // Same as above, but this time the active gate (allowed_ips) is also
    // genuinely configured - it alone should decide the outcome.
    config([
        'health-route.access.basic_auth' => ['username' => 'monitor', 'password' => 'secret'],
        'health-route.access.allowed_ips' => ['203.0.113.5'],
        'health-route.access.gates' => [StaticIpAllowlistGate::class],
    ]);

    $matchingIp = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.5']);
    $otherIp = Request::create('/up', server: ['REMOTE_ADDR' => '198.51.100.1']);

    expect(app(GateRegistry::class)->authorizes($matchingIp))->toBeTrue();
    expect(app(GateRegistry::class)->authorizes($otherIp))->toBeFalse();
});

class AlwaysAllowGate implements AccessGate
{
    public function authorizes(Request $request): bool
    {
        return true;
    }
}

class AlwaysDenyGate implements AccessGate
{
    public function authorizes(Request $request): bool
    {
        return false;
    }
}
