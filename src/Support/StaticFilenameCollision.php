<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Support;

use Illuminate\Support\Facades\Log;

/**
 * A published static liveness file is served by the web server before
 * Laravel. If its public filename matches the dynamic health route, the
 * framework (and this package's access control) never runs for that path.
 *
 * This is surfaced as a critical log entry rather than an exception: an
 * uncaught exception here runs on every request (service providers boot
 * every request), so it would take down the *entire* application - not
 * just the health route - on what is ultimately a static-file-publish-time
 * misconfiguration. A developer is expected to notice and fix this from
 * the log, not have their whole app go down over it.
 */
final class StaticFilenameCollision
{
    public static function check(string $path, string $staticFilename): void
    {
        $route = ltrim($path, '/');
        $file = ltrim($staticFilename, '/');

        if ($file === '' || $route !== $file) {
            return;
        }

        Log::critical(sprintf(
            'gavtaylor/laravel-health-route: static_filename ("%s") matches the health route path. '.
            'If published, most web servers will serve that static file directly and silently bypass the dynamic route, including its access control. Choose a different static_filename.',
            $staticFilename,
        ));
    }
}
