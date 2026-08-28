<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use GavTaylor\HealthRoute\Support\WritableDirectory;

/**
 * Confirms the log directory is writable, by probing the filesystem
 * directly rather than writing through the Log facade - the latter would
 * put a real entry in production logs on every health-route request.
 */
final class LogWritableCheck implements Check
{
    public function name(): string
    {
        return 'log';
    }

    public function run(): CheckResult
    {
        $path = (string) config('health-route.checks_config.log.path', storage_path('logs'));

        if (! WritableDirectory::check($path)) {
            return CheckResult::down($this->name(), 'Log directory is not writable.');
        }

        return CheckResult::up($this->name());
    }
}
