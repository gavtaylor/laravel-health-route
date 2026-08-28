<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Support\RouteCollisionWarning;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

it('warns when a route is already registered for the path', function () {
    Route::get('collision-test', fn () => 'existing');

    Log::shouldReceive('warning')->once()->withArgs(fn (string $message) => str_contains($message, '"/collision-test"'));

    (new RouteCollisionWarning(app('router'), '/collision-test'))->check();
});

it('does not warn when no route is yet registered for the path', function () {
    Log::shouldReceive('warning')->never();

    (new RouteCollisionWarning(app('router'), '/collision-test'))->check();
});
