<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\EnvironmentCheck;

afterEach(function () {
    putenv('HEALTH_ROUTE_TEST_VAR');
});

it('is up when nothing is configured', function () {
    expect(app(EnvironmentCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is up when every required variable is present', function () {
    putenv('HEALTH_ROUTE_TEST_VAR=some-value');
    config(['health-route.checks_config.environment.required_vars' => ['HEALTH_ROUTE_TEST_VAR']]);

    expect(app(EnvironmentCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is down and names the missing variable when a required one is absent', function () {
    config(['health-route.checks_config.environment.required_vars' => ['HEALTH_ROUTE_TEST_VAR']]);

    $result = app(EnvironmentCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->toContain('HEALTH_ROUTE_TEST_VAR');
    expect($result->context['missing'])->toBe(['HEALTH_ROUTE_TEST_VAR']);
});

it('is down and names the missing variable when it is present but empty', function () {
    putenv('HEALTH_ROUTE_TEST_VAR=');
    config(['health-route.checks_config.environment.required_vars' => ['HEALTH_ROUTE_TEST_VAR']]);

    expect(app(EnvironmentCheck::class)->run()->status)->toBe(CheckStatus::Down);
});

it('is down when debug mode is enabled outside the safe environments', function () {
    config(['app.debug' => true]);
    app()->detectEnvironment(fn () => 'production');

    $result = app(EnvironmentCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->toContain('production');
});

it('is up when debug mode is enabled inside a configured safe environment', function () {
    config(['app.debug' => true]);
    app()->detectEnvironment(fn () => 'local');

    expect(app(EnvironmentCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is up when debug mode is disabled regardless of environment', function () {
    config(['app.debug' => false]);
    app()->detectEnvironment(fn () => 'production');

    expect(app(EnvironmentCheck::class)->run()->status)->toBe(CheckStatus::Up);
});
