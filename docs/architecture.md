# Life Memory Timeline for Missionaries Brownfield Architecture

## Status

Validated for PM handoff. This document is based on `docs/prd.md`,
`docs/brief.md`, the architecture checklist, and a direct review of the current
Laravel/SaaSykit Tenancy codebase and UI structure.

## Scope

This architecture covers the MVP memory timeline enhancement for the existing
SaaSykit Tenancy application. The first validation niche is missionaries and
their families, but the implementation must use neutral memory-domain names so
the product can expand beyond missions.

The MVP must not implement per-record missionary-only versus family-visible
access. Access is private to the tenant/workspace boundary.

## Existing project analysis

The existing application is a Laravel monolith using SaaSykit Tenancy,
Filament Dashboard/Admin panels, Livewire, Blade, Tailwind CSS, Spatie
Permission, and Spatie Media Library.

The current project already provides these capabilities that the MVP should
reuse:

- `Tenant` as the workspace boundary.
- `tenant_user` as the workspace membership pivot.
- `User::canAccessTenant()` for Filament-compatible tenant access.
- `Invitation` plus `InvitationResource` for owner-created workspace invites.
- `TenantService::acceptInvitation()` and `TenantService::canInviteUsers()`.
- Tenant roles and permissions through `TenantPermissionService`.
- Plan-level workspace limits through `plans.max_users_per_tenant`.
- Seat-aware pricing fields through `plan_prices.included_seats` and
  `plan_prices.extra_seat_price`.
- The Filament Dashboard panel for billing, tenant settings, users, roles,
  teams, and invitations.
- Spatie Media Library, currently used for public blog images.

The existing application does not yet provide these MVP-specific capabilities:

- A memory-domain profile setup gate.
- Tenant-scoped memory records.
- Timeline ordering by experience date or period.
- Memory tags, highlights, and optional email-source tracking.
- Private memory-photo delivery through authorization checks.
- English, Portuguese, and Spanish locale selection.
- Workspace-level collaboration mode settings.
- Admin-only product validation reporting by tenant/workspace.

## Architecture decision summary

1. Build the MVP as a Laravel monolith enhancement inside the existing
   repository.
2. Reuse SaaSykit `Tenant` as the family workspace. Do not introduce a
   separate workspace model.
3. Reuse SaaSykit invitations for the owner-to-collaborator flow. Configure
   the first validation tier with `max_users_per_tenant = 2`.
4. Keep billing, workspace settings, and invitations in the existing Filament
   Dashboard.
5. Build the memory timeline as customer-facing Livewire and Blade screens
   under the tenant-scoped `/memories/{tenant:uuid}` product route, not as
   Filament table resources.
6. Use neutral `Memory*` model and service names instead of `Missionary*`
   names.
7. Store all memory records with `tenant_id`; every query, policy, and media
   route must enforce tenant membership.
8. Use Spatie Media Library for photos, but store memory photos on a private
   disk and serve them only through an authorized route.
9. Use Laravel localization for UI strings and user navigation only.
   User-generated content must stay in the language entered by the user.
10. Start with shared editing: owner and collaborator can both create and edit
    records inside the workspace.
11. Preserve a workspace-level collaboration mode setting so later plans can
    offer selectable modes without changing the record model.
12. Validate media on Hostinger with local private storage, but keep the media
    disk S3-compatible so AWS S3 or Cloudflare R2 can be adopted later.
13. Keep validation reporting lightweight, admin-only, and query-driven, with
    a small product activity table only where derived data is not enough.

## Tenant and access architecture

`Tenant` is the workspace boundary for the MVP. A tenant contains one owner and
one collaborator for the first validation tier.

The owner is the tenant creator or a tenant user with the existing admin role.
The collaborator is an invited tenant member with the existing user role.
Both users can access, create, and edit memory records inside the tenant. The
MVP does not distinguish records that are visible only to the missionary from
records that are visible to family members.

Implementation rules:

- Resolve the current tenant from the route or from the existing dashboard
  tenant context.
- Reject access when `auth()->user()->canAccessTenant($tenant)` returns false.
- Require `tenant_id` on every memory-domain table that belongs to a workspace.
- Use policy checks for list, view, create, update, delete, and media access.
- Do not expose per-record visibility fields in migrations, forms, timeline
  cards, detail views, or stories for the MVP.
- Authorize create and update through policies/services even while both MVP
  roles can edit. This keeps the implementation ready for later collaboration
  modes.

The existing invitation system should be used as-is unless implementation finds
a specific gap. The required MVP behavior is already covered by pending invite
validation, existing-user validation, pending-invite validation, expiration,
email notification, role assignment, and plan-based member limits.

## Collaboration mode architecture

The MVP collaboration mode is `shared_editing`: the owner and collaborator can
both create and edit records inside the same private workspace. Do not hard-code
this assumption directly into Livewire components or forms.

Add a small workspace settings model owned by the tenant. This keeps the current
behavior simple while preserving a clean path for later selectable modes such
as owner review, read-only collaborator access, or custom role-based access.

Initial modes:

- `shared_editing`: MVP default. Owner and collaborator can create and edit
  workspace records.
- `owner_review`: future mode where collaborator-created or edited records may
  require owner approval.
- `read_only_collaborator`: future mode where collaborators can view records but
  cannot create or edit them.
- `custom_roles`: future mode that can map SaaSykit tenant roles and
  permissions to memory actions.

MVP implementation should store only the default mode and enforce it through
`MemoryRecordPolicy` and `MemoryWorkspaceSettingsService`. Do not expose a mode
selector in the MVP UI unless a story explicitly adds it.

## Product routing and UI architecture

The memory product should use `/memories/{tenant:uuid}` as the initial
customer-facing route group while keeping SaaSykit account operations in the
Filament Dashboard.

Recommended route shape:

```php
Route::middleware(['auth', 'verified'])
    ->prefix('/memories/{tenant:uuid}')
    ->name('memories.')
    ->group(function () {
        Route::get('/profile/setup', MemoryProfileSetup::class)
            ->name('profile.setup');
        Route::get('/timeline', MemoryTimeline::class)
            ->name('timeline');
        Route::get('/records/create/{type}', MemoryRecordEditor::class)
            ->name('records.create');
        Route::get('/records/{record:uuid}', MemoryRecordDetail::class)
            ->name('records.show');
        Route::get('/records/{record:uuid}/edit', MemoryRecordEditor::class)
            ->name('records.edit');
        Route::get('/media/{media:uuid}', MemoryMediaController::class)
            ->name('media.show');
    });
```

The route group can use a small middleware such as `EnsureTenantMember` to set
the current tenant context for product Livewire components. This avoids
rebuilding SaaSykit tenancy while giving the timeline a memory-first layout
instead of a dashboard table layout.

The MVP timeline layout is a vertical month-grouped list. The
`MemoryTimelineService` should return records ordered by experience date or
period start so the Livewire component can group them by month for display.
Month labels are the only MVP grouping layer. Do not implement masonry, map
browsing, complex timeline animation, or advanced grouping in the MVP.

Keep these Filament Dashboard areas available for account operations:

- Billing and subscriptions.
- Workspace settings.
- Users, roles, teams, and invitations.
- Security settings and profile management.

## Existing UI analysis and product UI direction

The existing SaaSykit UI gives the MVP a strong technical base, but the memory
product should not visually inherit the public marketing experience unchanged.

Current UI surfaces:

- Public marketing pages use Blade components, a large hero treatment, strong
  purple `primary` color usage, rounded buttons, shadows, feature imagery, and
  landing-page sections.
- Authentication and onboarding screens use a split `focus` layout with a form
  area and a branded side panel. This can support the lightweight memory profile
  setup with restrained copy and styling.
- The Filament Dashboard is tenant-aware and should remain the home for account,
  billing, workspace settings, users, roles, teams, and invitations.
- Existing Livewire flows such as roadmap and checkout provide useful patterns
  for forms, tabs, loading states, and submit handling.

Reuse for the memory product:

- Tailwind CSS, DaisyUI, Blade, Livewire, Alpine.js, and existing Vite bundling.
- Existing authentication layouts and form conventions as a starting point for
  profile setup and account-adjacent flows.
- Filament Dashboard for account and workspace operations only.
- Existing `__()` translation usage and Laravel localization conventions.
- Existing button and input primitives when they can be styled down for product
  use.

Do not reuse directly for the memory product:

- The public marketing home, hero composition, and feature-section style.
- Blog cards as timeline cards, because blog media is public-oriented and the
  visual treatment is promotional rather than private-memory oriented.
- Filament Resources or table-first CRUD screens for the customer-facing
  timeline, record editor, or record detail experience.
- The dominant purple landing-page palette as the main memory-product visual
  language.
- Heavy shadows, hover scaling, and rounded-pill buttons as default treatment
  for timeline and writing workflows.

MVP UI architecture rules:

- Add a dedicated memory product layout, for example
  `resources/views/components/layouts/memory.blade.php`.
- Keep this layout authenticated, tenant-aware, compact, and product-first. It
  should not include the public marketing footer or a hero section.
- Provide a small top bar with workspace context, product navigation, profile or
  language access, and an account/dashboard escape hatch.
- Build timeline, record editor, and record detail views as custom Livewire and
  Blade UI under `resources/views/livewire/memory`.
- Keep the timeline vertical and grouped by month, with simple cards and no
  masonry, map browsing, complex animation, or advanced grouping.
- Use a calmer memory-specific visual treatment: more neutral surfaces, clearer
  text rhythm, restrained accent color, subtle borders, and simple card states.
- Keep the interface writing-first. Structure should support date, location,
  tags, photos, and Memory Highlights without making the form feel like metadata
  administration.
- Keep all media display paths compatible with private media authorization; do
  not rely on public media URLs or public blog image patterns.
- Use UI copy around timeline, records, memories, highlights, family workspace,
  and preservation. Do not use visible album-generation language in MVP screens.

PM and UX implications:

- PM should treat a dedicated memory product layout as part of MVP scope, not as
  optional polish.
- UX should design the authenticated memory surface separately from the public
  SaaS landing page and separately from Filament admin tables.
- Stories for timeline, editor, detail, filters, profile setup, localization,
  and media should include UI acceptance criteria that prevent fallback to
  admin/table patterns.
- If scope slips, visual polish can be reduced, but the product must still avoid
  opening as a table, dashboard list, or marketing page.

## Data model

### `memory_profiles`

Stores MVP profile completion for product onboarding.

- `id`
- `user_id`
- `display_name`
- `preferred_locale`
- `mission_context`
- `completed_at`
- timestamps

Use this model for the profile setup gate. Keep mission context optional.

`preferred_locale` should default to `en` and support `en`, `pt`, and `es`.
Users should be able to select the locale during registration/profile setup and
change it later in their profile.

### `memory_workspace_settings`

Stores memory-product settings for a tenant/workspace without changing the
SaaSykit tenant model.

- `id`
- `tenant_id`
- `collaboration_mode`: default `shared_editing`
- timestamps

Add a unique index on `tenant_id`.

### `memory_records`

Stores diary records and trip/period records on the same tenant timeline.

- `id`
- `uuid`
- `tenant_id`
- `author_user_id`
- `last_edited_by_user_id`
- `type`: `diary` or `period`
- `title`
- `body`
- `notes`
- `experience_date`
- `period_start_date`
- `period_end_date`
- `location_name`
- `people`
- `source`: nullable, `manual` or `mission_email_copy` if the non-blocking
  email-source marker is included
- `source_metadata`: nullable, only for non-sensitive metadata if the
  non-blocking email-source marker is included
- timestamps

Ordering rules:

- Diary records order by `experience_date`.
- Trip/period records order by `period_start_date`.
- `created_at` must not control primary timeline order.

Validation rules:

- Diary records require `body` and `experience_date`.
- Period records require `title`, `period_start_date`, and `period_end_date`.
- `period_end_date` must be on or after `period_start_date`.

### `memory_tags` and `memory_record_tag`

Use tenant-scoped tags instead of a JSON-only tag list so filtering stays
simple and efficient.

`memory_tags`:

- `id`
- `tenant_id`
- `name`
- `slug`
- timestamps

`memory_record_tag`:

- `memory_record_id`
- `memory_tag_id`

Add a unique index on `tenant_id` and `slug`.

### `memory_highlights`

Stores simple human-entered highlights from the optional dynamic "Add
highlight" list, without technical classification.

- `id`
- `memory_record_id`
- `text`
- `sort_order`
- timestamps

Do not add AI classification fields in the MVP. Store one row per highlight
item so users can add, edit, remove, and reorder highlights without parsing a
free-form textarea.

### `memory_activity_events`

Stores only the product events needed for validation when the data cannot be
derived from records.

- `id`
- `tenant_id`
- `user_id`
- `memory_record_id`
- `event_name`
- `metadata`
- `occurred_at`

Initial events:

- `timeline_viewed`
- `record_viewed`
- `record_created`
- `email_sourced_record_created`, only if the non-blocking email-source marker
  is included

This table must not store record content.

## Service architecture

Add memory-domain services under `app/Services` and keep them stateless, like
the existing SaaSykit services.

- `MemoryProfileService`: create/update profile, determine whether setup is
  complete, and resolve locale preference.
- `MemoryWorkspaceSettingsService`: resolve workspace settings, return the
  current collaboration mode, and centralize future mode changes.
- `MemoryRecordService`: create and update records, sync tags, sync ordered
  highlights, and enforce tenant-scoped writes.
- `MemoryTimelineService`: query timeline records for a tenant, apply date,
  tag, and location filters, return records ordered for month grouping, and
  eager load tags and media.
- `MemoryActivityService`: record validation events without storing private
  content.
- `MemoryValidationReportService`: return lightweight validation metrics for
  internal admin review.

Livewire components should call services rather than building write logic
directly in views.

## Media architecture

Memory photos are sensitive and must not use the same public assumptions as
blog images.

Recommended approach:

1. Add a private filesystem disk for memory media or use the existing `local`
   disk with a dedicated `memory-records` path for Hostinger validation.
2. Configure `MemoryRecord` as a Spatie Media Library model with a `photos`
   media collection.
3. Store accepted image types only: JPEG, PNG, and WebP.
4. Use these initial validation limits: 2 GB per workspace, 10 MB per image,
   and 25 photos per record.
5. Generate web renditions instead of serving originals directly:
   `thumb` at 320 px long edge, `card` at 768 px long edge, and `large` at
   1600 px long edge.
6. Prefer WebP for web renditions. Add AVIF later only when the server image
   stack supports it reliably. Keep JPEG fallback behavior for browsers or
   environments that cannot use WebP/AVIF.
7. Normalize orientation, strip EXIF/GPS data from served renditions, and keep
   location as an explicit user-entered field rather than hidden image metadata.
8. Keep the original upload private for reprocessing while it fits the
   workspace quota. Serve only the generated web renditions to users.
9. Queue image conversions when a queue worker is available. If Hostinger
   validation cannot run workers reliably, use synchronous conversion only for
   the MVP and keep the service boundary ready for queued processing.
10. Serve photos through an authenticated route that checks
   `MemoryRecordPolicy::view`.
11. For local storage, return the file through Laravel after authorization.
12. For S3-compatible storage later, generate short-lived temporary URLs only
   after authorization.

Do not expose memory media through `public/storage`.

The filesystem configuration should support a future `memory_media` disk backed
by S3-compatible object storage. AWS S3 and Cloudflare R2 are both acceptable
future targets because they can use the S3 API and keep domain code unchanged.

## Localization architecture

Use Laravel localization for UI and user navigation only. English is the
default language.

Add:

- `lang/en`, `lang/pt`, and `lang/es` translation files for memory product UI.
- A user locale field through `memory_profiles.preferred_locale`.
- Locale selection during registration/profile setup and a profile setting to
  change it later.
- Middleware that calls `app()->setLocale($profileLocale)` for authenticated
  product routes.
- Locale-safe UI tests for core product screens.

MVP translation minimum:

- Authentication and registration text used by memory-product entry.
- Profile setup and language preference.
- Product navigation.
- Timeline, empty states, filters, and record detail labels.
- Diary and trip/period create/edit forms.
- Workspace invitation/member-facing copy used by the validation flow.
- Customer-facing billing/account navigation when users must pass through it.

Admin-only screens may remain English in the MVP unless they are part of a
tester-facing workflow. Do not translate user-generated memory content.

## Validation reporting architecture

Validation reporting is admin-only. Do not expose validation dashboards,
metrics, or tester behavior summaries to workspace users in the MVP.

Recommended implementation:

- Use a small Filament Admin page or internal command/report for validation
  metrics.
- Derive metrics from records, profiles, tenant membership, and
  `memory_activity_events` where possible.
- Store event names and metadata only; never store private record body content
  in the activity table.
- Keep the report limited to activation, first record, activity across more
  than two weeks, qualitative email behavior or email-sourced records if the
  marker is included, family/collaborator views, and basic media usage.

## Source tree integration

Recommended additions:

```text
app/
+-- Constants/
|   +-- MemoryActivityEventName.php
|   +-- MemoryCollaborationMode.php
|   +-- MemoryRecordSource.php
|   +-- MemoryRecordType.php
+-- Http/
|   +-- Controllers/MemoryMediaController.php
|   +-- Middleware/EnsureTenantMember.php
+-- Livewire/
|   +-- Memory/
|       +-- MemoryProfileSetup.php
|       +-- MemoryRecordDetail.php
|       +-- MemoryRecordEditor.php
|       +-- MemoryTimeline.php
+-- Models/
|   +-- MemoryActivityEvent.php
|   +-- MemoryHighlight.php
|   +-- MemoryProfile.php
|   +-- MemoryRecord.php
|   +-- MemoryTag.php
|   +-- MemoryWorkspaceSettings.php
+-- Policies/
|   +-- MemoryRecordPolicy.php
+-- Services/
    +-- MemoryActivityService.php
    +-- MemoryProfileService.php
    +-- MemoryRecordService.php
    +-- MemoryTimelineService.php
    +-- MemoryValidationReportService.php
    +-- MemoryWorkspaceSettingsService.php

resources/views/
+-- components/
|   +-- layouts/
|       +-- memory.blade.php
+-- livewire/
    +-- memory/
        +-- memory-profile-setup.blade.php
        +-- memory-record-detail.blade.php
        +-- memory-record-editor.blade.php
        +-- memory-timeline.blade.php

database/
+-- factories/
|   +-- MemoryProfileFactory.php
|   +-- MemoryRecordFactory.php
|   +-- MemoryTagFactory.php
|   +-- MemoryWorkspaceSettingsFactory.php
+-- migrations/
    +-- create_memory_profiles_table.php
    +-- create_memory_workspace_settings_table.php
    +-- create_memory_records_table.php
    +-- create_memory_tags_table.php
    +-- create_memory_record_tag_table.php
    +-- create_memory_highlights_table.php
    +-- create_memory_activity_events_table.php

tests/Feature/
+-- Livewire/Memory/
+-- Policies/MemoryRecordPolicyTest.php
+-- Services/Memory/
```

## Implementation sequence

### Week 1: Tenant-safe foundation and diary capture

- Add memory profile setup and product route gating.
- Add the dedicated memory product layout shell.
- Add workspace settings with default `shared_editing` collaboration mode.
- Add memory record schema, constants, factories, and policy.
- Add diary create/edit flow without photos.
- Add timeline shell ordered by experience date.
- Render the timeline as a vertical month-grouped list with simple cards.
- Add cross-tenant policy tests.

### Week 2: Timeline value and media

- Add tags and highlights.
- Add the email-source marker only if implementation capacity allows after core
  capture and timeline behavior are stable.
- Add record detail view.
- Add private photo attachment, web renditions, and authorized media route.
- Add filters by date, tag, and location.
- Add tests for unauthorized record and media access.

### Week 3: Family workspace validation and readiness

- Configure first validation tier for two users.
- Reuse existing invitation flow for one collaborator.
- Add locale preference and `en`/`pt`/`es` UI translations for MVP screens and
  user navigation.
- Add lightweight admin-only validation reporting.
- Run privacy, media, localization, and timeline acceptance checks.

## Scope cuts if the three-week MVP slips

Do not cut:

- Tenant/workspace privacy.
- Profile setup gate.
- Diary records.
- Experience-date timeline ordering.
- Private media checks once photos are enabled.

Cut or defer first:

- Trip/period editing polish.
- Advanced filter polish.
- Detailed admin validation report UI.
- Teams beyond the existing SaaSykit support.
- Any UI copy or feature that implies album generation.

## Testing strategy

Use PHPUnit feature, service, policy, and Livewire tests.

Required coverage:

- Authenticated and unauthenticated product route access.
- Profile setup gating.
- Tenant membership access and cross-tenant denial.
- Diary record creation and editing.
- Period record date validation.
- Timeline ordering across diary and period records.
- Month grouping across diary and period records.
- Date, tag, and location filters scoped to tenant.
- Memory highlight add/edit/remove/reorder persistence.
- Email-source marker persistence, only if the non-blocking marker is included.
- Private media authorized access and cross-tenant denial.
- Owner invitation and collaborator membership using existing SaaSykit flows.
- Shared editing for owner and collaborator under `shared_editing` mode.
- Locale preference affects UI strings without translating user content.
- Admin-only validation reporting does not expose private record content.

Run focused tests during implementation with:

```bash
php artisan test --compact --filter=Memory
```

## PRD review findings

The PRD is directionally ready for implementation after architecture review.
The codebase review changes the implementation approach in three ways:

1. The two-user family workspace is not a new collaboration system. It should
   be implemented by configuring and reusing SaaSykit tenancy, invitations,
   roles, and plan limits.
2. The memory timeline should not be built as Filament CRUD resources. Filament
   remains useful for account, billing, team, and invitation operations, while
   the timeline uses custom Livewire and Blade UI.
3. Private media needs explicit architecture. The existing public blog media
   pattern is not safe for memory photos.

The brief, PRD, and architecture are now aligned around tenant/workspace
privacy, owner/collaborator shared editing, UI-only localization, and private
media.

## Story manager handoff

Create implementation-ready stories from `docs/prd.md` and this architecture
document. Start with profile setup, tenant-safe timeline routing, and diary
record creation before trip/period records, photos, localization, or reporting.

Every story that touches records or media must include tenant-boundary
acceptance criteria and automated tests.
