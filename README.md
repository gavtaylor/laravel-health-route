# Health Route for Laravel

A drop-in replacement for Laravel's built-in health route, with a customisable HTML view, a richer JSON contract, opt-in structured checks, and composable access control.

Laravel core ships a built-in health check route (`health:` in `withRouting()`): served at a configurable URI (default `/up`), it dispatches `Illuminate\Foundation\Events\DiagnosingHealth`, and returns `200`/`500` with `{"status": "up"|"down"}` for JSON clients. Its HTML page is deliberately not customisable.

This package **is that same route** — same event, same failure contract, same default path — with a customisable HTML view and a library of opt-in checks layered on top. Remove the package and add `health: '/up'` back to `withRouting()`, and any client that only reads `status` sees no change in behaviour.

## Installation

```bash
composer require gavtaylor/laravel-health-route
```

The package auto-registers itself and serves `/up` immediately - no other setup is required. If your app still passes `health: '/up'` to `withRouting()` in `bootstrap/app.php`, remove it; this package logs a boot-time warning if it detects another route already registered at the same path.

## The HTML view

Override the default view with zero config change:

```bash
php artisan vendor:publish --tag=health-route-views
```

This copies the view into `resources/views/vendor/health-route/default.blade.php`, where Laravel's own view resolution already looks before falling back to the package's copy. Edit it directly - nothing else needs to change.

The view receives:

| Variable    | Type                    | Description                                                    |
|-------------|-------------------------|------------------------------------------------------------------|
| `$exception` | `Throwable\|null`       | Set when a `DiagnosingHealth` listener threw (core's own failure mode) |
| `$down`      | `bool`                  | Whether the overall response is reporting "down"                 |
| `$checks`    | `list<CheckResult>`     | Every configured check's result (empty if none are configured)   |

This is a public contract: once you've customised the view, treat changes to these variables as breaking changes.

## Configuration

```bash
php artisan vendor:publish --tag=health-route-config
```

See the generated `config/health-route.php` for every option, documented inline. Highlights below.

## Checks

Register named checks that each independently report `up`, `degraded`, or `down`, with an optional message and structured context:

```php
// config/health-route.php
'checks' => [
    \GavTaylor\HealthRoute\Checks\DatabaseConnectionCheck::class,
    \GavTaylor\HealthRoute\Checks\CacheReadWriteCheck::class,
],
```

**A `degraded` check never fails the HTTP response** - only a `down` check does, using the configured `problem_status_code` (default `503`). This is the point of checks beyond a single up/down boolean: surface a real problem without paging on-call for something that isn't urgent.

The JSON payload gains a `checks` array only when at least one check is configured, so the default response stays byte-for-byte identical to core:

```json
{
    "status": "down",
    "checks": [
        {"name": "database", "status": "down", "message": "Could not connect to the database."}
    ]
}
```

No check leaks exception detail (messages, file paths, stack traces) into the response - the endpoint is public by default, and every response body should be treated as something an unauthenticated caller can read. Full detail always goes to your logger via `report()`, never to the HTTP response.

### Bundled checks

All opt-in, none run unless listed in `checks` above:

- `DatabaseConnectionCheck` - runs a trivial query against a database connection
- `CacheReadWriteCheck` - writes then reads back a probe value from a cache store
- `RedisCheck` - pings a Redis connection
- `DiskSpaceCheck` - free disk space against configurable degraded/down thresholds
- `OutboundHttpCheck` - probes a configured URL
- `PendingMigrationsCheck` - degrades when migrations haven't been run
- `SchedulerLivenessCheck` - degrades/downs based on a heartbeat timestamp (see below)
- `DependencyAdvisoryCheck` - wraps `composer audit`, cached far longer than other checks since it's expensive
- `CrossServiceCheck` - probes another service's own health-style endpoint

Each check's tunables (connection names, thresholds, URLs) live under `checks_config` in the config file.

#### Scheduler heartbeat

`SchedulerLivenessCheck` needs something to write a heartbeat. Set `checks_config.scheduler.register_heartbeat` to `true` and this package registers an `everyMinute()` scheduled task itself - or write to the same cache key yourself (`SchedulerLivenessCheck::HEARTBEAT_CACHE_KEY`) from your own scheduled command if you'd rather not use the bundled one. No scheduled job is *required* for any other feature in this package.

## Access control

The endpoint is public by default, matching core. Each method below is independently configurable and composable - if more than one is enabled, passing **any single one** is enough to see the full response:

```php
// config/health-route.php
'access' => [
    'bypass_when_local' => false,
    'basic_auth' => ['username' => env('HEALTH_ROUTE_BASIC_AUTH_USERNAME'), 'password' => env('HEALTH_ROUTE_BASIC_AUTH_PASSWORD')],
    'token' => ['header' => 'X-Health-Token', 'value' => env('HEALTH_ROUTE_TOKEN_VALUE')],
    'allowed_ips' => ['10.0.0.0/8', '203.0.113.5'],       // IPv4/IPv6, CIDR supported
    'allowed_hostnames' => ['monitor.example.ddns.net'],   // for callers on a dynamic IP
],
```

- **Local-development bypass** - explicit, off by default, checked against `app()->environment('local')` only, never the request's IP (which can be spoofed or misreported by a misconfigured proxy).
- **Basic auth** - for monitoring tooling that can only authenticate with a username/password.
- **Shared-secret header** - cheaper to rotate than credentials, never appears in a URL.
- **Static IP/CIDR allowlist** - for infrastructure with fixed addresses.
- **Dynamic hostname allowlist** - for a caller behind a DDNS record. Resolved via DNS and cached for the record's own TTL, so a lookup only happens once per TTL window. A failed lookup is cached briefly too, so a DNS outage doesn't turn every request into a slow one - PHP has no reliable built-in DNS timeout, so this is an accepted limitation: at most one unlucky request per outage pays the cost of a slow lookup.

All credential/token comparisons are timing-safe. An unconfigured method never accidentally authenticates - empty config never matches empty or absent credentials, and an empty allowlist never matches any IP.

A caller that fails every configured method still receives the real HTTP status code, with no response body - an unauthenticated monitor can learn "up or down," nothing more.

## Status header for other routes

Attach a lightweight check-status header to any other route:

```php
Route::middleware('health-status')->group(function () {
    // ...
});
```

Enable it via `status_header.enabled` in the config file. It reads through the same short-lived cache the main endpoint uses (`checks_cache_seconds`), so attaching it to many routes doesn't force the full check suite to re-run on every request.

## Static liveness file

For a cheaper first-line check than booting the framework at all:

```bash
php artisan vendor:publish --tag=health-route-static
```

This publishes a static `public/ping` file - hit `/ping` and the web server replies `pong` directly, without booting Laravel at all. Never a registered framework route, and never written automatically at request or boot time. Re-running the publish command never overwrites a file you've already customised (standard `vendor:publish` behaviour) - use `--force` if you want to reset it.

Customise the filename via `static_filename` in the config file if you like, but don't set it to match `path` (the dynamic route) - most web servers serve an existing static file directly and would silently bypass the dynamic route entirely. The package logs a boot-time warning if it detects that.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Issues and pull requests are welcome.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Gavin Taylor](https://github.com/gavtaylor)
- [All Contributors](../../contributors)

This package's design is informed by prior art: an MIT-licensed Laravel health-check package the author previously contributed to. The implementation here is entirely new, built from a fresh set of functional requirements rather than ported from that or any other package.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
