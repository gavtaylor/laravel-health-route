<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\DatabaseConnectionCheck;

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);
});

it('is up when it can query the configured connection', function () {
    $result = app(DatabaseConnectionCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Up);
});

it('is down without leaking exception detail when the connection fails', function () {
    config(['health-route.checks_config.database.connection' => 'missing-connection']);

    $result = app(DatabaseConnectionCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->not->toBeNull();
    expect($result->message)->not->toContain('missing-connection');
});
