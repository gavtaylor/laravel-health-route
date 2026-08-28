<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\CrossServiceCheck;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('is down when no URL is configured', function () {
    config(['health-route.checks_config.cross_service.url' => null]);

    expect(app(CrossServiceCheck::class)->run()->status)->toBe(CheckStatus::Down);
});

it('is up when the remote service reports itself as up', function () {
    config(['health-route.checks_config.cross_service.url' => 'https://other-service.test/up']);
    Http::fake(['https://other-service.test/up' => Http::response(['status' => 'up'], 200)]);

    expect(app(CrossServiceCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is down when the remote service reports itself as down', function () {
    config(['health-route.checks_config.cross_service.url' => 'https://other-service.test/up']);
    Http::fake(['https://other-service.test/up' => Http::response(['status' => 'down'], 500)]);

    expect(app(CrossServiceCheck::class)->run()->status)->toBe(CheckStatus::Down);
});

it('is down without leaking connection detail when the remote service is unreachable', function () {
    config(['health-route.checks_config.cross_service.url' => 'https://other-service.test/up']);
    Http::fake(['https://other-service.test/up' => fn () => throw new ConnectionException('DNS lookup failed for other-service.test')]);

    $result = app(CrossServiceCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->not->toContain('DNS lookup failed');
});
