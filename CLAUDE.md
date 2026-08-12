# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

meterDash is a Laravel 13 + Inertia.js + Vue 3 application (bootstrapped from `laravel/blank-vue-starter-kit`). It's early-stage: routing, models, and controllers for its core feature (wattage/energy metering — see `WattageEntry`/`WattageController`) are stubs waiting to be built out.

## Commands

Run everything through `composer` scripts rather than calling the underlying tools directly — they match what CI runs.

- `composer dev` — start the local dev environment (`php artisan dev`, runs server + queue + Vite together).
- `npm run dev` / `npm run build` — Vite dev server / production build (only needed standalone; `composer dev` covers local dev).
- `composer lint` / `composer lint:check` — Pint formatting (fix / check-only).
- `npm run lint` / `npm run lint:check` — ESLint (fix / check-only).
- `npm run format` / `npm run format:check` — Prettier for `resources/`.
- `npm run types:check` — `vue-tsc --noEmit`.
- `composer types:check` — PHPStan (Larastan) at level 9, scoped to `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`.
- `composer test` — clears config cache, then runs lint:check, types:check, and the full Pest suite.
- `composer ci:check` — the full CI gate: `npm run lint:check`, `npm run format:check`, `npm run types:check`, then `composer test`. Run this (or the individual pieces) before considering a change done.
- Single test: `php artisan test --compact --filter=testName`, or `php artisan test --compact tests/Feature/SomeTest.php`.
- New Pest test: `php artisan test --pest SomeFeatureTest` (name excludes the `Feature`/`Unit` directory prefix; feature tests are the default, pass `--unit` for unit tests).
- After editing any PHP file: `vendor/bin/pint --dirty --format agent` to auto-fix style before finishing.

## Architecture

- **Inertia SPA, no separate API/JSON layer.** Server-side routes render Inertia pages directly (`Route::inertia(...)` or `Inertia::render()` from controllers); there's no REST/JSON API to design around. Vue components living under `resources/js/pages/` are the routable pages.
- **Wayfinder generates the JS/TS routing glue.** Do not hand-write fetch calls or route strings in the frontend. Named routes and controller actions are exposed as generated TypeScript functions in `resources/js/routes/` and `resources/js/actions/`, imported via the `@/actions/` and `@/routes/` aliases. These are regenerated from PHP routes/controllers — if a route or controller signature changes, the corresponding generated file needs regenerating (the Vite `wayfinder` plugin does this automatically while `composer dev`/`npm run dev` is running).
- **Shared Inertia props** are defined once in `app/Http/Middleware/HandleInertiaRequests.php::share()` (currently `name` and `auth.user`) — extend this rather than passing the same prop from every controller.
- **`AppServiceProvider::configureDefaults()`** sets app-wide defaults worth knowing about: dates are `CarbonImmutable`, destructive DB commands (`DB::prohibitDestructiveCommands`) are blocked in production, and password rules only tighten (12+ chars, mixed case, symbols, uncompromised check) in production.
- **Database is SQLite** locally (`database/database.sqlite`), driven by `DB_CONNECTION` in `.env`.
- **Styling** is Tailwind CSS v4 via `@tailwindcss/vite` (no separate Tailwind config file — see `resources/css/app.css`), with `prettier-plugin-tailwindcss` sorting classes and `clsx`/`tailwind-merge` (`cn` helper convention) for conditional class composition.
- **Charts** use `chart.js` via `vue-chartjs` — the natural fit for rendering wattage/metering data once that feature is built out.

## Repo-specific rules (from Laravel Boost / AGENTS.md)

These are enforced conventions, not suggestions — see `AGENTS.md` for the full text:

- Stick to the existing directory structure; don't add new top-level folders without approval, and don't change dependencies without approval.
- Prefer `php artisan make:*` generators over hand-created files for models, controllers, migrations, etc.
- Always use single-root-element Vue components (Inertia + Vue convention).
- Use PHP 8 constructor property promotion, explicit return types/param types everywhere, curly braces on all control structures, and PHPDoc array-shape blocks over inline comments.
- Every PHP file must start with `declare(strict_types=1);` and follow the most up-to-date PHP 8.3+ coding standards (typed properties, enums, readonly where appropriate, etc.) — not older PHP idioms.
- Don't delete tests without approval; don't create ad hoc verification/tinker scripts when a Pest test already proves the behavior.
- This project uses [Laravel Boost](https://github.com/laravel/boost) MCP tooling (`search-docs`, `database-query`, `database-schema`, `browser-logs`, etc.) — prefer those tools over raw shell/DB access when available, and check `search-docs` before making framework-related changes since APIs are version-pinned (PHP 8.3+/Laravel 13, Inertia v3).
