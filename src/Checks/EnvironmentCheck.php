<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;

/**
 * Sanity-checks the running environment: that a configured set of
 * required environment variables are actually present, and that debug
 * mode isn't accidentally enabled outside an environment where that's
 * expected. Reads the real process environment (getenv()), not
 * config()'s already-resolved values - a required var might not be wired
 * into any config file at all.
 */
final class EnvironmentCheck implements Check
{
    public function name(): string
    {
        return 'environment';
    }

    public function run(): CheckResult
    {
        /** @var list<string> $required */
        $required = config('health-route.checks_config.environment.required_vars', []);

        $missing = array_values(array_filter(
            $required,
            fn (string $name): bool => ! $this->isSet($name),
        ));

        if ($missing !== []) {
            return CheckResult::down(
                $this->name(),
                sprintf('Missing required environment variable(s): %s.', implode(', ', $missing)),
                ['missing' => $missing],
            );
        }

        /** @var list<string> $safeEnvironments */
        $safeEnvironments = config('health-route.checks_config.environment.debug_safe_environments', ['local', 'testing']);

        if (config('app.debug') === true && ! app()->environment($safeEnvironments)) {
            return CheckResult::down(
                $this->name(),
                sprintf('Debug mode is enabled in the "%s" environment.', app()->environment()),
            );
        }

        return CheckResult::up($this->name());
    }

    private function isSet(string $name): bool
    {
        $value = getenv($name);

        return $value !== false && $value !== '';
    }
}
