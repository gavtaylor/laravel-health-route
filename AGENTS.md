# Health Route Extension for Laravel

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `gavtaylor/laravel-health-route`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Coding Standards

Code changes must follow the coding standards in [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md) - this package holds itself to Laravel's own official coding standards, not a generic style. Read that file rather than restating its rules here; it's the single source of truth for style, PHPDoc, and the `composer test` bar.

## Documentation

Each topic has exactly one canonical file. When a change touches guidance that already exists elsewhere, update the canonical file and link to it - don't paste a second copy:

- Coding standards, style, PHPDoc conventions → [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md)
- Package usage, configuration, feature behaviour → [`README.md`](README.md)
- Security reporting → [`.github/SECURITY.md`](.github/SECURITY.md)
- AI-agent integration steps for consuming apps → [`resources/boost/skills/laravel-health-route-development/SKILL.md`](resources/boost/skills/laravel-health-route-development/SKILL.md) (this one is necessarily somewhat self-contained, since it's loaded standalone by tooling - keep it a *summary* pointing at README/CONTRIBUTING for depth, not a rewrite of them)

Before adding a new paragraph of guidance, grep the other docs for the same idea first. If it already exists, link to it instead of restating it.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4/5 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation, README, and examples.
