<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Http\Middleware;

use Closure;
use GavTaylor\HealthRoute\Access\GateRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHealthRouteAccess
{
    public function __construct(
        private readonly GateRegistry $gates,
    ) {
        //
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Run the full route first, so the real HTTP status code always
        // reflects application health - only the body is withheld from a
        // caller that fails every configured access method.
        $response = $next($request);

        if ($this->gates->authorizes($request)) {
            return $response;
        }

        return response('', $response->getStatusCode());
    }
}
