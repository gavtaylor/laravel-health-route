<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use Illuminate\Http\Request;

final class SharedSecretHeaderGate implements AccessGate
{
    public function authorizes(Request $request): bool
    {
        $header = config('health-route.access.token.header');
        $value = config('health-route.access.token.value');

        if (! is_string($header) || $header === '' || ! is_string($value) || $value === '') {
            return false;
        }

        $provided = $request->header($header);

        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($value, $provided);
    }
}
