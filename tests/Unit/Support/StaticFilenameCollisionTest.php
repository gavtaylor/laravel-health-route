<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Support\StaticFilenameCollision;
use Illuminate\Support\Facades\Log;

it('logs critically when static_filename matches the health route path', function () {
    Log::shouldReceive('critical')->once()->withArgs(
        fn (string $message) => str_contains($message, 'static_filename ("up")')
            && str_contains($message, 'matches the health route path'),
    );

    StaticFilenameCollision::check('/up', 'up');
});

it('treats a leading slash on either side as the same public URI', function () {
    Log::shouldReceive('critical')->twice();

    StaticFilenameCollision::check('/up', 'up');
    StaticFilenameCollision::check('up', '/up');
});

it('does not log when the static filename does not match the route path', function () {
    Log::shouldReceive('critical')->never();

    StaticFilenameCollision::check('/up', 'up.txt');
    StaticFilenameCollision::check('/up', 'ping');
});

it('does not log for an empty static filename', function () {
    Log::shouldReceive('critical')->never();

    StaticFilenameCollision::check('/up', '');
});
