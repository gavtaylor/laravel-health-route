<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Throwable;

final class CheckRunner
{
    public function __construct(
        private readonly Container $container,
        private readonly CacheRepository $cache,
    ) {
        //
    }

    /**
     * Run every configured check, isolating and sanitising failures so a
     * broken check can never leak exception detail into the response, nor
     * take down the whole endpoint.
     *
     * @return list<CheckResult>
     *
     * @throws Throwable when a check fails and debug mode is enabled
     */
    public function run(): array
    {
        /** @var list<class-string<Check>> $checkClasses */
        $checkClasses = config('health-route.checks', []);

        if ($checkClasses === []) {
            return [];
        }

        $ttl = (int) config('health-route.checks_cache_seconds', 5);

        if ($ttl <= 0) {
            return $this->runAll($checkClasses);
        }

        $cacheKey = $this->cacheKey($checkClasses);

        $cached = $this->readCache($cacheKey);

        if ($cached !== null) {
            return $this->hydrate($cached, $checkClasses);
        }

        $results = $this->runAll($checkClasses);

        $this->writeCache($cacheKey, $results, $ttl);

        return $results;
    }

    /**
     * @param  list<class-string<Check>>  $checkClasses
     */
    private function cacheKey(array $checkClasses): string
    {
        return 'health-route.checks.'.hash('xxh3', implode("\0", $checkClasses));
    }

    /**
     * Read a previous run's cached results. Only cache I/O failures are
     * caught here (e.g. the cache store itself being down) - never a
     * check's own execution, which happens later in runAll() and must be
     * free to propagate (in debug mode) exactly once.
     *
     * @return list<array{name: string, status: string, message: string|null}>|null
     */
    private function readCache(string $cacheKey): ?array
    {
        try {
            /** @var list<array{name: string, status: string, message: string|null}>|null $cached */
            $cached = $this->cache->get($cacheKey);

            return $cached;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @param  list<CheckResult>  $results
     */
    private function writeCache(string $cacheKey, array $results, int $ttl): void
    {
        try {
            $this->cache->put(
                $cacheKey,
                array_map(fn (CheckResult $result) => $result->toArray(), $results),
                $ttl,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  list<array{name: string, status: string, message: string|null}>  $cached
     * @param  list<class-string<Check>>  $checkClasses
     * @return list<CheckResult>
     *
     * @throws Throwable when re-running falls back to a failing check in debug mode
     */
    private function hydrate(array $cached, array $checkClasses): array
    {
        try {
            return array_map(
                fn (array $result) => new CheckResult(
                    $result['name'],
                    CheckStatus::from($result['status']),
                    $result['message'],
                ),
                $cached,
            );
        } catch (Throwable $e) {
            report($e);

            return $this->runAll($checkClasses);
        }
    }

    /**
     * @param  list<class-string<Check>>  $checkClasses
     * @return list<CheckResult>
     *
     * @throws Throwable when a check fails and debug mode is enabled
     */
    private function runAll(array $checkClasses): array
    {
        return array_map(
            fn (string $checkClass) => $this->runOne($checkClass),
            $checkClasses,
        );
    }

    /**
     * @param  class-string<Check>  $checkClass
     *
     * @throws Throwable when the check fails and debug mode is enabled
     */
    private function runOne(string $checkClass): CheckResult
    {
        $name = class_basename($checkClass);

        try {
            $check = $this->container->make($checkClass);
            $name = $check->name();

            return $check->run();
        } catch (Throwable $e) {
            if (app()->hasDebugModeEnabled()) {
                throw $e;
            }

            report($e);

            return CheckResult::down($name, 'Check failed.');
        }
    }
}
