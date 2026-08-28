<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use Illuminate\Http\Request;

final class LocalEnvironmentBypassGate implements AccessGate
{
    public function authorizes(Request $request): bool
    {
        if (! config('health-route.access.bypass_when_local', false)) {
            return false;
        }

        // Checked against the application's own environment, never the
        // request's IP - a client IP can be spoofed or misreported by a
        // misconfigured reverse proxy, but this can't be forged.
        return app()->environment('local');
    }
}
