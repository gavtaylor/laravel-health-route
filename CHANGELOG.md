# Release Notes

## [Unreleased](https://github.com/gavtaylor/laravel-health-route/compare/v0.1.0...1.x)

- Log a critical message at boot time when `static_filename` matches the health route path (published static files bypass Laravel access control).
- Default published static liveness file is `public/ping` (`pong`).
- Keep serving check results when the cache store used by `CheckRunner` is down.
- Outbound HTTP probes no longer follow redirects; cross-service checks honour a remote `degraded` status.
- Named route `health-route`, optional extra middleware, and a configurable access-gate list.
- Document `@throws` on methods that can propagate an exception in debug mode, and require contributions to follow Laravel's own official coding standards (see `.github/CONTRIBUTING.md`).

## [v0.1.0](https://github.com/gavtaylor/laravel-health-route/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
