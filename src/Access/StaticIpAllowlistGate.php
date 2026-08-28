<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use GavTaylor\HealthRoute\Support\IpMatcher;
use Illuminate\Http\Request;

final class StaticIpAllowlistGate implements AccessGate
{
    public function authorizes(Request $request): bool
    {
        /** @var array<array-key, string> $configured */
        $configured = config('health-route.access.allowed_ips', []);

        $allowlist = array_values(array_filter($configured));

        if ($allowlist === []) {
            return false;
        }

        $ip = $request->ip();

        if ($ip === null) {
            return false;
        }

        return IpMatcher::matches($ip, $allowlist);
    }
}
