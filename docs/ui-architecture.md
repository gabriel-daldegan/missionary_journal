# Life Memory Timeline for Missionaries Frontend Architecture Document

## Status

Ready for SM story preparation and Dev implementation planning.

## Template and Framework Selection

### Starter Template Decision

This is a brownfield enhancement inside the existing SaaSykit Laravel
application, not a new standalone frontend project.

The frontend architecture will therefore use the existing application shell,
Laravel routing, Blade views, Livewire components, Tailwind CSS 4,
DaisyUI 5, Alpine.js, Vite, Laravel localization, and current SaaSykit tenancy
primitives. No new frontend starter template should be introduced for the MVP.

### Source Artifacts Reviewed

- `docs/prd.md`
- `docs/architecture.md`
- `docs/front-end-spec.md`
- `docs/brief.md`
- `composer.json`
- `package.json`
- Existing routes, layouts, Livewire components, Blade components, CSS entry
  points, and localization directories

### Existing Constraints

- The memory product route group is `/memories/{tenant:uuid}`.
- The memory surface must use a dedicated authenticated memory layout, proposed
  as `resources/views/components/layouts/memory.blade.php`.
- Timeline, editor, detail, filters, profile setup, and private media states
  must be custom Livewire and Blade UI, not Filament Resources or table-first
  CRUD.
- Filament Dashboard remains responsible for billing, subscriptions, workspace
  settings, users, roles, teams, invitations, security, and account profile
  operations.
- The visual language must be calmer than the public SaaS marketing pages:
  neutral surfaces, subtle borders, restrained teal or blue-gray memory accents,
  simple cards, compact navigation, and no heavy hero, blog-card, or public
  album treatment.
- User interface strings support English, Portuguese, and Spanish. User-created
  memory content is not translated.
- Records and photos remain private to the tenant/workspace boundary. Media
  display must use authorized delivery, not public URLs.
- The MVP must remain scoped for a three-week validation build.

### Change Log

| Date | Version | Description | Author |
| --- | --- | --- | --- |
| 2026-05-18 | 0.1 | Started frontend architecture from PRD, brownfield architecture, UX specification, and current Laravel/SaaSykit codebase. | Aria |
| 2026-05-18 | 1.0 | Completed frontend architecture for dedicated Livewire/Blade memory product surface. | Aria |

## Frontend Tech Stack

This section must stay synchronized with `docs/architecture.md`,
`docs/prd.md`, `composer.json`, and `package.json`.

| Category | Technology | Version | Purpose | Rationale |
| --- | --- | --- | --- | --- |
| Application Framework | Laravel | 13.x | Host authenticated memory routes, middleware, policies, controllers, services, Blade rendering, and localization. | Existing SaaSykit application is Laravel-based; staying inside the monolith preserves tenancy, auth, billing, and deployment behavior. |
| Runtime Language | PHP | 8.4 | Server-side rendering, Livewire components, policies, services, model access, and tests. | Required by `composer.json` and matches project setup. |
| Reactive UI Framework | Livewire | 4.x | Build server-driven memory profile setup, timeline, filters, record editor, record detail, and upload state interactions. | Fits the existing TALL stack and keeps validation, authorization, and state close to Laravel services and policies. |
| View Layer | Blade | Laravel 13 compatible | Compose the dedicated memory layout, reusable UI components, Livewire views, empty states, and detail reading views. | Existing app uses Blade components and Livewire Blade views; this avoids a second frontend runtime. |
| Client Interactions | Alpine.js | 3.x | Small local interactions such as mobile menus, create menus, disclosure panels, and lightweight UI affordances. | Existing dependency; useful for local UI state that should not require a Livewire round trip. |
| Admin and Account UI | Filament | 5.x | Continue account, billing, workspace, users, roles, teams, invitations, security, and admin validation surfaces. | PRD and architecture explicitly keep operational/admin flows in Filament while excluding Filament CRUD from the customer memory UI. |
| Component Primitives | Existing Blade components plus memory-specific Blade/Livewire partials | Project-local | Reuse buttons, inputs, layout conventions, and create memory-specific cards, timeline groups, filters, media states, and editor sections. | Reuse lowers risk, but timeline cards must not inherit public blog-card or marketing hero patterns. |
| Styling | Tailwind CSS | 4.x | Implement responsive layout, typography, spacing, borders, states, and component styling. | Existing CSS-first Tailwind setup in `resources/css/app.css`; supports restrained memory-specific tokens without adding a design system dependency. |
| UI Utility Library | DaisyUI | 5.x | Provide lightweight primitive affordances where already appropriate, such as tabs or basic controls. | Existing dependency; use selectively and style down so the memory product does not feel like an admin or marketing surface. |
| Build Tool | Vite | 7.x | Compile CSS and JavaScript assets through the existing Laravel Vite integration. | Existing `npm run dev` and `npm run build` scripts already support the application. |
| Asset Integration | Laravel Vite Plugin | 2.x | Connect Laravel Blade layouts to Vite-built assets. | Existing dependency and standard Laravel integration path. |
| Routing | Laravel named routes and route model binding | Laravel 13 compatible | Define tenant-scoped memory routes under `/memories/{tenant:uuid}` with `auth`, `verified`, tenant membership, and profile setup gates. | Keeps URLs, redirects, tests, and authorization inside Laravel conventions. |
| State Management | Livewire component state, Laravel services, Eloquent models, and request/session context | Project-local | Own filter state, editor state, profile completion, upload progress, and tenant context without a client-side store. | MVP screens are server-driven and privacy-sensitive; duplicating domain state into a JavaScript store increases complexity without value. |
| Form Handling | Livewire actions and validation backed by services and policies | Livewire 4 / Laravel 13 | Capture profile setup, diary records, trip/period records, Memory Highlights, filters, and media upload constraints. | Keeps validation and authorization in the same server-side boundary used by feature and Livewire tests. |
| Media UI | Spatie Media Library plus authorized Laravel media route | Composer dependency | Show thumbnails, upload progress, file-limit feedback, protected media, and unavailable states. | Existing media package can be reused, but memory photos must not follow public blog-media assumptions. |
| Localization | Laravel localization with `__()` and `lang/{locale}` files | Laravel 13 compatible | Provide English, Portuguese, and Spanish UI strings for memory layout, forms, filters, cards, errors, and controls. | Matches existing translation usage while preserving user-generated content in its original language. |
| Testing | PHPUnit, Laravel feature tests, policy tests, and Livewire tests | PHPUnit 11 / project-local | Verify route access, profile gating, timeline behavior, filters, private media authorization, localization, and Livewire interactions. | Required by project quality rules and by the privacy-sensitive tenant boundary. |
| Static Analysis and Formatting | Larastan/PHPStan and Laravel Pint | Larastan 3 / Pint 1 | Enforce PHP quality and formatting during implementation. | Existing scripts already route checks through Sail/Docker. |
| Motion | CSS transitions, Alpine.js, and Livewire loading states | Project-local | Provide subtle save, validation, filter, upload, and menu state feedback. | UX spec requires calm micro-interactions and excludes complex timeline animation. |
| Development Runtime | Laravel Sail / Docker Compose | Sail 1.x | Run local gates consistently for PHP, database, Redis, and tests. | Project scripts use `compose.yml`; memory implementation checks should follow the existing Sail path. |

## Project Structure

The frontend implementation should stay inside the existing Laravel application
structure and add only memory-domain directories where they clarify ownership.

```plaintext
routes/
+-- web.php
    # Add the authenticated /memories/{tenant:uuid} route group here.

app/
+-- Http/
|   +-- Controllers/
|   |   +-- MemoryMediaController.php
|   +-- Middleware/
|       +-- EnsureTenantMember.php
|       +-- EnsureMemoryProfileComplete.php
+-- Livewire/
|   +-- Memory/
|       +-- MemoryProfileSetup.php
|       +-- MemoryTimeline.php
|       +-- MemoryRecordEditor.php
|       +-- MemoryRecordDetail.php
+-- Services/
|   +-- MemoryProfileService.php
|   +-- MemoryRecordService.php
|   +-- MemoryTimelineService.php
|   +-- MemoryWorkspaceSettingsService.php
+-- Policies/
    +-- MemoryRecordPolicy.php

resources/
+-- views/
|   +-- components/
|   |   +-- layouts/
|   |   |   +-- memory.blade.php
|   |   +-- memory/
|   |       +-- action-link.blade.php
|   |       +-- empty-state.blade.php
|   |       +-- field-error.blade.php
|   |       +-- media-state.blade.php
|   |       +-- private-cue.blade.php
|   |       +-- timeline-card.blade.php
|   |       +-- timeline-month.blade.php
|   +-- livewire/
|       +-- memory/
|           +-- memory-profile-setup.blade.php
|           +-- memory-timeline.blade.php
|           +-- memory-record-editor.blade.php
|           +-- memory-record-detail.blade.php
+-- css/
    +-- app.css
    +-- colors.css
    +-- styles.css
    # Add memory-specific Tailwind theme tokens to existing CSS files only if
    # repeated utility classes become hard to maintain.

lang/
+-- en/
|   +-- memory.php
+-- pt/
|   +-- memory.php
+-- es/
    +-- memory.php

tests/
+-- Feature/
|   +-- Livewire/
|   |   +-- Memory/
|   |       +-- MemoryProfileSetupTest.php
|   |       +-- MemoryTimelineTest.php
|   |       +-- MemoryRecordEditorTest.php
|   |       +-- MemoryRecordDetailTest.php
|   +-- Memory/
|       +-- MemoryRouteAccessTest.php
|       +-- MemoryMediaAccessTest.php
|       +-- MemoryLocalizationTest.php
+-- Unit/
    +-- Services/
        +-- MemoryTimelineServiceTest.php
```

### Structure Rules

- Keep customer-facing memory UI in `app/Livewire/Memory` and
  `resources/views/livewire/memory`.
- Keep reusable visual fragments in `resources/views/components/memory` only
  when they are shared across two or more memory screens.
- Keep the dedicated memory shell in
  `resources/views/components/layouts/memory.blade.php`; do not modify the
  public `app.blade.php` layout to satisfy memory-product needs.
- Keep account, billing, workspace, team, invitation, role, security, and admin
  reporting flows in Filament or existing SaaSykit surfaces.
- Keep tenant membership checks outside Blade templates. Routes, middleware,
  policies, and services should deny unauthorized access before private memory
  content reaches a view.
- Use named routes for all memory navigation and redirects.
- Use `lang/{locale}/memory.php` for memory-product interface strings and keep
  user-generated content unmodified.
- Do not add React, Vue, Inertia, a JavaScript store, or a new CSS framework for
  the MVP.

## Component Standards

### Component Format

Memory product screens should use class-based Livewire components with separate
Blade views:

- Class: `app/Livewire/Memory/{ComponentName}.php`
- View: `resources/views/livewire/memory/{component-name}.blade.php`

This matches the existing application pattern in `app/Livewire` and
`resources/views/livewire`, keeps PHP authorization and validation explicit,
and gives Blade enough room for reading/writing-focused layouts.

Create components through Artisan unless a story has a specific reason not to:

```bash
php artisan make:livewire Memory/MemoryTimeline --class --no-interaction
```

Reusable memory UI fragments that are not independent Livewire state owners
should be Blade components under `resources/views/components/memory`. Examples:
timeline cards, month separators, private workspace cues, empty states, and
protected media placeholders.

### Livewire Component Template

Use this as an illustrative baseline for memory full-page or screen-level
components. Final route binding, middleware names, and service calls should be
confirmed in the implementation story before copying this pattern directly.

```php
<?php

namespace App\Livewire\Memory;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MemoryTimeline extends Component
{
    public function mount(public Tenant $tenant): void
    {
        abort_unless(auth()->user()?->canAccessTenant($this->tenant), 403);
    }

    public function render(): View
    {
        return view('livewire.memory.memory-timeline')
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
            ]);
    }
}
```

Production components should delegate domain work to services, for example
`MemoryTimelineService`, `MemoryRecordService`, and `MemoryProfileService`.
They should not build complex Eloquent queries or shared editing rules directly
inside Blade views.

### Blade View Pattern

Memory Livewire views should keep structure semantic and stable. This snippet
shows the intended shape, not a final styling contract:

```blade
<section class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-5">
        <p class="text-sm font-medium text-teal-700">
            {{ __('memory.private_workspace') }}
        </p>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-950">
                    {{ __('memory.timeline.title') }}
                </h1>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    {{ __('memory.timeline.subtitle') }}
                </p>
            </div>

            <a href="{{ route('memories.records.create', [$tenant, 'diary']) }}"
               class="btn btn-sm border-teal-700 bg-teal-700 text-white hover:bg-teal-800">
                {{ __('memory.actions.create_diary') }}
            </a>
        </div>
    </header>

    <div class="flex flex-col gap-8">
        {{-- Timeline groups render here. --}}
    </div>
</section>
```

### Naming Conventions

| Element | Convention | Examples |
| --- | --- | --- |
| Livewire namespace | `App\Livewire\Memory` | `MemoryTimeline`, `MemoryRecordEditor` |
| Livewire view path | `resources/views/livewire/memory/{kebab-name}.blade.php` | `memory-timeline.blade.php` |
| Blade component path | `resources/views/components/memory/{kebab-name}.blade.php` | `timeline-card.blade.php` |
| Route names | `memories.{area}.{action}` or `memories.{screen}` | `memories.timeline`, `memories.records.create` |
| Translation keys | `memory.{screen}.{label}` | `memory.timeline.empty_title` |
| CSS/theme tokens | Memory-specific names only when reused | `--color-memory-primary` |
| Public properties | Descriptive UI state names | `$selectedTag`, `$dateFrom`, `$location` |
| Actions | Verb-first methods | `saveProfile`, `createRecord`, `clearFilters` |
| Events | Domain or UI outcome names | `record-saved`, `filters-cleared` |

### Component Boundary Rules

- Screen-level Livewire components own request-visible state such as selected
  filters, form input, save state, and upload state.
- Services own persistence, timeline query construction, shared editing rules,
  activity recording, and profile completion checks.
- Middleware and policies own the primary access boundary for tenants, records,
  and media. Livewire checks are additional protection, not a substitute.
- Blade components are presentational and must not query tenant-private data.
- Private media Blade components may render authorized URLs or protected states
  passed from controllers, services, or Livewire components. They must not
  compute public storage paths or assume `public/storage` access.
- The memory layout shell owns navigation, workspace context, profile/language
  controls, and dashboard escape links.
- Components that render lists must use stable `wire:key` values in loops.
- Long-running or privacy-sensitive actions must validate and authorize inside
  the Livewire action, even if middleware already ran.
- UI copy must use translation keys; do not hard-code customer-facing strings
  in Blade except temporary implementation scaffolding that is removed before
  story closure.

### Implementation Cautions

- Do not treat the sample `mount()` authorization as the only privacy control.
  Route middleware, route model binding, policies, and service-layer tenant
  scopes must all align before private content reaches a view.
- Do not let Blade components fetch records, media, tags, or tenant settings on
  their own. Pass already-authorized data into the view.
- Do not let DaisyUI defaults or existing marketing components dictate the
  memory-product visual language. Use them only when they support the restrained
  memory UI defined in `docs/front-end-spec.md`.
- Do not create a reusable component after a single use. Extract only when a UI
  fragment appears across at least two memory screens or carries a security or
  accessibility rule worth centralizing.

## State Management

The memory product should not introduce a client-side state store. State should
stay close to the server-side boundary that already owns authentication,
authorization, tenant membership, validation, and persistence.

### State Ownership

| State Type | Owner | Examples | Persistence |
| --- | --- | --- | --- |
| Tenant context | Route model binding, `EnsureTenantMember`, policies, services | Current tenant, membership, owner/collaborator access | Request scoped |
| Profile gate state | `MemoryProfileService`, `MemoryProfileSetup` | Display name, preferred locale, setup completion | Database |
| Shareable timeline filter state | `MemoryTimeline` Livewire component | Date range, selected tag | URL query string where useful |
| Sensitive or free-text filter state | `MemoryTimeline` Livewire component | Location search | Transient Livewire state by default |
| Timeline result state | `MemoryTimelineService` | Month groups, records, tags, media summary | Query result, not browser store |
| Editor draft state | `MemoryRecordEditor` Livewire component | Body, title, dates, location, tags, people, highlights | Transient Livewire component state until saved |
| Record persistence | `MemoryRecordService`, models, policies | Diary records, trip/period records, tags, highlights | Database |
| Media UI state | Livewire upload state, `MemoryMediaController`, media service logic | Uploading, limit errors, authorized thumbnail URLs, protected/unavailable states | Private storage plus request state |
| Localization state | `MemoryProfileService`, middleware, Laravel locale | `en`, `pt`, `es` interface preference | Database profile preference |
| Validation reporting state | Admin-only service or Filament surface | Profile completion, first record, active tenants | Derived from database events/records |

### Store Structure

There is no frontend store directory. The equivalent state structure is the
combination of Livewire screen state, services, policies, and persisted models:

```plaintext
app/
+-- Livewire/
|   +-- Memory/
|       +-- MemoryProfileSetup.php      # profile form state
|       +-- MemoryTimeline.php          # filters and timeline screen state
|       +-- MemoryRecordEditor.php      # create/edit form state
|       +-- MemoryRecordDetail.php      # detail screen state
+-- Services/
|   +-- MemoryProfileService.php        # profile completion and locale
|   +-- MemoryTimelineService.php       # tenant-scoped timeline queries
|   +-- MemoryRecordService.php         # record, tag, and highlight writes
|   +-- MemoryWorkspaceSettingsService.php
+-- Policies/
|   +-- MemoryRecordPolicy.php          # view/create/update/media rules
+-- Models/
    +-- MemoryProfile.php
    +-- MemoryRecord.php
    +-- MemoryTag.php
    +-- MemoryHighlight.php
    +-- MemoryWorkspaceSettings.php
```

### Livewire State Template

Use typed public properties for request-visible UI state, and keep persistence
behind services:

```php
<?php

namespace App\Livewire\Memory;

use App\Models\Tenant;
use App\Services\MemoryTimelineService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class MemoryTimeline extends Component
{
    #[Url(as: 'from')]
    public ?string $dateFrom = null;

    #[Url(as: 'to')]
    public ?string $dateTo = null;

    #[Url(as: 'tag')]
    public ?string $selectedTag = null;

    public ?string $location = null;

    public function mount(public Tenant $tenant): void
    {
        abort_unless(auth()->user()?->canAccessTenant($this->tenant), 403);
    }

    public function clearFilters(): void
    {
        $this->reset('dateFrom', 'dateTo', 'selectedTag', 'location');
    }

    public function render(MemoryTimelineService $timelineService): View
    {
        return view('livewire.memory.memory-timeline', [
            'monthGroups' => $timelineService->forTenant($this->tenant, [
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'tag' => $this->selectedTag,
                'location' => $this->location,
            ]),
        ])->layout('components.layouts.memory', [
            'tenant' => $this->tenant,
        ]);
    }
}
```

### State Rules

- Use Livewire public properties only for state the screen needs to render or
  bind to controls.
- Use `#[Url]` only for filters that should survive refresh/share/back-button
  behavior. Do not place record body, highlights, people, media filenames, or
  private content in the URL.
- Keep free-text or sensitive filters, including location search, out of the
  URL by default because query strings can leak through browser history,
  screenshots, analytics, copied links, or support logs.
- Treat Livewire public properties as request-visible serialized state. Do not
  store secrets, authorization decisions, full private media paths, private
  filenames, or server-only access metadata in public properties.
- Reset pagination or visible timeline position when filters change once
  pagination or incremental loading exists.
- Validate and authorize inside every mutating action, even if the screen was
  already gated by middleware.
- Treat unsaved editor content as transient unless a later story explicitly
  implements autosave or draft persistence. Refreshing or navigating away may
  lose unsaved body, notes, highlights, people, and location fields.
- Keep record body, notes, highlights, people, and media metadata out of
  browser-local persistence for the MVP.
- Use services for state transitions that matter beyond a single render, such
  as creating records, updating highlights, completing profiles, setting
  locale, and recording validation events.
- Keep derived timeline groups as query results. Do not persist month grouping
  as separate frontend state.
- Re-resolve tenant and record access on the server for every mutating action.
  Never trust client-supplied tenant IDs, record IDs, media IDs, tag IDs, or
  highlight IDs without tenant-scoped queries and policy checks.
- Keep timeline queries eager-loaded and bounded. `MemoryTimelineService`
  should load tags, primary media summary, and counts deliberately, and should
  support pagination or incremental loading before validation tenants build a
  long real history.
- Use Livewire loading, dirty, and validation states for feedback rather than a
  custom JavaScript state layer.

## API Integration

The MVP memory UI is not a separate SPA and should not add a frontend API
client. Browser interaction flows through Laravel named routes, Livewire
actions, policies, services, Eloquent models, and one authorized media delivery
controller.

### Integration Pattern

| Interaction | Frontend Entry | Server Boundary | Response |
| --- | --- | --- | --- |
| Open memory product | Named Laravel route | `auth`, `verified`, tenant membership middleware | Livewire full-page component |
| Complete profile setup | Livewire action | `MemoryProfileService` plus validation | Redirect to tenant timeline |
| Browse timeline | Livewire render/action | `MemoryTimelineService` tenant-scoped query | Blade-rendered month groups |
| Filter timeline | Livewire properties/actions | `MemoryTimelineService` tenant-scoped query | Updated Livewire DOM |
| Create or edit record | Livewire action | `MemoryRecordService`, policy checks, validation | Redirect or saved state |
| Reorder highlights | Livewire action | `MemoryRecordService`, policy checks | Updated editor state |
| Attach photos | Livewire upload/action | validation, policy checks, private media storage | Upload state and thumbnails |
| Display photo | Authorized route/controller | `MemoryRecordPolicy` or equivalent media policy | Streamed private media response |
| Change UI language | Livewire action/profile control | `MemoryProfileService`, locale middleware | Redirect or refreshed UI strings |

### Route Configuration

Use Laravel named routes and route model binding. Keep the setup route reachable
before profile completion, and gate timeline/editor/detail routes after setup.

```php
<?php

use App\Http\Controllers\MemoryMediaController;
use App\Livewire\Memory\MemoryProfileSetup;
use App\Livewire\Memory\MemoryRecordDetail;
use App\Livewire\Memory\MemoryRecordEditor;
use App\Livewire\Memory\MemoryTimeline;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant.member'])
    ->prefix('/memories/{tenant:uuid}')
    ->name('memories.')
    ->group(function (): void {
        Route::get('/profile/setup', MemoryProfileSetup::class)
            ->name('profile.setup');

        Route::middleware('memory.profile.complete')->group(function (): void {
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
    });
```

Middleware names are placeholders for story implementation. The important
architectural rule is that tenant membership and profile completion are route
boundaries, not Blade-condition-only behavior.

### Service Template

Livewire components should call services for writes and important read models.
Services should receive already-resolved model instances and still scope every
query by tenant. This snippet documents the desired boundary, not final
copy-paste implementation.

```php
<?php

namespace App\Services;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MemoryRecordService
{
    /**
     * @param array{
     *     body: string,
     *     experience_date: string,
     *     location?: string|null,
     *     tags?: array<int, string>,
     *     highlights?: array<int, array{text: string, position: int}>
     * } $data
     */
    public function createDiaryRecord(Tenant $tenant, User $author, array $data): MemoryRecord
    {
        Gate::forUser($author)->authorize('create', [MemoryRecord::class, $tenant]);

        $record = MemoryRecord::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $author->id,
            'type' => 'diary',
            'body' => $data['body'],
            'experience_date' => $data['experience_date'],
            'location' => $data['location'] ?? null,
        ]);

        $this->syncTags($tenant, $record, $data['tags'] ?? []);
        $this->syncHighlights($record, $data['highlights'] ?? []);

        return $record->refresh();
    }

    /**
     * @param array<int, string> $tags
     */
    private function syncTags(Tenant $tenant, MemoryRecord $record, array $tags): void
    {
    }

    /**
     * @param array<int, array{text: string, position: int}> $highlights
     */
    private function syncHighlights(MemoryRecord $record, array $highlights): void
    {
    }
}
```

### Livewire Action Pattern

Livewire actions are the frontend's mutation boundary. Each action must
validate input, authorize the action, delegate persistence, and redirect through
named routes.

```php
public function save(MemoryRecordService $recordService): mixed
{
    $validated = $this->validate();

    $record = $recordService->createDiaryRecord(
        tenant: $this->tenant,
        author: auth()->user(),
        data: $validated,
    );

    return redirect()->route('memories.records.show', [$this->tenant, $record]);
}
```

### Media Delivery Pattern

Private memory media must be served by an authorized Laravel route or equivalent
private access mechanism. Blade and Livewire views receive an authorized route,
not a public storage path.

```php
public function __invoke(Tenant $tenant, Media $media): BinaryFileResponse
{
    $record = MemoryRecord::query()
        ->whereBelongsTo($tenant)
        ->whereHas('media', fn ($query) => $query->whereKey($media->getKey()))
        ->firstOrFail();

    Gate::authorize('viewMedia', [$record, $media]);

    return response()->file($media->getPath());
}
```

The final controller should account for the actual media model namespace,
storage disk, conversion/rendition strategy, and Hostinger filesystem behavior
defined during implementation.

### Implementation and Test Notes

- Treat route, service, and controller snippets as architectural examples. Final
  stories should replace placeholder middleware names, model namespaces,
  constants, enums, validation rules, and media handling with concrete
  implementation choices.
- Do not leave stubbed private methods from the service example in production
  code. Each extracted helper must either contain real behavior or be deferred.
- Prefer record type constants or enums once they exist; avoid scattering raw
  strings such as `diary` across components and services.
- Every story that adds record or media routes must include direct URL tests,
  not only UI navigation tests. Cover direct cross-tenant access to record
  detail, record edit, media URLs, and denied states.
- Tests for denied access must verify that responses do not leak record titles,
  body excerpts, filenames, tenant-private metadata, or media descriptions.
- Livewire action tests must cover validation failures and authorization
  failures separately.
- If a later story introduces a JSON endpoint for a specific upload or async
  behavior, it must use the same tenant scoping, policy checks, localization,
  and leakage tests defined here.

### API Rules

- Do not add JSON API endpoints for MVP customer screens unless an
  implementation story proves Livewire cannot support the workflow.
- Do not call `fetch`, Axios, or custom JavaScript clients for memory CRUD in
  the MVP.
- Do not expose public media URLs, original private file paths, filenames, or
  record-private metadata in Blade.
- Use named routes and route model binding for all navigation.
- Re-scope nested records and media to the current tenant on every request.
- Return generic access-denied states that do not leak record title, excerpt,
  filename, tenant-private metadata, or media descriptions.
- Keep validation messages localized through Laravel translation files.
- Use Eloquent relationships, policies, and services before raw queries.

## Routing

Routing must make the memory product feel like a dedicated authenticated
product surface while preserving SaaSykit account, billing, tenant, and
invitation behavior.

### Route Hierarchy

```plaintext
/memories/{tenant:uuid}
+-- /profile/setup
+-- /timeline
+-- /records/create/{type}
+-- /records/{record:uuid}
+-- /records/{record:uuid}/edit
+-- /media/{media:uuid}
```

### Route Responsibilities

| Route | Component/Controller | Gate | Purpose |
| --- | --- | --- | --- |
| `memories.profile.setup` | `MemoryProfileSetup` | Authenticated tenant member | Complete display name, preferred UI language, and optional context before timeline access. |
| `memories.timeline` | `MemoryTimeline` | Authenticated tenant member with completed memory profile | Primary month-grouped timeline home. |
| `memories.records.create` | `MemoryRecordEditor` | Tenant member with completed profile and create permission | Create diary or trip/period records. |
| `memories.records.show` | `MemoryRecordDetail` | Tenant member with completed profile and view permission | Read a single memory record without admin/table framing. |
| `memories.records.edit` | `MemoryRecordEditor` | Tenant member with completed profile and update permission | Edit an existing memory record. |
| `memories.media.show` | `MemoryMediaController` | Tenant member with completed profile and media view permission | Serve private media through authorized delivery. |

### Navigation Rules

- The default memory entry point for a tenant should redirect to profile setup
  when the current user has no completed memory profile.
- After profile setup, the default memory destination is
  `memories.timeline`.
- The memory layout shell must keep first-level access to timeline and create
  actions. Profile/language controls and the dashboard escape can sit in the
  top bar or mobile menu.
- Create actions should route explicitly to a supported record type:
  `diary` or `period`. Stories should replace these raw strings with constants
  or enums when the record type layer exists.
- Record detail and edit routes must re-scope records to the route tenant. A
  valid record UUID from a different tenant must return a generic denial or
  not-found response without private content.
- Media routes must re-scope media to the route tenant through the parent
  record. A valid media UUID from a different tenant must not reveal file
  existence, filename, record title, body excerpt, or metadata.
- The dashboard escape should use the existing `dashboard` route or existing
  SaaSykit tenant dashboard resolution; it must not duplicate billing or
  workspace management inside the memory layout.
- Public marketing routes, blog routes, checkout routes, roadmap routes, and
  Filament Dashboard routes must continue to work unchanged.

### Redirect and Denial Behavior

| Scenario | Expected Behavior |
| --- | --- |
| Guest opens `/memories/{tenant}` | Redirect to login through existing auth behavior. |
| Authenticated non-member opens tenant memory route | Return generic access denied or not found. Do not leak tenant-private details. |
| Member without completed memory profile opens timeline/editor/detail/media | Redirect to `memories.profile.setup` for the same tenant, except where redirecting media would expose behavior; media can deny generically. |
| Member completes profile setup | Redirect to `memories.timeline` for the same tenant. |
| Member opens create route with unsupported `{type}` | Return validation-style not found or redirect to timeline with a generic message; do not create unknown record types. |
| Member opens cross-tenant record UUID | Deny or not-found generically without private record data. |
| Member opens cross-tenant media UUID | Deny or not-found generically without private media data. |

### Route Model Binding Rules

- Use `{tenant:uuid}`, `{record:uuid}`, and `{media:uuid}` in routes, but do
  not trust binding alone for authorization.
- Re-scope nested models after binding. A bound record or media item must belong
  to the route tenant before it is rendered or streamed.
- Prefer policy methods for `view`, `create`, `update`, and `viewMedia`.
- Avoid implicit redirects that reveal whether a private record or media item
  exists in another tenant.

### Route Tests Required by Stories

- Guest redirect for memory entry routes.
- Tenant member access to profile setup and timeline.
- Non-member denial for profile setup, timeline, record detail, record edit,
  and media routes.
- Profile gate redirect from timeline/editor/detail to setup.
- Completed profile redirect to timeline.
- Unsupported create type behavior.
- Cross-tenant record detail and edit denial.
- Cross-tenant media direct URL denial.
- Existing Filament Dashboard, invitation, account, billing, and public routes
  are not broken by the memory route group.

## Styling Guidelines

The memory product should reuse the existing Tailwind CSS 4, DaisyUI 5, Blade,
and Vite setup while adding a restrained memory-product visual layer. This is a
product UI, not a marketing page and not an admin table.

### Styling Approach

- Use Tailwind utility classes in Blade views for layout and one-off component
  composition.
- Use existing Blade components when they support the memory product's calm,
  private, writing-first interface. Create memory-specific Blade components
  when reuse is needed across screens.
- Use DaisyUI selectively for primitive behavior and baseline control styling.
  Do not let DaisyUI defaults create a dashboard/admin feel.
- Keep cards at `8px` radius or less, with subtle borders and minimal shadows.
- Prefer `gap-*` utilities over sibling margins for layout spacing.
- Use explicit focus rings or outlines as the primary focus treatment. Shadows
  may support focus, but should not be the only visible focus indicator.
- Keep the main timeline and editor measure constrained. Wide screens may add a
  supporting side area for filters or metadata, but body text and writing areas
  should not stretch across the full viewport.
- Use stable dimensions and responsive constraints for repeated timeline cards,
  media thumbnails, action rows, filter controls, and mobile navigation so
  loading states and translated text do not shift the layout unexpectedly.
- Use visible focus states for all interactive controls, including record
  cards, create actions, filters, upload controls, language controls, and
  mobile menu items.
- Use active filter labels and reset actions with text, not color alone.
- Respect reduced-motion preferences for transitions.

### Visual Boundaries

- Do not reuse public marketing hero composition, feature sections, footer
  treatment, heavy purple surfaces, oversized decorative media, hover scaling,
  or blog-card visuals.
- Do not render the memory product as Filament CRUD, an admin table, or a
  dashboard list.
- Do not use album-generation language or visual treatment.
- Use existing SaaSykit purple only as a small continuity accent for
  account/dashboard escape paths, not as the dominant memory-product color.
- Use neutral hierarchy as the dominant surface language. Teal and blue-gray are
  action, focus, navigation, and state accents; they should not become dominant
  tinted page backgrounds.
- Do not introduce dark mode as new MVP scope unless the existing app surface
  already has a supported dark-mode path and the story includes matching QA.

### Global Theme Variables

Only add memory-specific theme variables when repeated utility classes become
hard to maintain. If added, start with the smallest useful token set and keep it
in the existing Tailwind CSS 4 CSS-first setup, preferably near the current
color tokens in `resources/css/colors.css`.

```css
@theme {
    --color-memory-primary: #2f6f73;
    --color-memory-secondary: #567a9c;
    --color-memory-canvas: #f7f9f8;
    --color-memory-surface: #ffffff;
    --color-memory-border: #d9e2df;
    --color-memory-heading: #1f2933;
    --color-memory-body: #4a5562;
}
```

### Tailwind Patterns

Use these as architectural patterns, not exact final class contracts. Custom
`memory-*` utilities should only be used after the token names are added and
verified in the Tailwind v4 build. Until then, use standard Tailwind utilities
that express the same layout and contrast intent.

```blade
<main class="min-h-screen bg-slate-50 text-slate-700">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</main>
```

```blade
<article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm focus-within:ring-2 focus-within:ring-teal-700 focus-within:ring-offset-2">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        {{ $slot }}
    </div>
</article>
```

```blade
<button
    type="button"
    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700"
>
    {{ $slot }}
</button>
```

### Responsive Styling Rules

- Mobile (`0px-639px`): single-column timeline, stacked filters, compact top
  navigation, and full-width primary actions where appropriate.
- Tablet (`640px-1023px`): single primary column with wider cards and
  side-by-side action rows when labels fit.
- Desktop (`1024px+`): constrained main column with optional supporting side
  area for filters or metadata.
- Wide (`1440px+`): preserve readable line length; do not stretch record body,
  notes, or timeline cards to fill the viewport.

### Localization Styling Rules

- Design button rows and filter controls for English, Portuguese, and Spanish
  expansion.
- Avoid fixed-width text buttons unless the longest supported label is known to
  fit at `375px`.
- Let tags, locations, and action labels wrap instead of overlapping.
- Prefer stacked controls on mobile when translated labels become long.
- Use text labels for primary workflow commands such as creating and saving
  records.
- Use familiar icons for common utility actions such as filter, upload, edit,
  language, dashboard escape, close, remove, and reorder when available in the
  existing icon stack. Pair icons with visible labels, accessible labels, or
  tooltips according to the control's importance and available space.

### Accessibility Styling Rules

- Normal text must meet WCAG AA contrast against its background.
- Focus indicators must be visible on both neutral and teal surfaces.
- Use outline or ring utilities for keyboard focus; do not rely on color change
  or box shadow alone.
- Error, warning, success, filter-active, and private/protected media states
  need text labels in addition to color.
- Loading skeletons must preserve layout dimensions and not hide primary
  navigation or create actions.
- Motion should be short, subtle, and disabled or reduced under
  `prefers-reduced-motion`.

## Testing Requirements

Memory UI stories must be covered by PHPUnit feature, Livewire, policy, and
service tests. Do not introduce Pest tests. Browser-level checks can be added
later if the project adopts them, but MVP quality gates should start with the
existing PHPUnit and Laravel testing stack.

### Test Locations

```plaintext
tests/
+-- Feature/
|   +-- Memory/
|   |   +-- MemoryRouteAccessTest.php
|   |   +-- MemoryMediaAccessTest.php
|   |   +-- MemoryLocalizationTest.php
|   +-- Livewire/
|       +-- Memory/
|           +-- MemoryProfileSetupTest.php
|           +-- MemoryTimelineTest.php
|           +-- MemoryRecordEditorTest.php
|           +-- MemoryRecordDetailTest.php
|   +-- Services/
|       +-- Memory/
|           +-- MemoryTimelineServiceTest.php
```

Create test classes through Artisan:

```bash
php artisan make:test Livewire/Memory/MemoryTimelineTest --phpunit --no-interaction
php artisan make:test Memory/MemoryRouteAccessTest --phpunit --no-interaction
php artisan make:test Services/Memory/MemoryTimelineServiceTest --phpunit --no-interaction
```

### Component Test Template

Use PHPUnit classes and `Livewire::test()` for Livewire components:

```php
<?php

namespace Tests\Feature\Livewire\Memory;

use App\Livewire\Memory\MemoryTimeline;
use App\Models\MemoryRecord;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class MemoryTimelineTest extends FeatureTest
{
    public function test_timeline_renders_tenant_records_grouped_by_month(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $record = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'body' => 'Visited a new family near the city center.',
            'experience_date' => now()->toDateString(),
        ]);

        Livewire::test(MemoryTimeline::class, ['tenant' => $tenant])
            ->assertSee('Visited a new family')
            ->assertSee(now()->format('Y'));
    }

    public function test_timeline_does_not_render_cross_tenant_records(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $otherRecord = MemoryRecord::factory()->create([
            'tenant_id' => $otherTenant->id,
            'body' => 'Private record from another workspace',
        ]);

        Livewire::test(MemoryTimeline::class, ['tenant' => $tenant])
            ->assertDontSee($otherRecord->body);
    }
}
```

The final test setup should use the actual tenant membership helpers, factories,
record fields, casts, and profile setup helpers available after implementation.
Exact Livewire status assertions are implementation-dependent; prefer
assertions that prove visible behavior, redirects, validation errors, and
authorization outcomes.

### Required Coverage by Story Type

| Story Type | Required Tests |
| --- | --- |
| Route shell and layout | Guest redirect, member access, non-member denial, dedicated memory layout, existing Filament/account route regression. |
| Profile setup | Setup gate, successful profile completion, locale persistence, redirect to timeline, account/dashboard unaffected. |
| Timeline | Tenant-scoped query, month grouping, experience-date ordering, empty state, no-results state, cross-tenant exclusion. |
| Record editor | Required validation, diary creation, trip/period creation, highlights persistence, shared editing, unauthorized create/update denial. |
| Record detail | Authorized view, edit link visibility, back-to-timeline navigation, generic cross-tenant denial without content leakage. |
| Filters | Date, tag, and location filtering scoped to tenant; clearing filters; no cross-tenant results; location not persisted to URL by default. |
| Private media | Upload limits, authorized thumbnail/detail access, direct URL denial, cross-tenant denial, protected/unavailable state, no public URL assumptions. |
| Localization | EN/PT/ES UI strings, profile locale preference, no translation of user-generated content, long-label layout checks. |
| Accessibility acceptance checks | Keyboard reachability, visible focus, semantic headings, labeled inputs, non-color-only status, mobile target size. Manual or browser-assisted unless automated tooling is added. |
| Validation reporting | Admin-only access, aggregate signals only, no private record body/title/media leakage in reporting. |

### Testing Best Practices

1. Use PHPUnit test classes only.
2. Use factories and project helper methods for users, tenants, records, tags,
   profiles, and media once they exist.
3. Test happy paths, validation failures, authorization failures, and
   cross-tenant attempts separately.
4. Assert direct URL behavior for record and media routes; do not rely only on
   navigation tests.
5. For denied states, assert both the status/redirect and the absence of
   private titles, excerpts, filenames, media descriptions, and tenant metadata.
6. Use Livewire tests for component state, validation, actions, redirects, and
   emitted/rendered UI state.
7. Follow the repo's current convention for service tests under
   `tests/Feature/Services`; use `tests/Feature/Services/Memory` for memory
   service coverage unless the project deliberately reorganizes tests later.
8. Use service tests for timeline grouping, ordering, filters, highlights, and
   activity reporting logic that can be tested without rendering Blade.
9. Keep media tests storage-safe by using Laravel fake disks or test-specific
   private storage configuration.
10. Conversion, thumbnails, and rendition behavior can be faked or deferred
    until the exact Hostinger/S3-compatible media path is finalized.
11. Accessibility checks are required story acceptance checks, but they are not
    assumed to be automated PHPUnit coverage unless the story adds tooling.
12. Keep localization tests focused on interface strings and locale selection;
    user-generated content must remain unchanged.
13. Run the smallest relevant test file while implementing a story, then run
    broader gates before handoff.

### Test Commands

When already inside the Sail container, use focused `php artisan test` commands:

```bash
php artisan test --compact tests/Feature/Livewire/Memory/MemoryTimelineTest.php
php artisan test --compact tests/Feature/Memory/MemoryRouteAccessTest.php
php artisan test --compact tests/Feature/Services/Memory/MemoryTimelineServiceTest.php
```

From the host, use the existing project scripts or the equivalent Sail/Docker
command path. The full project `npm test` script already starts required
services and runs `php artisan test --compact` inside Docker.

Before story handoff, run the project gates required by `AGENTS.md` when the
story changes executable code:

```bash
npm run lint
npm run typecheck
npm test
```

## Environment Configuration

The memory frontend does not require a separate frontend environment file or
new Vite-exposed variables for the MVP. Configuration should stay server-side
unless a value is already safe and intentionally exposed through the existing
Vite pipeline.

### Existing Environment Values Used by Memory UI

| Variable | Current Source | Memory UI Usage |
| --- | --- | --- |
| `APP_URL` | `.env.example`, `config/app.php` | URL generation for named routes and media responses. |
| `APP_ENV` | `.env.example`, `config/app.php` | Local/testing/production behavior and debug expectations. |
| `APP_DEBUG` | `.env.example`, `config/app.php` | Must be disabled in production so denied states do not expose debug details. |
| `APP_TIMEZONE` | `.env.example`, `config/app.php` | Date display and grouping should be consistent with application timezone rules. |
| `FILESYSTEM_DISK` | `.env.example`, `config/filesystems.php` | Existing default disk; memory media should not assume this is public. |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_DEFAULT_REGION` / `AWS_BUCKET` / `AWS_USE_PATH_STYLE_ENDPOINT` | `.env.example`, `config/filesystems.php` | Future S3-compatible storage path for private memory media if selected by implementation. |
| `QUEUE_CONNECTION` | `.env.example`, `config/queue.php` | Future media conversions or reporting jobs if the story introduces queued work. |
| `SESSION_DRIVER` | `.env.example`, `config/session.php` | Livewire/auth session behavior for authenticated memory screens. |

### Memory-Specific Config Strategy

Do not read `env()` directly from Livewire components, Blade views, services,
controllers, or models. If memory-specific configuration is needed, add a
Laravel config file such as `config/memory.php` and read it through
`config('memory.*')`.

Suggested config keys, only when implementation stories need them:

```php
<?php

return [
    'supported_locales' => ['en', 'pt', 'es'],
    'default_locale' => 'en',

    'media' => [
        'disk' => env('MEMORY_MEDIA_DISK', 'local'),
        'max_image_size_kb' => 10240,
        'max_photos_per_record' => 25,
        'max_workspace_storage_mb' => 2048,
    ],
];
```

If a story adds `MEMORY_MEDIA_DISK`, it should also update `.env.example`,
deployment notes, storage tests, and private-media acceptance criteria. Do not
point memory media at the existing `public` disk.

### Frontend Build Configuration

- Continue using `resources/css/app.css`, `resources/js/app.js`, and Vite.
- Do not add `VITE_MEMORY_*` variables for MVP UI behavior. Route names,
  locale, profile state, and media access should come from Laravel-rendered
  views and Livewire state.
- Keep Tailwind CSS 4 configuration CSS-first through `@theme` only when
  memory-specific tokens are actually added and verified.
- Use the existing `npm run dev` and `npm run build` scripts. Do not introduce a
  second frontend build pipeline.

### Locale Configuration

- English remains the default/fallback application locale.
- Memory profile setup stores the user's preferred interface locale as one of
  `en`, `pt`, or `es`.
- Locale middleware should set `app()->setLocale($profileLocale)` for the
  authenticated memory product surface.
- User-generated memory content must never be translated by configuration or
  middleware.

### Media Configuration

- MVP private media should start with local private storage unless the
  implementation story validates a different Hostinger-compatible path.
- Public blog media and memory media must remain separate in behavior, even if
  they share package infrastructure.
- Memory media should be delivered through authorized routes or equivalent
  private access. Do not rely on `public/storage`.
- If future S3-compatible storage is used, keep credentials in existing AWS/S3
  env variables or narrowly scoped new config values, never in Blade,
  JavaScript, or Livewire public properties.

### Environment Tests

- Tests that depend on media storage should fake or isolate the configured
  memory media disk.
- Tests that depend on locale should set profile preference and assert UI
  strings change while record content remains unchanged.
- Tests should run with `APP_ENV=testing`, `QUEUE_CONNECTION=sync`, and
  `SESSION_DRIVER=array` as configured in `phpunit.xml` unless a story has a
  specific reason to vary those settings.

## Frontend Developer Standards

### Critical Coding Rules

These rules prevent the most likely implementation mistakes for the memory
product. Stories that touch customer-facing memory UI should reference the
applicable rules in acceptance criteria or implementation notes.

1. Use the dedicated memory layout for memory routes.
   `resources/views/components/layouts/memory.blade.php` is the customer-facing
   memory shell. Do not place timeline, editor, detail, or filter screens inside
   the public marketing layout or Filament Dashboard chrome.

2. Keep Filament out of customer memory workflows.
   Filament remains for billing, account, workspace settings, users, roles,
   teams, invitations, security, and admin/reporting surfaces. Timeline,
   editor, detail, filters, and profile setup are Livewire/Blade product UI.

3. Enforce tenant boundaries before rendering private content.
   Routes, middleware, policies, services, and queries must all scope records,
   tags, highlights, and media to the current tenant. Blade templates must not
   decide whether private records are accessible.

4. Re-scope nested models after route binding.
   A bound record, media item, tag, or highlight ID is not safe until the server
   confirms it belongs to the route tenant and the current user is authorized.

5. Never expose private media through public paths.
   Memory photos must use authorized routes or an equivalent private access
   mechanism. Do not use `public/storage`, public blog-media assumptions, or
   raw file paths in Blade, JavaScript, Livewire public properties, or logs.

6. Keep sensitive or free-text filters out of URLs by default.
   Date and tag filters may use `#[Url]` where useful. Location search and
   private record content should remain transient Livewire state unless a story
   explicitly accepts the privacy tradeoff.

7. Treat Livewire public properties as request-visible state.
   Do not put secrets, full private media paths, authorization decisions,
   filenames, or server-only access metadata in public properties.

8. Use services for domain transitions.
   Livewire components can own screen state and actions, but record creation,
   updates, highlights, timeline queries, profile completion, locale changes,
   and activity events should go through memory services.

9. Use Laravel localization for all customer-facing memory UI strings.
   Store memory UI strings in `lang/{locale}/memory.php` or the established
   project translation structure. Do not translate user-generated content.

10. Keep Memory Highlights lightweight.
    Implement highlights as an optional ordered dynamic list with add, remove,
    edit, and accessible reorder controls where supported. Do not introduce AI
    classification, inline annotation, or technical metadata language in MVP UI.

11. Preserve the timeline-first product model.
    The timeline home is the first meaningful memory screen after profile
    setup. It remains vertical, month-grouped, and ordered by experience date
    or period start date. Do not implement masonry, maps, complex animation, or
    table/list admin framing for MVP.

12. Keep record editors writing-first.
    Diary body and experience date lead the diary form. Trip/period title,
    location, and dates lead the period form. Optional metadata must not
    visually dominate writing and capture.

13. Use neutral `Memory*` names.
    Do not hard-code the domain as `Missionary*` in models, services,
    components, routes, or translation namespaces. Missionaries are the first
    validation niche, not the permanent product boundary.

14. Build for English, Portuguese, and Spanish layout tolerance.
    Buttons, filters, cards, navigation, empty states, and access-denied states
    must handle supported-language label expansion, including mobile width
    checks around `375px`.

15. Use visible text for primary commands and appropriate icons for utilities.
    Create, save, and continue actions need visible labels. Utility actions
    such as filter, upload, edit, language, dashboard escape, close, remove, and
    reorder may use familiar icons with labels, accessible names, or tooltips.

16. Do not add new dependencies for MVP UI without explicit approval.
    Use Blade, Livewire, Alpine.js, Tailwind CSS, DaisyUI, Vite, and existing
    Laravel packages unless a story proves the current stack cannot support a
    requirement.

17. Test every privacy boundary touched by a story.
    Stories that touch records, media, routes, filters, collaboration, locale,
    or reporting must include focused tests for happy path, validation failure,
    authorization failure, and cross-tenant denial where relevant.

18. Denied states must not leak private data.
    Generic denial or not-found responses must not include private record
    titles, body excerpts, media descriptions, filenames, tenant-private
    metadata, or internal storage details.

19. Do not create persistent drafts unless scoped by a story.
    Unsaved Livewire form state is transient. Autosave, draft persistence, or
    browser-local persistence is outside MVP unless explicitly added with
    privacy and recovery tests.

20. Keep frontend changes story-scoped.
    Do not refactor public marketing pages, blog cards, Filament panels, global
    theme tokens, or unrelated SaaSykit components unless a story directly
    requires it.

### Quick Reference

#### Primary Files and Directories

| Area | Path |
| --- | --- |
| Memory routes | `routes/web.php` |
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

#### Common Commands

```bash
php artisan make:livewire Memory/MemoryTimeline --class --no-interaction
php artisan make:livewire Memory/MemoryRecordEditor --class --no-interaction
php artisan make:livewire Memory/MemoryRecordDetail --class --no-interaction
php artisan make:test Livewire/Memory/MemoryTimelineTest --phpunit --no-interaction
php artisan make:test Memory/MemoryRouteAccessTest --phpunit --no-interaction
php artisan test --compact tests/Feature/Livewire/Memory/MemoryTimelineTest.php
npm run lint
npm run typecheck
npm test
```

#### Route Names

| Route | Purpose |
| --- | --- |
| `memories.profile.setup` | Memory profile setup gate |
| `memories.timeline` | Tenant-scoped timeline home |
| `memories.records.create` | Diary or trip/period creation |
| `memories.records.show` | Record detail |
| `memories.records.edit` | Record editing |
| `memories.media.show` | Authorized private media delivery |

#### Component Ownership

| Concern | Owner |
| --- | --- |
| Screen state | Livewire component |
| Domain persistence | Service |
| Tenant/record/media authorization | Middleware and policies |
| Private media stream | Controller or equivalent authorized route |
| Visual fragments | Blade components |
| UI strings | Laravel translation files |
| Account/billing/workspace operations | Existing Filament Dashboard |

#### MVP Do Not Build List

- Filament Resources for customer-facing memory timeline/editor/detail.
- Public marketing layout, hero, footer, or blog-card timeline UI.
- Public media URLs for memory photos.
- JSON API or frontend HTTP client for MVP memory CRUD.
- Client-side store for memory domain state.
- Per-record missionary-only versus family-visible controls.
- Social feed, public sharing, visitor access, or friend access.
- Album generation UI or album-facing language.
- AI extraction, inline annotation, or second-brain graph UI.
- Masonry, maps, complex timeline animation, or advanced grouping.

## Implementation Handoff

Use this document with `docs/prd.md`, `docs/architecture.md`, and
`docs/front-end-spec.md` when preparing implementation stories.

Recommended story sequence:

1. Tenant-safe memory route group and dedicated memory layout shell.
2. Memory profile setup gate with preferred locale.
3. Timeline shell with empty state and tenant boundary tests.
4. Diary record creation and editing without photo complexity.
5. Record detail and month-grouped timeline cards.
6. Trip/period record editor.
7. Private media upload and authorized media delivery.
8. Timeline filters by date, tag, and location.
9. EN/PT/ES UI localization and mobile layout tolerance.
10. Admin-only validation reporting or runbook support.

Every story should preserve the dedicated memory layout, tenant/workspace
privacy, timeline-first behavior, private media constraints, localization
rules, accessibility acceptance checks, and no-admin-table customer UI boundary
defined above.
