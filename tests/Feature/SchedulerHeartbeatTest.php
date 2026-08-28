<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

it('does not register a heartbeat when disabled by default', function () {
    $events = app(Schedule::class)->events();

    expect(collect($events)->contains(fn ($event) => $event->description === 'health-route-heartbeat'))->toBeFalse();
});
