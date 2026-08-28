<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Tests\TestCase;

/**
 * Whether static_filename collides with the route path is only knowable
 * from config at boot time, so this needs its own TestCase that sets both
 * before the app boots (same reasoning as the scheduler heartbeat tests).
 */
class StaticFilenameCollisionTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('health-route.static_filename', 'up');
        $app['config']->set('logging.default', 'single');
        $app['config']->set('logging.channels.single.path', $this->logPath());
    }

    protected function logPath(): string
    {
        return sys_get_temp_dir().'/health-route-static-collision-test.log';
    }
}

uses(StaticFilenameCollisionTestCase::class)->in(__DIR__);

afterEach(function () {
    @unlink($this->logPath());
});

it('warns at boot time when static_filename matches the health route path', function () {
    expect(file_exists($this->logPath()))->toBeTrue();

    $log = file_get_contents($this->logPath());

    expect($log)->toContain('gavtaylor/laravel-health-route')
        ->toContain('static_filename ("up")');
});
