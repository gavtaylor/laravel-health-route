<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access\Contracts;

use Illuminate\Http\Request;

interface AccessGate
{
    /**
     * Whether this gate authorises the given request.
     *
     * Implementations must return false whenever they are not configured -
     * an absent/empty configuration must never accidentally match an
     * absent/empty credential on the request.
     */
    public function authorizes(Request $request): bool;
}
