# Release Notes

## [Unreleased](https://github.com/gavtaylor/laravel-health-route/compare/v0.1.0...main)

- New bundled checks: `StorageWritableCheck`, `LogWritableCheck`, `EnvironmentCheck`.
- The default HTML view shows `-` for a check with no message, instead of an empty cell (JSON keeps `null`).

## [v0.1.0](https://github.com/gavtaylor/laravel-health-route/releases/tag/v0.1.0) - 2026-08-28

Initial release. A drop-in replacement for Laravel's built-in health route (`health:` in `withRouting()`), with a customisable HTML view, a richer JSON contract, opt-in structured checks, and composable access control.

- Same `/up` default path, `DiagnosingHealth` event, and `{"status": "up"|"down"}` JSON contract as Laravel core - remove the package and add `health:` back to `withRouting()`, and a status-only client sees no change.
- Customisable HTML view (`vendor:publish --tag=health-route-views`), zero config change required.
- Named checks reporting `up`, `degraded`, or `down` - a `degraded` check never fails the HTTP response. Nine bundled checks (database, cache, Redis, disk space, outbound HTTP, pending migrations, scheduler liveness, dependency advisories, cross-service), all opt-in.
- Five composable access-control methods (basic auth, shared-secret header, static IP/CIDR allowlist, dynamic DDNS hostname allowlist, local-environment bypass) plus a configurable/replaceable gate list for custom gates - public by default, matching core.
- Optional static liveness file (`public/ping` → `pong`), never a registered framework route.
- Optional status-header middleware for other routes, sharing the same check-result cache as the main endpoint.
- Named route (`route('health-route')`) and boot-time warnings for a colliding `health:` route or a colliding static filename.
