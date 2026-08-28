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

        /** @var list<array{name: string, status: string, message: string|null}> $cached */
        $cached = $this->cache->remember(
            'health-route.checks',
            $ttl,
            fn () => array_map(
                fn (CheckResult $result) => $result->toArray(),
                $this->runAll($checkClasses),
            ),
        );

        return array_map(
            fn (array $result) => new CheckResult(
                $result['name'],
                CheckStatus::from($result['status']),
                $result['message'],
            ),
            $cached,
        );
    }

    /**
     * @param  list<class-string<Check>>  $checkClasses
     * @return list<CheckResult>
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
