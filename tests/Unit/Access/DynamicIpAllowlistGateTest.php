<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\Contracts\DnsResolver;
use GavTaylor\HealthRoute\Access\DynamicIpAllowlistGate;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;

class FakeDnsResolver implements DnsResolver
{
    public int $lookups = 0;

    /**
     * @var array{0: list<string>, 1: int}
     */
    public array $result = [['203.0.113.9'], 300];

    public function resolve(string $hostname): array
    {
        $this->lookups++;

        return $this->result;
    }
}

it('authorises an IP resolved from an allowed hostname', function () {
    config(['health-route.access.allowed_hostnames' => ['home.example.test']]);

    $resolver = new FakeDnsResolver;
    $gate = new DynamicIpAllowlistGate(app(CacheRepository::class), $resolver);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.9']);

    expect($gate->authorizes($request))->toBeTrue();
});

it('denies an IP that does not match the resolved address', function () {
    config(['health-route.access.allowed_hostnames' => ['home.example.test']]);

    $gate = new DynamicIpAllowlistGate(app(CacheRepository::class), new FakeDnsResolver);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '198.51.100.1']);

    expect($gate->authorizes($request))->toBeFalse();
});

it('never authorises when no hostnames are configured', function () {
    config(['health-route.access.allowed_hostnames' => []]);

    $resolver = new FakeDnsResolver;
    $gate = new DynamicIpAllowlistGate(app(CacheRepository::class), $resolver);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.9']);

    expect($gate->authorizes($request))->toBeFalse();
    expect($resolver->lookups)->toBe(0);
});

it('caches the resolved IP for the DNS record TTL, only resolving once per window', function () {
    config(['health-route.access.allowed_hostnames' => ['home.example.test']]);

    $resolver = new FakeDnsResolver;
    $gate = new DynamicIpAllowlistGate(app(CacheRepository::class), $resolver);
    $request = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.9']);

    $gate->authorizes($request);
    $gate->authorizes($request);
    $gate->authorizes($request);

    expect($resolver->lookups)->toBe(1);
});

it('caches a failed lookup briefly rather than retrying on every request', function () {
    config([
        'health-route.access.allowed_hostnames' => ['home.example.test'],
        'health-route.access.dynamic_ip.negative_cache_seconds' => 30,
    ]);

    $resolver = new FakeDnsResolver;
    $resolver->result = [[], 0];
    $gate = new DynamicIpAllowlistGate(app(CacheRepository::class), $resolver);

    $request = Request::create('/up', server: ['REMOTE_ADDR' => '203.0.113.9']);

    $gate->authorizes($request);
    $gate->authorizes($request);

    expect($resolver->lookups)->toBe(1);
});
