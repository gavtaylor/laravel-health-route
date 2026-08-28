<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\SchedulerLivenessCheck;
use GavTaylor\HealthRoute\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

/**
 * Whether the scheduler heartbeat gets registered is decided once, at boot
 * time, from config('health-route.checks_config.scheduler.register_heartbeat').
 * That can't be toggled from inside a test body (the app is already booted
 * by then), so this gets its own TestCase that enables it before boot.
 */
class SchedulerHeartbeatEnabledTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('health-route.checks_config.scheduler.register_heartbeat', true);
    }
}

uses(SchedulerHeartbeatEnabledTestCase::class)->in(__DIR__);

it('registers and records a heartbeat when enabled at boot time', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'health-route-heartbeat');

    expect($event)->not->toBeNull();

    $event->run(app());

    expect(Cache::get(SchedulerLivenessCheck::HEARTBEAT_CACHE_KEY))->not->toBeNull();
});
