<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\CheckRunner;
use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Contracts\Cache\Repository;

it('returns an empty list when no checks are configured', function () {
    config(['health-route.checks' => []]);

    expect(app(CheckRunner::class)->run())->toBe([]);
});

it('runs every configured check', function () {
    config(['health-route.checks' => [PassingCheck::class, AnotherPassingCheck::class]]);

    $results = app(CheckRunner::class)->run();

    expect($results)->toHaveCount(2);
    expect($results[0]->name)->toBe('passing');
    expect($results[1]->name)->toBe('another-passing');
});

it('isolates a check that throws into a down result with a sanitised message', function () {
    config(['health-route.checks' => [ThrowingRunnerCheck::class]]);

    $results = app(CheckRunner::class)->run();

    expect($results)->toHaveCount(1);
    expect($results[0]->status)->toBe(CheckStatus::Down);
    expect($results[0]->message)->not->toContain('/etc/shadow');
});

it('rethrows a check failure in debug mode instead of swallowing it', function () {
    config(['app.debug' => true, 'health-route.checks' => [ThrowingRunnerCheck::class]]);

    expect(fn () => app(CheckRunner::class)->run())->toThrow(RuntimeException::class);
});

it('caches results so a check only runs once within the cache window', function () {
    config(['health-route.checks' => [CountingCheck::class], 'health-route.checks_cache_seconds' => 60]);

    CountingCheck::$calls = 0;

    $runner = app(CheckRunner::class);
    $runner->run();
    $runner->run();

    expect(CountingCheck::$calls)->toBe(1);
});

it('runs checks uncached when reading the cache store fails', function () {
    config(['health-route.checks' => [CountingCheck::class], 'health-route.checks_cache_seconds' => 60]);

    CountingCheck::$calls = 0;

    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('get')->once()->andThrow(new RuntimeException('cache connection refused'));
    $cache->shouldReceive('put')->once();

    $results = (new CheckRunner(app(), $cache))->run();

    expect($results)->toHaveCount(1);
    expect($results[0]->status)->toBe(CheckStatus::Up);
    expect(CountingCheck::$calls)->toBe(1);
});

it('still returns results when writing to the cache store fails', function () {
    config(['health-route.checks' => [CountingCheck::class], 'health-route.checks_cache_seconds' => 60]);

    CountingCheck::$calls = 0;

    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('get')->once()->andReturn(null);
    $cache->shouldReceive('put')->once()->andThrow(new RuntimeException('cache connection refused'));

    $results = (new CheckRunner(app(), $cache))->run();

    expect($results)->toHaveCount(1);
    expect($results[0]->status)->toBe(CheckStatus::Up);
    expect(CountingCheck::$calls)->toBe(1);
});

it('executes a debug-mode check failure exactly once, without a duplicate rethrow via the cache layer', function () {
    config(['app.debug' => true, 'health-route.checks' => [ThrowingRunnerCheck::class], 'health-route.checks_cache_seconds' => 60]);

    ThrowingRunnerCheck::$calls = 0;

    expect(fn () => app(CheckRunner::class)->run())->toThrow(RuntimeException::class);
    expect(ThrowingRunnerCheck::$calls)->toBe(1);
});

it('runs fresh every time when caching is disabled', function () {
    config(['health-route.checks' => [CountingCheck::class], 'health-route.checks_cache_seconds' => 0]);

    CountingCheck::$calls = 0;

    $runner = app(CheckRunner::class);
    $runner->run();
    $runner->run();

    expect(CountingCheck::$calls)->toBe(2);
});

class PassingCheck implements Check
{
    public function name(): string
    {
        return 'passing';
    }

    public function run(): CheckResult
    {
        return CheckResult::up($this->name());
    }
}

class AnotherPassingCheck implements Check
{
    public function name(): string
    {
        return 'another-passing';
    }

    public function run(): CheckResult
    {
        return CheckResult::up($this->name());
    }
}

class ThrowingRunnerCheck implements Check
{
    public static int $calls = 0;

    public function name(): string
    {
        return 'throwing';
    }

    public function run(): CheckResult
    {
        self::$calls++;

        throw new RuntimeException('permission denied reading /etc/shadow');
    }
}

class CountingCheck implements Check
{
    public static int $calls = 0;

    public function name(): string
    {
        return 'counting';
    }

    public function run(): CheckResult
    {
        self::$calls++;

        return CheckResult::up($this->name());
    }
}
