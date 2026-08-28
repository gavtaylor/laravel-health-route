<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Support;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;

/**
 * Detects another route already registered at the same path (e.g. an app
 * that forgot to remove core's `health:` argument from withRouting()) and
 * surfaces a warning, rather than silently producing two competing
 * handlers for the same URI.
 *
 * This must run *before* this package registers its own route: Laravel's
 * router collapses two GET routes registered at the same URI into one (the
 * later registration silently wins), so a route already present is only
 * observable up until the moment we add ours on top of it.
 */
final class RouteCollisionWarning
{
    public function __construct(
        private readonly Router $router,
        private readonly string $path,
    ) {
        //
    }

    public function check(): void
    {
        $uri = ltrim($this->path, '/');

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array('GET', $route->methods(), true)) {
                Log::warning(sprintf(
                    'gavtaylor/laravel-health-route: a route is already registered for "%s". '.
                    'Did you forget to remove `health:` from withRouting() in bootstrap/app.php?',
                    $this->path,
                ));

                return;
            }
        }
    }
}
