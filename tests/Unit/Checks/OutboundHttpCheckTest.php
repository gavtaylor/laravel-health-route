<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\OutboundHttpCheck;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('is down when no URL is configured', function () {
    config(['health-route.checks_config.outbound_http.url' => null]);

    expect(app(OutboundHttpCheck::class)->run()->status)->toBe(CheckStatus::Down);
});

it('is up when the configured URL responds successfully', function () {
    config(['health-route.checks_config.outbound_http.url' => 'https://example.test/status']);
    Http::fake(['https://example.test/status' => Http::response('ok', 200)]);

    expect(app(OutboundHttpCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is degraded when the configured URL responds with a non-2xx status', function () {
    config(['health-route.checks_config.outbound_http.url' => 'https://example.test/status']);
    Http::fake(['https://example.test/status' => Http::response('error', 500)]);

    expect(app(OutboundHttpCheck::class)->run()->status)->toBe(CheckStatus::Degraded);
});

it('is down without leaking connection detail when the request fails entirely', function () {
    config(['health-route.checks_config.outbound_http.url' => 'https://example.test/status']);
    Http::fake(['https://example.test/status' => fn () => throw new ConnectionException('Could not resolve host: example.test')]);

    $result = app(OutboundHttpCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->not->toContain('resolve host');
});
