<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\SchedulerLivenessCheck;
use Illuminate\Support\Facades\Cache;

it('is down when no heartbeat has ever been recorded', function () {
    expect(app(SchedulerLivenessCheck::class)->run()->status)->toBe(CheckStatus::Down);
});

it('is up when the heartbeat is recent', function () {
    Cache::forever(SchedulerLivenessCheck::HEARTBEAT_CACHE_KEY, now());

    expect(app(SchedulerLivenessCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is degraded once the heartbeat is older than the degraded threshold', function () {
    config(['health-route.checks_config.scheduler.degraded_after_minutes' => 5]);
    Cache::forever(SchedulerLivenessCheck::HEARTBEAT_CACHE_KEY, now()->subMinutes(6));

    expect(app(SchedulerLivenessCheck::class)->run()->status)->toBe(CheckStatus::Degraded);
});

it('is down once the heartbeat is older than the down threshold', function () {
    config(['health-route.checks_config.scheduler.down_after_minutes' => 15]);
    Cache::forever(SchedulerLivenessCheck::HEARTBEAT_CACHE_KEY, now()->subMinutes(20));

    expect(app(SchedulerLivenessCheck::class)->run()->status)->toBe(CheckStatus::Down);
});
