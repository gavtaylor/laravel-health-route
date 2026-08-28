<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\DependencyAdvisoryCheck;
use Illuminate\Support\Facades\Cache;

it('returns a cached result without re-running the audit', function () {
    Cache::put('health-route.dependency-advisories', [
        'status' => 'degraded',
        'message' => '2 dependency advisory(ies) found.',
        'context' => null,
    ], 3600);

    $result = app(DependencyAdvisoryCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Degraded);
    expect($result->message)->toBe('2 dependency advisory(ies) found.');
});

it('caches its result after a fresh run so the audit only runs once per window', function () {
    expect(Cache::has('health-route.dependency-advisories'))->toBeFalse();

    $result = app(DependencyAdvisoryCheck::class)->run();

    expect($result)->toBeInstanceOf(CheckResult::class);
    expect(Cache::has('health-route.dependency-advisories'))->toBeTrue();
})->skip(fn () => ! shell_exec('command -v composer'), 'composer binary not available');
