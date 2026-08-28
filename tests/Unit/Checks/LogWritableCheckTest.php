<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\LogWritableCheck;

it('is up when the log directory is writable', function () {
    $path = sys_get_temp_dir().'/health-route-log-'.uniqid();
    mkdir($path);
    config(['health-route.checks_config.log.path' => $path]);

    expect(app(LogWritableCheck::class)->run()->status)->toBe(CheckStatus::Up);

    rmdir($path);
});

it('is down when the log directory does not exist', function () {
    config(['health-route.checks_config.log.path' => '/this/path/does/not/exist']);

    $result = app(LogWritableCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->not->toBeNull();
});

it('is down when the log directory is not writable', function () {
    $path = sys_get_temp_dir().'/health-route-log-'.uniqid();
    mkdir($path, 0500);
    config(['health-route.checks_config.log.path' => $path]);

    expect(app(LogWritableCheck::class)->run()->status)->toBe(CheckStatus::Down);

    chmod($path, 0700);
    rmdir($path);
})->skip(fn () => PHP_OS_FAMILY === 'Windows', 'chmod is not meaningful on Windows');
