# Coding Standards

This file is the lightweight Dev-context implementation rule set. It is derived
from `docs/architecture.md`, `docs/ui-architecture.md`, and the workspace
Laravel Boost guidelines. Keep the source architecture documents as the richer
decision record.

## Laravel And PHP

- Follow existing Laravel and SaaSykit conventions before introducing new
  abstractions.
- Use explicit parameter and return types for PHP methods and functions.
- Use PHP 8 constructor property promotion for injected dependencies.
- Always use braces for control structures.
- Prefer Eloquent models and relationships over raw queries. Avoid `DB::`
  unless a query is genuinely complex and a simpler ORM expression is not
  appropriate.
- Use named routes and `route()` for links.
- Use policies, gates, middleware, and service checks for authorization.
- Use Form Request classes for controller validation. Livewire actions should
  validate and authorize server-side as they would in HTTP requests.
- Use `config()` outside configuration files. Do not call `env()` directly in
  application code.
- Queue long-running work with jobs that implement `ShouldQueue`.
- Run `npm run lint` as the repository-level formatting gate after PHP
  changes. If direct focused Pint execution is required, use
  `vendor/bin/sail pint --dirty --format=agent`.

## Memory Product Rules

These rules apply to customer-facing memory work:

1. Use the dedicated memory layout for memory routes.
   `resources/views/components/layouts/memory.blade.php` is the customer-facing
   memory shell. Timeline, editor, detail, profile setup, and filter screens
   must not render inside the public marketing layout or Filament Dashboard
   chrome.

2. Keep Filament out of customer memory workflows.
   Filament remains for billing, account, workspace settings, users, roles,
   teams, invitations, security, and admin/reporting surfaces. Customer memory
   UI is Livewire and Blade.

3. Enforce tenant boundaries before rendering private content.
   Routes, middleware, policies, services, and queries must all scope records,
   tags, highlights, and media to the current tenant. Blade templates must not
   decide whether private records are accessible.

4. Re-scope nested route-bound models to the route tenant.
   A bound record, media item, tag, or highlight ID is not safe until the server
   confirms it belongs to the route tenant and the current user is authorized.

5. Never expose private media through public paths.
   Memory photos must use authorized routes or an equivalent private access
   mechanism. Do not use public blog-media assumptions, `public/storage`, raw
   file paths, Livewire public properties, JavaScript, or logs for private paths.

6. Keep sensitive or free-text filters out of URLs by default.
   Date and tag filters may be URL-backed when useful. Location search and
   private record content should stay as transient Livewire state unless a story
   explicitly accepts the privacy tradeoff.

7. Treat Livewire public properties as request-visible state.
   Do not put secrets, full private media paths, authorization decisions,
   filenames, or server-only access metadata in public properties.

8. Use services for domain transitions.
   Livewire components can own screen state and actions. Record creation,
   updates, highlights, timeline queries, profile completion, locale changes,
   and activity events should go through memory services.

9. Use Laravel localization for customer-facing memory UI strings.
   Do not translate user-generated content.

10. Preserve the timeline-first product model.
    The timeline home is the first meaningful memory screen after profile setup.
    It remains vertical, month-grouped, and ordered by experience date or period
    start date. Do not implement masonry, maps, complex animation, or table/admin
    framing for MVP.

11. Keep record editors writing-first.
    Diary body and experience date lead diary flows. Trip/period title,
    location, and dates lead period flows. Optional metadata must remain
    secondary.

12. Avoid album, admin-table, and marketing-page framing.
    Use timeline, record, memory, highlight, family workspace, and preservation
    language. Do not use visible album-generation copy in MVP screens.

## Component Boundaries

- Screen-level Livewire components own request-visible UI state such as filters,
  form input, save state, and upload state.
- Services own persistence, timeline query construction, shared editing rules,
  activity recording, and profile completion checks.
- Middleware and policies own the primary access boundary for tenants, records,
  and media. Livewire checks are additional protection, not a substitute.
- Blade components are presentational and must not query tenant-private data.
- Private media Blade components may render authorized URLs or protected states
  passed from controllers, services, or Livewire components. They must not
  compute public storage paths.
- The memory layout shell owns navigation, workspace context, profile/language
  controls, and dashboard escape links.
- Components that render lists must use stable `wire:key` values.
- Long-running or privacy-sensitive actions must validate and authorize inside
  the Livewire action, even if middleware already ran.
- UI copy must use translation keys before story closure.

## Tailwind And UI

- Use Tailwind CSS 4 utilities and existing Blade conventions.
- Use DaisyUI selectively and style it down for memory-product UI.
- Do not let the memory product inherit the public marketing hero, public blog
  card treatment, dominant purple palette, or table-first admin surfaces.
- Prefer neutral surfaces, clear text rhythm, restrained teal/blue-gray accents,
  subtle borders, and simple card states.
- Design controls for English, Portuguese, and Spanish label expansion.
- Let tags, locations, and action labels wrap instead of overlapping.
- Keep text readable and preserve line length on wide screens.

## Tests

- Write PHPUnit tests. Do not introduce Pest tests.
- Use Laravel feature tests, policy tests, service tests, and Livewire tests for
  memory routes and screens.
- Use factories and existing model states where available.
- Run the smallest relevant test set first, for example:

```bash
php artisan test --compact tests/Feature/Memory/MemoryRouteAccessTest.php
```

For full local gates, use:

```bash
npm run lint
npm run typecheck
npm test
```
