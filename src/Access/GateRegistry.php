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
    public const array DEFAULT_GATES = [
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
     * The endpoint is public by default: if none of the currently active
     * gates (see the `gates` config option) are configured, every request
     * is authorised. Once at least one active gate is configured, passing
     * any single one is enough.
     */
    public function authorizes(Request $request): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        foreach ($this->gates() as $gateClass) {
            if ($this->container->make($gateClass)->authorizes($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether any gate *currently in the active list* is actually
     * configured. This deliberately looks at the active list rather than
     * config('health-route.access.*') in isolation: a value like
     * basic_auth credentials must not count as "configured" if
     * BasicAuthGate has been removed from `gates` - otherwise a caller
     * with valid credentials for an excluded gate would be denied by
     * every *other* active gate, even though nothing meant to gate them
     * in the first place (a fail-closed lockout, not a real restriction).
     */
    private function isConfigured(): bool
    {
        $access = config('health-route.access', []);

        foreach ($this->gates() as $gateClass) {
            if ($this->gateIsConfigured($gateClass, is_array($access) ? $access : [])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  class-string<AccessGate>  $gateClass
     * @param  array<string, mixed>  $access
     */
    private function gateIsConfigured(string $gateClass, array $access): bool
    {
        return match ($gateClass) {
            BasicAuthGate::class => filled($access['basic_auth']['username'] ?? null)
                && filled($access['basic_auth']['password'] ?? null),
            SharedSecretHeaderGate::class => filled($access['token']['value'] ?? null),
            StaticIpAllowlistGate::class => array_filter($access['allowed_ips'] ?? []) !== [],
            DynamicIpAllowlistGate::class => array_filter($access['allowed_hostnames'] ?? []) !== [],
            LocalEnvironmentBypassGate::class => (bool) ($access['bypass_when_local'] ?? false),
            // A gate class this package doesn't recognise (a custom,
            // app-supplied gate) is assumed deliberately added - its mere
            // presence in the active list counts as "configured", since
            // this package has no way to introspect its own config.
            default => true,
        };
    }

    /**
     * @return list<class-string<AccessGate>>
     */
    private function gates(): array
    {
        $configured = config('health-route.access.gates', self::DEFAULT_GATES);

        if (! is_array($configured) || $configured === []) {
            return self::DEFAULT_GATES;
        }

        /** @var list<class-string<AccessGate>> */
        return array_values(array_filter(
            $configured,
            fn (mixed $gate): bool => is_string($gate) && $gate !== '',
        ));
    }
}
