<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

final class GateRegistry
{
    /**
     * @var list<class-string<AccessGate>>
     */
    private const array GATES = [
        BasicAuthGate::class,
        SharedSecretHeaderGate::class,
        StaticIpAllowlistGate::class,
        DynamicIpAllowlistGate::class,
        LocalEnvironmentBypassGate::class,
    ];

    public function __construct(
        private readonly Container $container,
    ) {
        //
    }

    /**
     * Whether the request is allowed through the access gate.
     *
     * The endpoint is public by default: if none of the access methods
     * below are configured, every request is authorised. Once at least
     * one is configured, passing any single configured method is enough.
     */
    public function authorizes(Request $request): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        foreach (self::GATES as $gateClass) {
            if ($this->container->make($gateClass)->authorizes($request)) {
                return true;
            }
        }

        return false;
    }

    private function isConfigured(): bool
    {
        $access = config('health-route.access', []);

        $basicAuthConfigured = filled($access['basic_auth']['username'] ?? null)
            && filled($access['basic_auth']['password'] ?? null);

        $tokenConfigured = filled($access['token']['value'] ?? null);

        $staticIpsConfigured = array_filter($access['allowed_ips'] ?? []) !== [];

        $dynamicIpsConfigured = array_filter($access['allowed_hostnames'] ?? []) !== [];

        $localBypassConfigured = (bool) ($access['bypass_when_local'] ?? false);

        return $basicAuthConfigured
            || $tokenConfigured
            || $staticIpsConfigured
            || $dynamicIpsConfigured
            || $localBypassConfigured;
    }
}
