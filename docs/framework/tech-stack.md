# Technical Stack

This file is the lightweight Dev-context stack summary for implementation work.
It is derived from `docs/architecture.md`, `docs/ui-architecture.md`,
`composer.json`, and `package.json`. Keep the source architecture documents as
the richer decision record.

## Runtime

| Area | Standard |
| --- | --- |
| Application framework | Laravel 13 |
| Runtime language | PHP 8.4 |
| Architecture shape | Brownfield Laravel monolith enhancement |
| Tenancy | SaaSykit `Tenant` and `tenant_user` membership |
| Authentication and authorization | Laravel auth, policies, gates, Sanctum where applicable |
| Admin and account UI | Filament 5 |
| Customer memory UI | Livewire 4, Blade, Alpine.js 3 |
| Styling | Tailwind CSS 4 and DaisyUI 5 |
| Asset build | Vite 7 and Laravel Vite Plugin 2 |
| Testing | PHPUnit 11, Laravel feature tests, Livewire tests |
| Static analysis and formatting | Larastan/PHPStan 3 and Laravel Pint 1 |
| Local runtime | Laravel Sail and Docker Compose |

## Core Packages

Use the existing SaaSykit and Laravel package set before adding dependencies:

- `laravel/framework` `^13.0`
- `livewire/livewire` `^4.0`
- `filament/filament` `^5.0`
- `laravel/sanctum` `^4.0`
- `laravel/horizon` `^5.21`
- `laravel/socialite` `^5.6`
- `spatie/laravel-permission` `^6.0`
- `filament/spatie-laravel-media-library-plugin` `^5.0`
- `laravel/sail` `^1.18`
- `larastan/larastan` `^3.0`
- `laravel/pint` `^1.0`
- `phpunit/phpunit` `^11.0`

Frontend packages:

- `tailwindcss` `^4.1.11`
- `@tailwindcss/vite` `^4.1.11`
- `daisyui` `^5.0.50`
- `alpinejs` `^3.13.3`
- `laravel-vite-plugin` `^2.0.0`
- `vite` `^7.1.2`

## Memory Product Direction

The memory product stays inside the existing Laravel application. Do not add a
SPA, a JSON API client layer, a new JavaScript state store, or a second frontend
runtime for MVP memory screens.

Reuse these existing primitives:

- `Tenant` as the workspace boundary.
- `tenant_user` as workspace membership.
- `User::canAccessTenant()` for tenant access compatibility.
- SaaSykit invitations, roles, permissions, seat limits, billing, and account
  surfaces.
- Spatie Media Library for media modeling, with private authorized delivery for
  memory photos.
- Laravel localization for UI strings only. User-generated content stays in the
  language entered by the user.

Customer-facing memory routes live under `/memories/{tenant:uuid}` and use
Livewire/Blade product UI. Filament remains for billing, account, workspace,
users, roles, teams, invitations, security, and admin/reporting surfaces.

## Quality Gates

Use the existing project scripts:

```bash
npm run lint
npm run typecheck
npm test
```

For focused Laravel tests, prefer:

```bash
php artisan test --compact path/to/Test.php
```

When Sail is required, follow the project scripts instead of inventing a second
local runtime.
