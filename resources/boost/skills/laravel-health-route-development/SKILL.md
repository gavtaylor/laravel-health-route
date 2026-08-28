---
name: laravel-health-route-development
description: >
  Configure and apply the Health Route for Laravel package in Laravel applications.
license: MIT
metadata:
  author: Gavin Taylor
---

# Health Route for Laravel

Use this skill when a Laravel application needs to integrate the Health Route for Laravel package.

## Primary Goal

- apply the `gavtaylor/laravel-health-route` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- check `bootstrap/app.php` for an existing `health: '...'` argument to `withRouting()` - remove it, this package replaces that route entirely at the same default path (`/up`)
- check whether `config/health-route.php` has been published (`php artisan vendor:publish --tag=health-route-config`) before assuming any non-default config

### 2. Apply the package's public API

- **Custom HTML**: publish the view (`--tag=health-route-views`) and edit `resources/views/vendor/health-route/default.blade.php` directly. It receives `$exception` (`Throwable|null`), `$down` (`bool`), `$checks` (`list<CheckResult>`). Never invent new variables here - treat that set as a fixed contract.
- **Checks**: add fully-qualified check class names to `checks` in `config/health-route.php`. Never enable a check the app doesn't actually need - each one is opt-in for a reason. Built-in checks live under `GavTaylor\HealthRoute\Checks\*`; a custom check implements `GavTaylor\HealthRoute\Checks\Contracts\Check` (`name(): string`, `run(): CheckResult`) and must never put raw exception messages, file paths, or stack traces into a `CheckResult` - the endpoint is public by default.
- **Access control**: configure under `access` in the config file. Multiple methods compose with OR - enabling more than one is a deliberate choice, not redundancy. Never suggest `bypass_when_local` as a way to skip access control anywhere other than local development.
- **Status header on other routes**: attach the `health-status` middleware alias; it only works if `status_header.enabled` is `true` in config.
- **Static liveness file**: `php artisan vendor:publish --tag=health-route-static` - a one-time, manual step. Never suggest generating this file at runtime or on every deploy without `--force` if the app may have customised it.

### 3. Verify

- Hit the configured path with and without `Accept: application/json` and confirm the response shape matches what was configured (checks array present only if checks are configured).
- If checks were added, confirm a forced failure (e.g. temporarily breaking the dependency) produces a `degraded` or `down` result as intended, and that the message contains no leaked exception detail.

## Rules, References, and Templates

Read before executing:

- `README.md` in the package root - full config reference and access-control behaviour
- `config/health-route.php` (published copy, if present) - inline documentation for every option

## Examples

- Adding a database check with basic-auth protection: add `DatabaseConnectionCheck::class` to `checks`, set `access.basic_auth.username`/`password` (typically via env vars), and confirm `php artisan route:list` shows one `GET /up` route.
- Customising the HTML page to match brand styling: publish the view, replace the markup while keeping the same three variables, and verify both the healthy and (via a temporary throwing listener) unhealthy states still render.

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not re-implement the native `health:` routing argument alongside this package - pick one
- do not put exception detail, stack traces, or file paths into a check's message or context
