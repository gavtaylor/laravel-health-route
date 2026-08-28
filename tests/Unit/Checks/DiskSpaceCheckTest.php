<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\DiskSpaceCheck;

it('is up when free space is comfortably above both thresholds', function () {
    config([
        'health-route.checks_config.disk.path' => sys_get_temp_dir(),
        'health-route.checks_config.disk.degraded_below_percent' => 0,
        'health-route.checks_config.disk.down_below_percent' => 0,
    ]);

    $result = app(DiskSpaceCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Up);
    expect($result->context)->toHaveKey('percent_free');
});

it('is degraded when free space is below the degraded threshold but above the down threshold', function () {
    config([
        'health-route.checks_config.disk.path' => sys_get_temp_dir(),
        'health-route.checks_config.disk.degraded_below_percent' => 100,
        'health-route.checks_config.disk.down_below_percent' => 0,
    ]);

    $result = app(DiskSpaceCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Degraded);
});

it('is down when free space is below the down threshold', function () {
    config([
        'health-route.checks_config.disk.path' => sys_get_temp_dir(),
        'health-route.checks_config.disk.degraded_below_percent' => 100,
        'health-route.checks_config.disk.down_below_percent' => 100,
    ]);

    $result = app(DiskSpaceCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
});

it('is down when the configured path does not exist', function () {
    config(['health-route.checks_config.disk.path' => '/this/path/does/not/exist']);

    $result = app(DiskSpaceCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
});
