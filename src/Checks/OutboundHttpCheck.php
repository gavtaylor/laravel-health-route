<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Support\Facades\Http;
use Throwable;

final class OutboundHttpCheck implements Check
{
    public function name(): string
    {
        return 'outbound-http';
    }

    public function run(): CheckResult
    {
        $url = config('health-route.checks_config.outbound_http.url');

        if (! is_string($url) || $url === '') {
            return CheckResult::down($this->name(), 'No URL is configured for the outbound HTTP check.');
        }

        $timeout = (int) config('health-route.checks_config.outbound_http.timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min(3, $timeout))
                ->withoutRedirecting()
                ->get($url);

            if ($response->successful()) {
                return CheckResult::up($this->name());
            }

            return CheckResult::degraded($this->name(), sprintf('Received HTTP %d.', $response->status()));
        } catch (Throwable) {
            return CheckResult::down($this->name(), 'Could not reach the configured URL.');
        }
    }
}
