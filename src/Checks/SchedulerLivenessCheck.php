<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Reports whether the scheduler is still running, based on a heartbeat
 * timestamp. Enable health-route.checks_config.scheduler.register_heartbeat
 * to have this package register the heartbeat itself, or write to the same
 * cache key (see HEARTBEAT_CACHE_KEY) from your own scheduled command.
 */
final class SchedulerLivenessCheck implements Check
{
    public const string HEARTBEAT_CACHE_KEY = 'health-route.scheduler-heartbeat';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {
        //
    }

    public function name(): string
    {
        return 'scheduler';
    }

    public function run(): CheckResult
    {
        $lastHeartbeat = $this->cache->get(self::HEARTBEAT_CACHE_KEY);

        if (! $lastHeartbeat instanceof DateTimeInterface) {
            return CheckResult::down($this->name(), 'No scheduler heartbeat has ever been recorded.');
        }

        $minutesSince = abs(CarbonImmutable::now()->diffInMinutes($lastHeartbeat));

        $downAfter = (int) config('health-route.checks_config.scheduler.down_after_minutes', 15);
        $degradedAfter = (int) config('health-route.checks_config.scheduler.degraded_after_minutes', 5);

        $context = ['minutes_since_last_heartbeat' => $minutesSince];

        if ($minutesSince >= $downAfter) {
            return CheckResult::down($this->name(), sprintf('No heartbeat for %d minute(s).', $minutesSince), $context);
        }

        if ($minutesSince >= $degradedAfter) {
            return CheckResult::degraded($this->name(), sprintf('No heartbeat for %d minute(s).', $minutesSince), $context);
        }

        return CheckResult::up($this->name());
    }
}
