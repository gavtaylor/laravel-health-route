<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\RedisCheck;

it('is down without leaking exception detail when Redis is unreachable', function () {
    config([
        'database.redis.default' => [
            'host' => '127.0.0.1',
            'port' => 1, // nothing listens here
            'timeout' => 0.1,
        ],
    ]);

    $result = app(RedisCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->not->toBeNull();
    expect($result->message)->not->toContain('127.0.0.1');
});
