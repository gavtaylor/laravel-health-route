<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Wraps `composer audit`, which is inherently expensive - its own result is
 * cached far longer than the shared check-result cache (see
 * health-route.checks_config.dependency_advisory.cache_hours).
 */
final class DependencyAdvisoryCheck implements Check
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {
        //
    }

    public function name(): string
    {
        return 'dependency-advisories';
    }

    public function run(): CheckResult
    {
        $hours = (int) config('health-route.checks_config.dependency_advisory.cache_hours', 12);

        /** @var array{status: string, message: string|null, context: array<string, mixed>|null}|null $cached */
        $cached = $this->cache->get('health-route.dependency-advisories');

        if ($cached !== null) {
            return new CheckResult($this->name(), CheckStatus::from($cached['status']), $cached['message'], $cached['context']);
        }

        $result = $this->audit();

        $this->cache->put('health-route.dependency-advisories', [
            'status' => $result->status->value,
            'message' => $result->message,
            'context' => $result->context,
        ], max(1, $hours * 3600));

        return $result;
    }

    private function audit(): CheckResult
    {
        try {
            $process = new Process(['composer', 'audit', '--format=json', '--no-interaction'], base_path());
            $process->setTimeout(30);
            $process->run();

            $output = json_decode($process->getOutput(), true);

            if (! is_array($output)) {
                return CheckResult::degraded($this->name(), 'Could not determine dependency advisory status.');
            }

            $advisories = $output['advisories'] ?? [];
            $count = is_array($advisories) ? count($advisories) : 0;

            if ($count > 0) {
                return CheckResult::degraded($this->name(), sprintf('%d dependency advisory(ies) found.', $count));
            }

            return CheckResult::up($this->name());
        } catch (Throwable) {
            return CheckResult::degraded($this->name(), 'Could not run the dependency advisory audit.');
        }
    }
}
