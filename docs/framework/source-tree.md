# Source Tree

This file is the lightweight Dev-context source tree summary. It is derived
from `docs/architecture.md` and `docs/ui-architecture.md`. Keep the source
architecture documents as the richer decision record.

## Governance

`docs/framework/*.md` files are stable implementation summaries for agents.
When architecture changes, update the richer source document first
(`docs/architecture.md` or `docs/ui-architecture.md`) and then update these
framework shards so Dev context stays current.

Do not create new base directories for feature work without explicit
architecture approval. Prefer the existing Laravel/SaaSykit structure.

## Existing Application Areas

| Area | Path | Ownership |
| --- | --- | --- |
| Admin panel | `app/Filament/Admin` | Filament admin resources and pages |
| Dashboard panel | `app/Filament/Dashboard` | Billing, account, workspace, users, roles, teams, invitations |
| Models | `app/Models` | Eloquent domain models |
| Services | `app/Services` | Stateless business logic services |
| Policies | `app/Policies` | Authorization boundaries |
| Middleware | `app/Http/Middleware` | Request access and context checks |
| Livewire classes | `app/Livewire` | Server-driven UI components |
| Blade views | `resources/views` | Layouts, pages, components, Livewire views |
| Routes | `routes/web.php` | Web route declarations |
| Migrations | `database/migrations` | Schema changes |
| Factories | `database/factories` | Test data setup |
| Seeders | `database/seeders` | Development and baseline data |
| Tests | `tests` | PHPUnit test suites |

## Memory Product Locations

| Area | Path |
| --- | --- |
| Memory routes | `routes/web.php` |
| Tenant membership middleware | `app/Http/Middleware/EnsureTenantMember.php` |
| Profile gate middleware | `app/Http/Middleware/EnsureMemoryProfileComplete.php` |
| Memory media controller | `app/Http/Controllers/MemoryMediaController.php` |
| Memory layout | `resources/views/components/layouts/memory.blade.php` |
| Memory Livewire classes | `app/Livewire/Memory` |
| Memory Livewire views | `resources/views/livewire/memory` |
| Shared memory Blade fragments | `resources/views/components/memory` |
| Memory services | `app/Services` with `Memory*Service` names |
| Memory policies | `app/Policies` |
| Memory translations | `lang/en/memory.php`, `lang/pt/memory.php`, `lang/es/memory.php` |
| Memory route tests | `tests/Feature/Memory` |
| Memory Livewire tests | `tests/Feature/Livewire/Memory` |
| Memory service tests | `tests/Feature/Services/Memory` |

## Route Shape

Customer-facing memory routes use `/memories/{tenant:uuid}` and Laravel named
routes under the `memories.` name prefix. The route group should include
`auth`, `verified`, and tenant membership checks.

Expected MVP route responsibilities:

- `/profile/setup` for memory profile setup.
- `/timeline` for the timeline home.
- `/records/create/{type}` for record creation.
- `/records/{record:uuid}` for record detail.
- `/records/{record:uuid}/edit` for record editing.
- `/media/{media:uuid}` for authorized private media delivery.

The route group may use `EnsureTenantMember` to set current tenant context for
product Livewire components without rebuilding SaaSykit tenancy.

## File Placement Rules

- Keep account, billing, subscriptions, users, teams, roles, invitations,
  security, and admin reporting in Filament surfaces.
- Keep timeline, profile setup, record editor, record detail, filters, and
  customer-facing private media states in Livewire/Blade memory UI.
- Keep tenant and record authorization in middleware, policies, services, and
  scoped Eloquent queries before private content reaches a view.
- Put reusable memory domain logic in services instead of Livewire views.
- Put presentational fragments in Blade components; they must not query private
  tenant data directly.
- Put customer-facing UI strings in language files, not hard-coded Blade text.
- Put tests next to their behavioral scope: route access in
  `tests/Feature/Memory`, Livewire behavior in `tests/Feature/Livewire/Memory`,
  and service behavior in `tests/Feature/Services/Memory`.

## Common Commands

```bash
php artisan make:livewire Memory/MemoryTimeline --class --no-interaction
php artisan make:livewire Memory/MemoryRecordEditor --class --no-interaction
php artisan make:livewire Memory/MemoryRecordDetail --class --no-interaction
php artisan make:test Livewire/Memory/MemoryTimelineTest --phpunit --no-interaction
php artisan make:test Memory/MemoryRouteAccessTest --phpunit --no-interaction
php artisan test --compact tests/Feature/Memory/MemoryRouteAccessTest.php
npm run lint
npm run typecheck
npm test
```
