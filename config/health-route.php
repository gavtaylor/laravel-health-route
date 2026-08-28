<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Access\BasicAuthGate;
use GavTaylor\HealthRoute\Access\DynamicIpAllowlistGate;
use GavTaylor\HealthRoute\Access\LocalEnvironmentBypassGate;
use GavTaylor\HealthRoute\Access\SharedSecretHeaderGate;
use GavTaylor\HealthRoute\Access\StaticIpAllowlistGate;

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Whether this package registers its health route at all. Disable this
    | if you only want the static liveness file (see below), or if you are
    | migrating away from the package and want to fall back to Laravel's
    | own `health:` route without removing the package immediately.
    |
    */

    'enabled' => env('HEALTH_ROUTE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | The URI the health route is served at. Matches the default used by
    | Laravel core's own `health:` routing argument.
    |
    */

    'path' => env('HEALTH_ROUTE_PATH', '/up'),

    /*
    |--------------------------------------------------------------------------
    | Problem status code
    |--------------------------------------------------------------------------
    |
    | The HTTP status code returned when a registered check reports "down".
    | This is independent of Laravel core's own health-check failure mode
    | (an uncaught exception from a DiagnosingHealth listener), which always
    | returns 500 - matching core exactly, not configurable here.
    |
    */

    'problem_status_code' => env('HEALTH_ROUTE_PROBLEM_STATUS_CODE', 503),

    /*
    |--------------------------------------------------------------------------
    | Checks
    |--------------------------------------------------------------------------
    |
    | Named checks to run on every request to the health route. Each class
    | must implement \GavTaylor\HealthRoute\Checks\Contracts\Check. Nothing
    | runs unless it's listed here - every bundled check is opt-in.
    |
    */

    'checks' => [
        // \GavTaylor\HealthRoute\Checks\DatabaseConnectionCheck::class,
        // \GavTaylor\HealthRoute\Checks\CacheReadWriteCheck::class,
        // \GavTaylor\HealthRoute\Checks\RedisCheck::class,
        // \GavTaylor\HealthRoute\Checks\DiskSpaceCheck::class,
        // \GavTaylor\HealthRoute\Checks\StorageWritableCheck::class,
        // \GavTaylor\HealthRoute\Checks\LogWritableCheck::class,
        // \GavTaylor\HealthRoute\Checks\EnvironmentCheck::class,
        // \GavTaylor\HealthRoute\Checks\OutboundHttpCheck::class,
        // \GavTaylor\HealthRoute\Checks\PendingMigrationsCheck::class,
        // \GavTaylor\HealthRoute\Checks\SchedulerLivenessCheck::class,
        // \GavTaylor\HealthRoute\Checks\DependencyAdvisoryCheck::class,
        // \GavTaylor\HealthRoute\Checks\CrossServiceCheck::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bundled check configuration
    |--------------------------------------------------------------------------
    |
    | Tunables for the bundled checks above. Only read by a check if it's
    | actually enabled in the `checks` array above.
    |
    */

    'checks_config' => [

        'database' => [
            // The connection to check, or null for the default connection.
            'connection' => env('HEALTH_ROUTE_DATABASE_CONNECTION'),
        ],

        'cache' => [
            // The cache store to check, or null for the default store.
            'store' => env('HEALTH_ROUTE_CACHE_STORE'),
        ],

        'redis' => [
            // The Redis connection to check, or null for the default connection.
            'connection' => env('HEALTH_ROUTE_REDIS_CONNECTION'),
        ],

        'disk' => [
            // The path to check free space on.
            'path' => env('HEALTH_ROUTE_DISK_PATH', storage_path()),
            // Percentage of disk space free below which the check degrades/goes down.
            'degraded_below_percent' => env('HEALTH_ROUTE_DISK_DEGRADED_BELOW_PERCENT', 15),
            'down_below_percent' => env('HEALTH_ROUTE_DISK_DOWN_BELOW_PERCENT', 5),
        ],

        'storage' => [
            // Paths relative to storage_path() that must be writable.
            'paths' => ['framework/cache', 'framework/sessions', 'framework/views'],
        ],

        'log' => [
            // The log directory to check is writable, or null for the default.
            'path' => env('HEALTH_ROUTE_LOG_PATH', storage_path('logs')),
        ],

        'environment' => [
            // Environment variable names that must be present and non-empty.
            'required_vars' => array_filter(explode(',', (string) env('HEALTH_ROUTE_ENVIRONMENT_REQUIRED_VARS', ''))),
            // Environments allowed to have app.debug enabled - down outside these.
            'debug_safe_environments' => array_filter(explode(',', (string) env('HEALTH_ROUTE_ENVIRONMENT_DEBUG_SAFE', 'local,testing'))),
        ],

        'outbound_http' => [
            'url' => env('HEALTH_ROUTE_OUTBOUND_HTTP_URL'),
            'timeout' => env('HEALTH_ROUTE_OUTBOUND_HTTP_TIMEOUT', 5),
        ],

        'scheduler' => [
            // A scheduled heartbeat (registered by this package, opt-in via
            // health-route.checks_config.scheduler.register_heartbeat)
            // writes a timestamp this check compares itself against.
            'register_heartbeat' => env('HEALTH_ROUTE_SCHEDULER_REGISTER_HEARTBEAT', false),
            'degraded_after_minutes' => env('HEALTH_ROUTE_SCHEDULER_DEGRADED_AFTER_MINUTES', 5),
            'down_after_minutes' => env('HEALTH_ROUTE_SCHEDULER_DOWN_AFTER_MINUTES', 15),
        ],

        'dependency_advisory' => [
            // Wraps `composer audit` on the request path (cached for this
            // many hours). Needs a composer binary on PATH; typical production
            // images do not have one. Prefer a scheduled command that writes a
            // cache key, or leave this check disabled.
            'cache_hours' => env('HEALTH_ROUTE_DEPENDENCY_ADVISORY_CACHE_HOURS', 12),
        ],

        'cross_service' => [
            'url' => env('HEALTH_ROUTE_CROSS_SERVICE_URL'),
            'timeout' => env('HEALTH_ROUTE_CROSS_SERVICE_TIMEOUT', 5),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Check result cache
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) a run of the configured checks is cached for.
    | Shared between the health route itself and the optional status-header
    | middleware, so attaching that middleware to many routes - or hitting
    | the health route repeatedly - doesn't re-run the full check suite
    | more than once per window. Set to 0 to always run checks fresh.
    |
    */

    'checks_cache_seconds' => env('HEALTH_ROUTE_CHECKS_CACHE_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | Route name
    |--------------------------------------------------------------------------
    |
    | The name registered for the health route, so the app can generate a
    | URL with route('health-route').
    |
    */

    'route_name' => env('HEALTH_ROUTE_ROUTE_NAME', 'health-route'),

    /*
    |--------------------------------------------------------------------------
    | Extra middleware
    |--------------------------------------------------------------------------
    |
    | Additional middleware classes or aliases to run on the health route,
    | after this package's access middleware. Use this for throttle, or
    | app-specific IP middleware.
    |
    */

    'middleware' => array_values(array_filter(explode(',', (string) env('HEALTH_ROUTE_MIDDLEWARE', '')))),

    /*
    |--------------------------------------------------------------------------
    | Access control
    |--------------------------------------------------------------------------
    |
    | The health route is public by default, matching Laravel core. Each of
    | the methods below is independently opt-in and composable: if more
    | than one is configured, passing any single one is enough to see the
    | full response. A caller that passes none of the configured methods
    | still receives the real HTTP status code, with no response body.
    |
    | Access control hides the response body. Configured checks still run
    | for every request, so the status code stays accurate. Prefer not to
    | enable outbound HTTP, cross-service, or composer-audit checks on an
    | otherwise public URL. IP allowlists use $request->ip() and require
    | a correct TrustProxies configuration.
    |
    */

    'access' => [

        // Skip the whole access gate in local development. Checked against
        // the application's own environment, never the request's IP.
        'bypass_when_local' => env('HEALTH_ROUTE_BYPASS_WHEN_LOCAL', false),

        // HTTP basic auth. Needed by monitoring tooling that can only
        // authenticate with a username/password, not custom headers.
        'basic_auth' => [
            'username' => env('HEALTH_ROUTE_BASIC_AUTH_USERNAME'),
            'password' => env('HEALTH_ROUTE_BASIC_AUTH_PASSWORD'),
        ],

        // A shared-secret header. Cheaper to rotate than basic-auth
        // credentials, and never appears in a URL.
        'token' => [
            'header' => env('HEALTH_ROUTE_TOKEN_HEADER', 'X-Health-Token'),
            'value' => env('HEALTH_ROUTE_TOKEN_VALUE'),
        ],

        // Static IP/CIDR allowlist (IPv4 and IPv6).
        'allowed_ips' => array_filter(explode(',', (string) env('HEALTH_ROUTE_ALLOWED_IPS', ''))),

        // Dynamic IP allowlist for callers behind a DDNS hostname. Resolved
        // via DNS and cached for the record's own TTL - see the README for
        // the accepted DNS-timeout limitation this implies.
        'allowed_hostnames' => array_filter(explode(',', (string) env('HEALTH_ROUTE_ALLOWED_HOSTNAMES', ''))),

        'dynamic_ip' => [
            // How long a failed/unresolvable DNS lookup is cached for, so a
            // DNS outage doesn't turn every request into a slow one.
            'negative_cache_seconds' => env('HEALTH_ROUTE_DYNAMIC_IP_NEGATIVE_CACHE_SECONDS', 30),
        ],

        // Replace or append to the bundled access gates (same contract as
        // Checks: each class implements AccessGate). An empty list falls
        // back to the bundled set.
        'gates' => [
            BasicAuthGate::class,
            SharedSecretHeaderGate::class,
            StaticIpAllowlistGate::class,
            DynamicIpAllowlistGate::class,
            LocalEnvironmentBypassGate::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Status header
    |--------------------------------------------------------------------------
    |
    | Optional middleware (see Http\Middleware\AddHealthStatusHeader) that
    | adds a lightweight status header to other routes' responses, reusing
    | the shared check-result cache above rather than re-running checks.
    |
    */

    'status_header' => [
        'enabled' => env('HEALTH_ROUTE_STATUS_HEADER_ENABLED', false),
        'name' => env('HEALTH_ROUTE_STATUS_HEADER_NAME', 'X-Health-Status'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Static liveness file
    |--------------------------------------------------------------------------
    |
    | The filename used when publishing the optional static liveness file
    | (php artisan vendor:publish --tag=health-route-static), served as a
    | plain "pong" response straight from the public directory - no
    | framework route, no config needed. Customise this if you like, but
    | don't set it to match the `path` above: that's the dynamic route,
    | this is a separate, cheaper static check. A published file at the
    | same public URI is served by the web server and bypasses Laravel
    | (and this package's access control). The package logs a critical
    | message at boot time if it detects the two collide - it does not
    | refuse to boot, since that would take the whole app down over a
    | static-file-publish-time misconfiguration, not just this route.
    |
    */

    'static_filename' => env('HEALTH_ROUTE_STATIC_FILENAME', 'ping'),

];
