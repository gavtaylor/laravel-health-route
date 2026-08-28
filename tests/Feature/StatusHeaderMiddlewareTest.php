<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Support\Facades\Route;

it('adds no header when disabled', function () {
    config(['health-route.status_header.enabled' => false]);

    Route::middleware('health-status')->get('/other', fn () => 'ok');

    $this->get('/other')->assertHeaderMissing('X-Health-Status');
});

it('adds the configured header when enabled', function () {
    config(['health-route.status_header.enabled' => true]);

    Route::middleware('health-status')->get('/other', fn () => 'ok');

    $this->get('/other')->assertHeader('X-Health-Status', 'up');
});

it('reflects a down check in the header', function () {
    config([
        'health-route.status_header.enabled' => true,
        'health-route.checks' => [StatusHeaderDownCheckStub::class],
    ]);

    Route::middleware('health-status')->get('/other', fn () => 'ok');

    $this->get('/other')->assertHeader('X-Health-Status', 'down');
});

it('only runs the check suite once within the cache window across multiple requests', function () {
    config([
        'health-route.status_header.enabled' => true,
        'health-route.checks' => [StatusHeaderCountingCheckStub::class],
        'health-route.checks_cache_seconds' => 60,
    ]);

    StatusHeaderCountingCheckStub::$calls = 0;

    Route::middleware('health-status')->get('/other-1', fn () => 'ok');
    Route::middleware('health-status')->get('/other-2', fn () => 'ok');

    $this->get('/other-1');
    $this->get('/other-2');

    expect(StatusHeaderCountingCheckStub::$calls)->toBe(1);
});

class StatusHeaderDownCheckStub implements Check
{
    public function name(): string
    {
        return 'stub';
    }

    public function run(): CheckResult
    {
        return CheckResult::down($this->name());
    }
}

class StatusHeaderCountingCheckStub implements Check
{
    public static int $calls = 0;

    public function name(): string
    {
        return 'stub';
    }

    public function run(): CheckResult
    {
        self::$calls++;

        return CheckResult::up($this->name());
    }
}
