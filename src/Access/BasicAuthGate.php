<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use Illuminate\Http\Request;

final class BasicAuthGate implements AccessGate
{
    public function authorizes(Request $request): bool
    {
        $username = config('health-route.access.basic_auth.username');
        $password = config('health-route.access.basic_auth.password');

        if (! is_string($username) || $username === '' || ! is_string($password) || $password === '') {
            return false;
        }

        $providedUsername = $request->getUser();
        $providedPassword = $request->getPassword();

        if (! is_string($providedUsername) || $providedUsername === '' || ! is_string($providedPassword) || $providedPassword === '') {
            return false;
        }

        return hash_equals($username, $providedUsername) && hash_equals($password, $providedPassword);
    }
}
