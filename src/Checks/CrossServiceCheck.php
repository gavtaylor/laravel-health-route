<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Probes another service's own health-style endpoint, interpreting its
 * `status` field the same way this package's own endpoint reports it.
 */
final class CrossServiceCheck implements Check
{
    public function name(): string
    {
        return 'cross-service';
    }

    public function run(): CheckResult
    {
        $url = config('health-route.checks_config.cross_service.url');

        if (! is_string($url) || $url === '') {
            return CheckResult::down($this->name(), 'No URL is configured for the cross-service check.');
        }

        $timeout = (int) config('health-route.checks_config.cross_service.timeout', 5);

        try {
            $response = Http::timeout($timeout)->acceptJson()->get($url);

            if (! $response->successful()) {
                return CheckResult::down($this->name(), sprintf('Received HTTP %d.', $response->status()));
            }

            $status = $response->json('status');

            if ($status === 'down') {
                return CheckResult::down($this->name(), 'The remote service reports itself as down.');
            }

            return CheckResult::up($this->name());
        } catch (Throwable) {
            return CheckResult::down($this->name(), 'Could not reach the remote service.');
        }
    }
}
