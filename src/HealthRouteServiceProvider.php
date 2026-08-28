<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute;

use GavTaylor\HealthRoute\Access\Contracts\DnsResolver;
use GavTaylor\HealthRoute\Access\PhpDnsResolver;
use GavTaylor\HealthRoute\Checks\SchedulerLivenessCheck;
use GavTaylor\HealthRoute\Http\Controllers\HealthRouteController;
use GavTaylor\HealthRoute\Http\Middleware\AddHealthStatusHeader;
use GavTaylor\HealthRoute\Http\Middleware\EnsureHealthRouteAccess;
use GavTaylor\HealthRoute\Support\RouteCollisionWarning;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class HealthRouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/health-route.php', 'health-route');

        $this->app->bind(DnsResolver::class, PhpDnsResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'health-route');

        $this->registerRoute();

        $this->app->make(Router::class)->aliasMiddleware('health-status', AddHealthStatusHeader::class);

        if (config('health-route.checks_config.scheduler.register_heartbeat', false)) {
            $this->registerSchedulerHeartbeat();
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/health-route.php' => config_path('health-route.php'),
        ], ['health-route', 'health-route-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/health-route'),
        ], ['health-route', 'health-route-views']);

        $this->publishes([
            __DIR__.'/../resources/static/up.txt' => public_path(config('health-route.static_filename', 'up.txt')),
        ], ['health-route-static']);
    }

    private function registerRoute(): void
    {
        if (! config('health-route.enabled', true)) {
            return;
        }

        $path = (string) config('health-route.path', '/up');

        (new RouteCollisionWarning($this->app->make('router'), $path))->check();

        Route::get($path, HealthRouteController::class)->middleware(EnsureHealthRouteAccess::class);

        PreventRequestsDuringMaintenance::except($path);
    }

    private function registerSchedulerHeartbeat(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)
                ->call(function (): void {
                    $this->app->make(CacheRepository::class)->forever(
                        SchedulerLivenessCheck::HEARTBEAT_CACHE_KEY,
                        now(),
                    );
                })
                ->everyMinute()
                ->name('health-route-heartbeat');
        });
    }
}
