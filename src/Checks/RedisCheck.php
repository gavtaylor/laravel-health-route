<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class RedisCheck implements Check
{
    public function name(): string
    {
        return 'redis';
    }

    public function run(): CheckResult
    {
        $connectionName = config('health-route.checks_config.redis.connection');

        try {
            $pong = Redis::connection($connectionName)->ping();

            if ($pong !== true && $pong !== 'PONG' && $pong !== '+PONG') {
                return CheckResult::down($this->name(), 'Redis did not respond to PING as expected.');
            }

            return CheckResult::up($this->name());
        } catch (Throwable) {
            return CheckResult::down($this->name(), 'Could not connect to Redis.');
        }
    }
}
