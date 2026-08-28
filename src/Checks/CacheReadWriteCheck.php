<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

final class CacheReadWriteCheck implements Check
{
    public function name(): string
    {
        return 'cache';
    }

    public function run(): CheckResult
    {
        $store = Cache::store(config('health-route.checks_config.cache.store'));
        $key = 'health-route.cache-check.'.Str::random(8);
        $value = Str::random(16);

        try {
            $store->put($key, $value, 10);

            if ($store->get($key) !== $value) {
                return CheckResult::down($this->name(), 'Cache write succeeded but the read-back value did not match.');
            }

            return CheckResult::up($this->name());
        } catch (Throwable) {
            return CheckResult::down($this->name(), 'Could not read from or write to the cache.');
        } finally {
            try {
                $store->forget($key);
            } catch (Throwable) {
                // Best-effort cleanup only - the check's result above stands either way.
            }
        }
    }
}
