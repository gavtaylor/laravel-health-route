<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Http\Middleware;

use Closure;
use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\CheckRunner;
use GavTaylor\HealthRoute\Checks\CheckStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a lightweight check-status header to the response of any route this
 * middleware is attached to. Reads through CheckRunner's shared cache
 * (see config('health-route.checks_cache_seconds')), so attaching this to
 * many routes doesn't force the full check suite to re-run on every
 * request it touches.
 */
final class AddHealthStatusHeader
{
    public function __construct(
        private readonly CheckRunner $checkRunner,
    ) {
        //
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('health-route.status_header.enabled', false)) {
            return $response;
        }

        $checks = $this->checkRunner->run();

        $status = collect($checks)->contains(fn (CheckResult $check) => $check->status === CheckStatus::Down)
            ? 'down'
            : 'up';

        $response->headers->set(
            config('health-route.status_header.name', 'X-Health-Status'),
            $status,
        );

        return $response;
    }
}
