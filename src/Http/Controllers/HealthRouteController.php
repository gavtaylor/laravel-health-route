<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Http\Controllers;

use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\CheckRunner;
use GavTaylor\HealthRoute\Checks\CheckStatus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class HealthRouteController
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly CheckRunner $checkRunner,
    ) {
        //
    }

    /**
     * @throws Throwable when a DiagnosingHealth listener or a configured
     *                   check fails and debug mode is enabled
     */
    public function __invoke(Request $request): Response
    {
        $coreException = $this->diagnoseCoreHealth();

        $checks = $coreException !== null ? [] : $this->checkRunner->run();

        $anyCheckDown = collect($checks)->contains(fn (CheckResult $check) => $check->status === CheckStatus::Down);

        $down = $coreException !== null || $anyCheckDown;

        $statusCode = match (true) {
            $coreException !== null => 500,
            $anyCheckDown => (int) config('health-route.problem_status_code', 503),
            default => 200,
        };

        if ($request->expectsJson()) {
            return $this->jsonResponse($down, $checks, $statusCode);
        }

        return $this->htmlResponse($coreException, $down, $checks, $statusCode);
    }

    /**
     * Dispatch DiagnosingHealth exactly like Laravel core's own health
     * route: a listener fails the check by throwing. In debug mode the
     * exception propagates to the framework's own exception handler
     * instead of being caught here.
     *
     * @throws Throwable when a listener fails and debug mode is enabled
     */
    private function diagnoseCoreHealth(): ?Throwable
    {
        try {
            $this->events->dispatch(new DiagnosingHealth);

            return null;
        } catch (Throwable $e) {
            if (app()->hasDebugModeEnabled()) {
                throw $e;
            }

            report($e);

            return $e;
        }
    }

    /**
     * @param  list<CheckResult>  $checks
     */
    private function jsonResponse(bool $down, array $checks, int $statusCode): Response
    {
        $payload = [
            'status' => $down ? 'down' : 'up',
        ];

        if ($checks !== []) {
            $payload['checks'] = array_map(fn (CheckResult $check) => $check->toArray(), $checks);
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * @param  list<CheckResult>  $checks
     */
    private function htmlResponse(?Throwable $exception, bool $down, array $checks, int $statusCode): Response
    {
        return response(
            View::make('health-route::default', [
                'exception' => $exception,
                'down' => $down,
                'checks' => $checks,
            ])->render(),
            $statusCode,
        );
    }
}
