<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Tests;

use GavTaylor\HealthRoute\HealthRouteServiceProvider;
use Monolog\Handler\NullHandler;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HealthRouteServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', false);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.null', ['driver' => 'monolog', 'handler' => NullHandler::class]);
    }
}
