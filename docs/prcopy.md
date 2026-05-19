# Life Memory Timeline for Missionaries Product Requirements Document (PRD)

## Status

Draft complete for product review. This PRD is derived from `docs/brief.md` and was completed in YOLO mode through the AIOX PM workflow.

## Goals and Background Context

### Goals

- Deliver a private-first, timeline-centered MVP for preserving meaningful life memories.
- Validate missionaries and their families as the first niche without hard-coding the product around missions.
- Make the timeline the primary product experience and first meaningful screen after profile setup.
- Support lightweight capture through diary records and trip/period records.
- Keep Memory Highlights simple and human, avoiding technical metadata work in the MVP.
- Support a private family workspace model where records are protected by tenant/workspace boundaries, without per-record missionary-vs-family visibility controls in the MVP.
- Allow the owner and collaborator to both create and edit workspace records in the MVP, while preserving a future workspace-level collaboration mode setting.
- Support English, Portuguese, and Spanish interface and navigation localization without translating user-generated content.
- Validate emotional value with real missionary/family testers before expanding scope.

### Background Context

Life Memory Timeline for Missionaries is a private-first memory product experience inspired by missionary life. The original insight came from the difficulty of preserving mission experiences that are scattered across emails, photos, notes, calls, messages, and family conversations. Missionaries and families are still the first validation niche because mission life creates urgency, emotional value, and repeated memory-capture moments.

The broader Life Memory Timeline platform direction is not a missionary-only journal. The long-term product is a personal and family memory timeline where users can preserve facts, experiences, places, periods, people, trips, celebrations, and meaningful moments. The MVP should prove the core timeline model with a narrow missionary scope: personal profile setup, timeline-first home, diary records, trip/period records, photos, family workspace access, manual email copy/paste for the missionary niche, and simple Memory Highlights. Structured email-source tracking is a non-blocking should-have.

### Change Log

| Date | Version | Description | Author |
| --- | --- | --- | --- |
| 2026-05-17 | 0.1 | Initial PRD draft from approved project brief through Requirements section. | Morgan |
| 2026-05-17 | 0.2 | Completed UI goals, technical assumptions, epic structure, checklist report, and next-step prompts in YOLO mode. | Morgan |
| 2026-05-17 | 0.3 | Removed missionary-vs-family record visibility from MVP/roadmap and clarified tenant/workspace access model. | Morgan |
| 2026-05-17 | 0.4 | Clarified two-user family membership, owner invite flow, and minimum validation metrics. | Morgan |
| 2026-05-17 | 0.5 | Added architecture review notes after inspecting the existing Laravel/SaaSykit Tenancy structure. | Aria |
| 2026-05-17 | 0.6 | Aligned brief/PRD/architecture on shared editing, future collaboration modes, Hostinger-to-S3 media path, admin-only reporting, and UI-only localization. | Aria |
| 2026-05-17 | 0.7 | Set the validation-facing name to Life Memory Timeline for Missionaries while preserving Life Memory Timeline as the broader platform direction. | Aria |
| 2026-05-17 | 0.8 | Confirmed `/memories/{tenant:uuid}` as the initial customer-facing product route group. | Aria |
| 2026-05-17 | 0.9 | Defined Memory Highlights as an optional dynamic "Add highlight" list with ordered item storage. | Aria |
| 2026-05-17 | 0.10 | Downgraded structured email-source tracking to non-blocking should-have and moved inline diary-text highlight selection to roadmap. | Aria |
| 2026-05-17 | 0.11 | Defined the MVP timeline as a vertical month-grouped layout with simple memory cards and no masonry/maps/complex animation. | Aria |

### MVP Validation Metrics

- At least three tenant/workspace accounts participate in validation.
- Each tenant/workspace records activity across more than two weeks.
- Each missionary creates records from at least two weekly contact moments, such as weekly family contact or weekly mission email updates.
- Validation observes whether copied mission email content becomes real diary records; structured source tracking is useful but not required for MVP readiness.
- Family members or collaborators report whether centralized access makes the mission easier to follow, revisit, and preserve.

## Requirements

### Functional

- **FR1:** Users can create an account, log in, and access the product securely.
- **FR2:** After registration, users complete a lightweight personal profile before landing on the timeline.
- **FR3:** The product must not require mission setup as the global organizing object; mission context is optional and belongs to the first validation niche.
- **FR4:** After profile setup, users land on a timeline-first home as the primary product surface.
- **FR5:** The first paid tier supports a two-user family workspace for the initial validation niche: one owner and one collaborator.
- **FR6:** Users can create diary records with body text, experience date, optional location, tags, photos, and optional Memory Highlights.
- **FR7:** Users can edit existing diary records as memories are refined or corrected.
- **FR8:** Users can create trip/period records with title, location, start date, end date, people, photos, and notes.
- **FR9:** Users can open an individual record view to read the complete timeline record.
- **FR10:** The timeline displays records ordered by experience date, not by creation date.
- **FR11:** The timeline groups records by month with clear visual month separators.
- **FR12:** Timeline cards show date or period, optional location, primary photo, emotional excerpt, simple tags, photo count, and a clear action to open the full record.
- **FR13:** Users can filter records by date, tag, and location.
- **FR14:** Users can upload photos to records and keep photos connected to the memory being recorded.
- **FR15:** All MVP records are private to the tenant/workspace by default and are not exposed publicly or socially.
- **FR16:** The owner can invite one collaborator into the same workspace, using existing SaaSykit tenancy controls if they already support the required invitation and membership behavior.
- **FR17:** The owner and collaborator can both create and edit workspace records in the MVP.
- **FR18:** The architecture preserves a workspace-level collaboration mode setting for future selectable modes such as shared editing, owner review, or read-only collaborator access.
- **FR19:** The MVP must not include per-record visibility controls that classify records as missionary-only vs family-visible.
- **FR20:** Granular missionary-vs-family record access remains backlog-only and is outside both the MVP and the active roadmap.
- **FR21:** The interface and user navigation support English, Portuguese, and Spanish, with English as the default.
- **FR22:** Users can select their preferred interface language during registration/profile setup and later from their profile.
- **FR23:** User-generated content remains in the language entered by the user and is not translated in the MVP.
- **FR24:** Users can copy content from mission emails into a structured diary record.
- **FR25:** Users can save Memory Highlights through an optional dynamic list with an “Add highlight” action.
- **FR26:** Memory Highlights allow users to save important names, scriptures, lessons, quotes, or moments without classifying them technically in the MVP.

### Should Have (Non-blocking)

- **SH1:** Records created from copied mission emails can be marked as email-sourced for validation reporting if implementation capacity allows.
- **SH2:** Email-source tracking must remain simple, using a source marker rather than email parsing, recipient capture, or header storage.

### Non Functional

- **NFR1:** The product must be private by default and avoid accidental public exposure of records or media.
- **NFR2:** Authorization must consistently scope records and media by workspace or tenant.
- **NFR3:** The product must treat names, photos, locations, family context, religious context, and other personal data as sensitive, with LGPD readiness in mind.
- **NFR4:** Media access rules must be explicit and aligned with tenant/workspace boundaries and parent-record access.
- **NFR5:** Timeline browsing, record creation, and filtering should feel fast for a small active workspace.
- **NFR6:** Photo uploads must be reliable, private, and web-optimized, with initial validation limits of 2 GB per workspace, 10 MB per image, and 25 photos per record.
- **NFR7:** The MVP should validate private local storage on Hostinger while keeping media storage portable to S3-compatible object storage such as AWS S3 or Cloudflare R2.
- **NFR8:** Served image renditions should be generated for responsive web use, with metadata stripped from served files and originals kept private for reprocessing while quota allows.
- **NFR9:** The MVP must work in modern responsive web browsers on desktop, tablet, and mobile web; native mobile apps are out of scope.
- **NFR10:** The customer-facing timeline must feel modern, clean, emotional, and memory-first rather than administrative or CRUD-like.
- **NFR11:** The product must not open the main experience as a table or admin list.
- **NFR12:** The MVP timeline must avoid overbuilt visual patterns such as masonry, maps, complex animations, or advanced grouping beyond month separators.
- **NFR13:** UI layouts must be safe for English, Portuguese, and Spanish strings without breaking buttons, filters, cards, month separators, or empty states.
- **NFR14:** The MVP must avoid visible album-creation language, even though the data model may preserve future curation readiness.
- **NFR15:** MVP validation reporting must be admin-only and must not expose private record content to workspace users.
- **NFR16:** The MVP must exclude automated inbound email capture, AI metadata extraction, public feeds, social-follow behavior, friend/visitor access, granular missionary-vs-family record visibility, inline diary-text selection for Memory Highlights, selectable collaboration modes beyond shared editing, full album generation, print-ready album layout, second-brain behavior, advanced life graphs, and additional record types beyond diary and trip/period records.
- **NFR17:** New dependencies should be avoided unless clearly necessary for MVP validation.
- **NFR18:** The implementation should follow existing SaaSykit/Laravel conventions, including Laravel, Livewire, Filament where appropriate, Spatie Media Library, policies/gates, service-layer organization, and tenant/workspace scoping.

## User Interface Design Goals

### Overall UX Vision

The product experience should feel like a private memory timeline, not an administrative content manager. After a lightweight profile setup, the first meaningful experience is a timeline that helps users rediscover people, places, dates, trips, lessons, photos, and emotional fragments from lived experience.

The interface should prioritize calm capture and meaningful browsing. Record creation should feel writing-first, with structure available around the memory rather than imposed before the user can write. The visual direction should be modern, clean, elegant, emotionally appropriate, and safe for repeated use by missionaries and families.

### Key Interaction Paradigms

- Guided first-run profile setup before timeline access.
- Timeline-first browsing with cards ordered by experience date and grouped by month.
- Writing-first diary creation with supporting fields for date, location, tags, photos, and Memory Highlights.
- Optional email-source marker when included as a non-blocking validation enhancement.
- Structured trip/period creation for memories that span multiple days or locations.
- Private workspace access that relies on tenant/workspace membership rather than per-record missionary-vs-family sharing controls.
- Shared editing where the owner and collaborator can both create and edit records in the MVP.
- Optional Memory Highlights captured through a simple dynamic "Add highlight" list rather than technical annotation.
- Record detail views that preserve the full story behind each timeline fragment.
- Filters that narrow the timeline by date, tag, and location without turning the experience into a table.
- Family workspace paths that rely on authenticated tenant/workspace membership.

### Core Screens and Views

- Authentication and registration screens.
- Personal profile setup.
- Timeline home with empty, loading, populated, month-grouped, and filtered states.
- Diary record create/edit view.
- Trip/period record create/edit view.
- Individual record detail view.
- Timeline filter controls.
- Family workspace timeline/read/edit view.
- Profile and language settings.
- Access-denied and private-record boundary states.

### Accessibility: WCAG AA

The MVP should target WCAG AA as the product baseline. This is appropriate because the product is family-facing, text-heavy, emotionally important, and likely to be used across different ages and devices. The most important early requirements are keyboard-accessible forms and controls, readable contrast, clear focus states, predictable navigation, and layouts that do not break with English, Portuguese, or Spanish strings.

### Branding

The validation-facing brand direction is Life Memory Timeline for Missionaries. The broader platform direction remains Life Memory Timeline. The design should feel private, warm, organized, and trustworthy without becoming nostalgic decoration or an album product. The MVP should not use visible album-creation language. Album readiness may remain a future curation capability, but the customer-facing MVP language should focus on timeline, records, memories, highlights, family workspace access, and preservation.

### Target Device and Platforms: Web Responsive

The MVP target is a responsive web application for modern desktop, tablet, and mobile browsers. Native mobile apps are out of scope. Mobile web must support the core create, edit, browse, filter, and workspace-member read workflows, but it does not need native-device behaviors beyond reliable browser-based photo upload.

## Technical Assumptions

### Repository Structure: Monorepo

The product will be built inside the existing Laravel/SaaSykit repository. Backend, customer-facing web views, Livewire components, Filament resources where appropriate, tests, and documentation remain in this repo following existing conventions.

### Service Architecture

The MVP should be implemented as a Laravel monolith within the existing SaaSykit tenancy architecture. Timeline behavior should be organized around tenant/workspace-scoped domain models, policies, and services rather than separate services or microservices.

Customer-facing memory workflows should use Laravel, Blade, Livewire, Alpine.js, Tailwind CSS, and existing application conventions. Filament may be used for administrative/internal management, but the main customer timeline should not feel like a Filament table or admin CRUD surface. Spatie Media Library should be used for record photos, with explicit private/media access aligned to tenant/workspace boundaries and image conversions for responsive web delivery.

### Testing Requirements: Unit + Integration

The MVP should use PHPUnit with targeted feature, service, policy, and Livewire coverage. Privacy and tenant/workspace scoping need stronger coverage than visual polish because they protect sensitive records and media. Full browser end-to-end coverage is useful later, but the first MVP should prioritize fast local verification for registration/profile setup, timeline access, record creation/editing, workspace access boundaries, filters, localization-safe routes/views, and photo attachment behavior.

### Additional Technical Assumptions and Requests

- Timeline records should support at least two MVP record types: diary and trip/period.
- The initial customer-facing product route group should use `/memories/{tenant:uuid}`.
- Records must be ordered by experience date or period, not creation timestamp.
- The MVP timeline should be a vertical month-grouped layout with simple memory cards.
- Do not build masonry, map-based browsing, complex animations, or advanced grouping in the MVP timeline.
- All MVP records should remain private to the tenant/workspace unless a future feature explicitly introduces external sharing.
- SaaSykit tenancy and workspace membership are the primary access boundary for missionary/family records in the MVP.
- The MVP family workspace has two users: one owner and one collaborator.
- The owner should be able to invite the collaborator; architecture should verify whether SaaSykit tenancy already provides the required invitation and membership control before adding custom behavior.
- Collaborators in the same workspace can access, create, and edit workspace records according to the existing tenant/workspace membership model.
- The initial collaboration mode is `shared_editing`; future selectable collaboration modes should be modeled at the workspace level, not on individual records.
- The MVP should not implement record-level missionary-only vs family-visible classification.
- Granular missionary-vs-family record access remains backlog-only and outside the active roadmap until validation justifies revisiting it.
- Social, friends, visitor, and external-recipient access are roadmap concepts and must not drive the MVP access model.
- Manual email copy/paste should be possible by pasting content into diary records. A simple source marker is a non-blocking should-have for validation, not a must-have.
- Memory Highlights should be stored as ordered structured items from the dynamic list, even if later AI workflows classify them into people, places, scriptures, quotes, lessons, feelings, themes, or moments.
- Inline diary-text selection for Memory Highlights belongs on the roadmap and should reuse the same `memory_highlights` storage model.
- UI localization should use Laravel localization files for English, Portuguese, and Spanish, with English as the default. Users should select language during registration/profile setup and can change it in their profile. User-generated content should not be translated.
- Photo storage should validate on Hostinger using private local storage while preserving a future S3-compatible disk path for AWS S3 or Cloudflare R2.
- Initial media limits are 2 GB per workspace, 10 MB per image, and 25 photos per record for validation.
- Photo handling should generate web renditions, strip metadata from served versions, avoid public storage URLs, and keep originals private for reprocessing while quota allows.
- Validation reporting should be available only to admins/internal operators, not workspace users.
- Avoid new dependencies unless the existing stack cannot deliver a required validation outcome.
- Architecture should preserve future optionality for AI metadata extraction, inbound email capture, social/friend/visitor access, selective sharing, and curated artifacts without building those features in the MVP.

### Architecture Review Notes

The architecture review confirmed that the MVP should reuse the existing SaaSykit Tenancy workspace model instead of introducing a separate collaboration system. The existing codebase already provides tenants, tenant-user membership, tenant roles, invitations, plan-based user limits, and seat-aware pricing. The two-user validation workspace should therefore be enforced through the SaaSykit tenant and plan configuration, with one owner and one invited collaborator. The MVP collaboration mode is shared editing, but the architecture should include a workspace-level setting so future collaboration modes can be selected without changing the memory record model.

Life Memory Timeline for Missionaries memory features should be implemented as a neutral memory-domain enhancement inside the existing Laravel monolith. Customer-facing timeline, capture, and record-detail screens should use custom Livewire and Blade UI so the product feels memory-first rather than administrative. Existing Filament Dashboard capabilities should remain responsible for billing, workspace settings, users, roles, teams, and invitations.

Private media requires explicit implementation work. Existing public blog media usage is not an acceptable pattern for memory photos. Record photos should use Spatie Media Library on a private disk or private path and should be served only through authorized tenant/workspace media routes. The MVP should validate on Hostinger with local private storage, generate web-optimized renditions, and keep a future S3-compatible path for AWS S3 or Cloudflare R2.

Validation reporting should be admin-only. Workspace users should not see product validation dashboards or behavior summaries.

The current architecture source of truth is `docs/architecture.md`.

## Epic List

- **Epic 1: Profile, Workspace Access, and Timeline Shell:** Establish the authenticated memory workspace, lightweight profile setup, private-by-default primitives, and the first timeline home so users reach the product's aha moment quickly.
- **Epic 2: Timeline Record Capture:** Enable users to create and edit diary and trip/period records with photos, optional source tracking, and simple Memory Highlights.
- **Epic 3: Memory Timeline Browsing and Retrieval:** Turn captured records into a useful emotional timeline with ordered cards, record detail views, and filters by date, tag, and location.
- **Epic 4: Workspace Privacy and Tenant Boundaries:** Enable family workspace access through SaaSykit tenancy while excluding per-record missionary-vs-family visibility from the MVP.
- **Epic 5: Localization, Validation, and MVP Readiness:** Prepare the MVP for real validation with English/Portuguese/Spanish UI, behavior tracking, privacy/media checks, and focused acceptance coverage.

## Epic 1 Profile, Workspace Access, and Timeline Shell

Epic 1 gets users from authentication into a credible memory product surface without requiring mission setup as the global organizing object. It establishes the minimum workspace, profile, privacy, and timeline shell needed before record capture begins.

### Story 1.1 Authenticated Memory Workspace Entry

As a registered user,
I want secure access to my memory workspace,
so that my private records are scoped to the correct account or workspace.

#### Acceptance Criteria

1. Authenticated users can access the memory product area.
2. Unauthenticated visitors are redirected to authentication before accessing memory routes.
3. The product area resolves the correct tenant or workspace context for the user.
4. The first memory route does not expose another user's or workspace's data.
5. Automated tests verify authenticated access, unauthenticated redirect, and workspace scoping.

### Story 1.2 Lightweight Personal Profile Setup

As a new user,
I want to complete a simple profile before using the timeline,
so that the product can personalize the memory space without forcing mission-specific setup.

#### Acceptance Criteria

1. A new user without a completed profile is routed to profile setup before the timeline.
2. Profile setup captures only MVP-critical personal information.
3. Mission context is optional and does not block completion.
4. Completing setup redirects the user to the timeline home.
5. Automated tests verify setup gating and successful completion.

### Story 1.3 Timeline Home Shell

As a user with a completed profile,
I want to land on the memory timeline,
so that the primary product experience is immediately clear.

#### Acceptance Criteria

1. The timeline home is the first meaningful screen after profile setup.
2. The empty timeline state encourages creating a diary record or trip/period record.
3. The timeline home is not rendered as an admin table or CRUD list.
4. The timeline shell uses a vertical layout with month separators when records exist.
5. The page includes clear entry points for creating MVP record types.
6. The timeline shell works responsively on desktop, tablet, and mobile web.

### Story 1.4 Workspace-Private Access Foundation

As a user,
I want new memories to remain inside my family workspace,
so that sensitive records are not accidentally exposed outside the tenant boundary.

#### Acceptance Criteria

1. MVP records are private to the tenant/workspace by default.
2. The MVP does not expose missionary-only vs family-visible record controls.
3. Record access is available to policies, views, and media rules through tenant/workspace membership.
4. The interface avoids per-record family sharing labels in the MVP.
5. Automated tests verify tenant/workspace access boundaries.

## Epic 2 Timeline Record Capture

Epic 2 delivers the core capture workflows that create value in the timeline. Users can write diary memories, add trip or period records, attach photos, optionally mark copied email content if the non-blocking enhancement is included, and save optional Memory Highlights without turning capture into technical metadata work.

### Story 2.1 Diary Record Creation

As a user,
I want to create a diary record,
so that I can preserve a meaningful experience while it is fresh.

#### Acceptance Criteria

1. Users can create a diary record with body text and experience date.
2. Users can optionally add location, tags, photos, and Memory Highlights.
3. Diary records remain private to the tenant/workspace by default.
4. The form prioritizes writing and does not require technical classification.
5. Automated tests verify successful creation and required validation.

### Story 2.2 Diary Record Editing

As a user,
I want to edit an existing diary record,
so that I can refine or correct a memory later.

#### Acceptance Criteria

1. Authorized users can open an existing diary record for editing.
2. Users can update text, experience date, location, tags, photos, and Memory Highlights.
3. Unauthorized users cannot edit records outside their allowed scope.
4. Editing preserves record identity and timeline placement updates when experience date changes.
5. Automated tests verify authorized update and unauthorized denial.

### Story 2.3 Trip or Period Record Creation

As a user,
I want to create a trip or period record,
so that multi-day memories can appear naturally on the timeline.

#### Acceptance Criteria

1. Users can create a trip/period record with title, location, start date, end date, people, photos, and notes.
2. Start and end dates support timeline period display.
3. Trip/period records remain private to the tenant/workspace by default.
4. The form is structured and faster than a long-form diary entry.
5. Automated tests verify successful creation and date validation.

### Story 2.4 Photo Attachments for Records

As a user,
I want to attach photos to records,
so that the timeline preserves both images and the story around them.

#### Acceptance Criteria

1. Users can attach photos to diary records.
2. Users can attach photos to trip/period records.
3. Photos remain connected to their parent record.
4. Media access follows tenant/workspace boundaries and parent-record access rules.
5. Automated tests verify attachment behavior and unauthorized media access denial where feasible.

### Story 2.5 Memory Highlights and Optional Email Source Marker

As a missionary user,
I want to save simple highlights and optionally mark copied email content,
so that future capture and AI decisions are based on real behavior.

#### Acceptance Criteria

1. Users can add Memory Highlights through an optional dynamic list with an “Add highlight” action.
2. Users can add, edit, remove, and reorder highlight items before saving the record.
3. Highlight language uses human terms such as important names, scriptures, lessons, quotes, or moments.
4. Highlights do not require technical metadata categories in the MVP.
5. If the non-blocking email-source marker is included, a diary record can be marked as sourced from copied mission email content.
6. If the email-source marker is included, email-sourced records are trackable for admin-only validation reporting.

## Epic 3 Memory Timeline Browsing and Retrieval

Epic 3 turns records into the product's core aha moment. The timeline should feel like an emotional memory surface, with useful fragments, visual context, filtering, and complete detail views.

### Story 3.1 Experience-Date Timeline Ordering

As a user,
I want memories ordered by when they happened,
so that the timeline reflects lived experience rather than data entry time.

#### Acceptance Criteria

1. Timeline records are ordered by experience date or period.
2. Timeline records are grouped under month separators based on experience date or period start.
3. Diary records display as dated moments.
4. Trip/period records display as period-based memories.
5. Creation timestamp does not control primary timeline order.
6. Automated tests verify ordering and month grouping across diary and trip/period records.

### Story 3.2 Memory Timeline Cards

As a user,
I want timeline cards to show meaningful fragments,
so that I can rediscover memories without opening every record.

#### Acceptance Criteria

1. Timeline cards show date or period, optional location, and record type context.
2. Cards show a primary photo when available.
3. Cards show an emotional excerpt or useful text fragment.
4. Cards show simple tags, photo count, and record type context without per-record family visibility labels.
5. Cards include a clear action to open the full record.
6. Cards remain simple and do not use masonry, maps, complex animations, or advanced visual grouping.

### Story 3.3 Individual Record Detail View

As a user,
I want to open a full record from the timeline,
so that I can read the complete memory and see its photos and highlights.

#### Acceptance Criteria

1. Users can open a diary record detail view from a timeline card.
2. Users can open a trip/period record detail view from a timeline card.
3. Detail views show complete text/notes, date or period, location, tags, photos, and highlights.
4. Authorized users can access edit actions from the detail view.
5. Unauthorized users cannot view private records.

### Story 3.4 Timeline Filters

As a user,
I want to filter the timeline by date, tag, and location,
so that I can find memories without scrolling through everything.

#### Acceptance Criteria

1. Users can filter records by date or date range.
2. Users can filter records by tag.
3. Users can filter records by location.
4. Filters can be cleared to restore the full allowed timeline.
5. Automated tests verify filtered results and tenant/workspace scoping.

## Epic 4 Workspace Privacy and Tenant Boundaries

Epic 4 validates the family stakeholder through the existing SaaSykit tenancy and workspace model instead of introducing per-record missionary-vs-family visibility. The MVP protects records between tenants/workspaces, allows owner/collaborator shared editing, and keeps social, friend, visitor, and granular family visibility outside the build.

### Story 4.1 Family Workspace Membership

As a missionary or workspace owner,
I want to invite one collaborator into my workspace,
so that the family can preserve and revisit records inside one private tenant boundary.

#### Acceptance Criteria

1. The MVP supports a two-user family workspace: one owner and one collaborator.
2. The owner can invite one collaborator into the workspace.
3. Architecture or implementation verifies whether SaaSykit tenancy already provides the required invite and membership controls before custom behavior is added.
4. The collaborator authenticates before accessing the workspace.
5. The collaborator is associated with the correct tenant/workspace.
6. Workspace members can access workspace records through the membership model.
7. The owner and collaborator can both create and edit workspace records under the MVP `shared_editing` mode.
8. Automated tests verify owner invitation, collaborator access, shared editing, and workspace membership scoping.

### Story 4.2 Tenant Boundary Enforcement

As a user,
I want records protected between workspaces,
so that another family or tenant cannot access my records or media.

#### Acceptance Criteria

1. Users cannot list records from another tenant/workspace.
2. Users cannot open record detail views from another tenant/workspace.
3. Users cannot edit records from another tenant/workspace.
4. Empty and restricted states do not leak private record content.
5. Automated tests verify cross-tenant denial for listing, reading, and editing.

### Story 4.3 No Per-Record Missionary-vs-Family Visibility

As a product team,
I want the MVP to avoid missionary-only vs family-visible record classification,
so that access control stays simple and unambiguous during validation.

#### Acceptance Criteria

1. Record create/edit forms do not ask users to classify records as missionary-only or family-visible.
2. Timeline cards do not display missionary-only or family-visible labels.
3. Record detail views do not display missionary-only or family-visible labels.
4. Requirements for granular missionary-vs-family access are documented as backlog-only and outside the active roadmap.
5. Social, friend, visitor, and external-recipient access remains excluded from the MVP.

### Story 4.4 Private Media Boundary

As a user,
I want record media protected by workspace access,
so that photos attached to memories are not exposed outside the family workspace.

#### Acceptance Criteria

1. Record media access follows tenant/workspace boundaries.
2. Users outside the workspace cannot access attached photos.
3. Workspace users can view media attached to accessible workspace records.
4. Media access checks do not rely on removed missionary-only vs family-visible states.
5. Tests cover authorized workspace media access and unauthorized cross-workspace denial.

## Epic 5 Localization, Validation, and MVP Readiness

Epic 5 prepares the MVP for real testers and handoff to architecture, implementation, and QA. It keeps the MVP narrow while making sure the validation niche can actually use it in English, Portuguese, and Spanish.

### Story 5.1 English, Portuguese, and Spanish UI Localization

As a user,
I want to use the interface in my preferred supported language,
so that record capture and timeline browsing are understandable.

#### Acceptance Criteria

1. MVP UI strings are available in English, Portuguese, and Spanish.
2. English is the default interface language.
3. Users can select their preferred language during registration/profile setup and later in their profile.
4. User-generated content remains in the language entered by the user.
5. Language selection or preference affects product UI and navigation strings.
6. Core forms, buttons, filters, cards, navigation, and empty states fit supported languages.
7. Localization does not introduce content translation behavior.

### Story 5.2 MVP Validation Signals

As a product team,
I want to track core MVP behaviors,
so that validation decisions are based on real usage.

#### Acceptance Criteria

1. The MVP can report whether a workspace completed profile setup.
2. The MVP can report first record creation.
3. The MVP can report whether at least three tenants/workspaces are active in validation.
4. The MVP can report whether each validation tenant/workspace records activity across more than two weeks.
5. The MVP can collect qualitative feedback on whether copied mission email content becomes diary records.
6. The MVP can support validation of family workspace record views.
7. Validation reporting is available only to admins/internal operators.
8. Validation reporting does not expose private record content unnecessarily.

### Story 5.3 Media, Privacy, and Performance Readiness

As a product team,
I want media, privacy, and performance risks checked before tester use,
so that the MVP is safe enough for sensitive memories.

#### Acceptance Criteria

1. Record media follows tenant/workspace access expectations.
2. Timeline browsing, creation, and filtering feel fast for a small active workspace.
3. Storage assumptions use the initial validation limits of 2 GB per workspace, 10 MB per image, and 25 photos per record.
4. Hostinger private local storage is validated without exposing memory media through public storage URLs.
5. The media layer remains portable to S3-compatible object storage such as AWS S3 or Cloudflare R2.
6. Private media access is checked against unauthorized access paths.
7. Relevant feature and policy tests pass before validation.

### Story 5.4 MVP Acceptance Runbook

As a product team,
I want a concise validation runbook,
so that missionary/family testers can prove whether the MVP is working.

#### Acceptance Criteria

1. The runbook covers registration, profile setup, language selection, diary creation, trip/period creation, photo attachment, timeline browsing, filters, tenant/workspace access, family workspace access, and shared editing.
2. The runbook includes the target of at least three tenants/workspaces participating in validation.
3. The runbook verifies that each tenant/workspace records activity across more than two weeks.
4. The runbook captures whether missionaries create records from weekly contact moments, such as weekly family contact or weekly mission email updates.
5. The runbook captures emotional validation from collaborators or family members.
6. The runbook captures qualitatively whether copied mission email content becomes real diary records.
7. The runbook separates MVP validation from post-MVP feature requests.

## Checklist Results Report

### Executive Summary

- **Overall PRD completeness:** 90%
- **MVP scope appropriateness:** Slightly large but coherent for a focused three-week validation if implementation cuts stay disciplined.
- **Readiness for story phase:** Ready for SM/PO story preparation after architecture alignment.
- **Most critical concerns:** Tenant/workspace access boundaries, private media handling, shared editing policy coverage, and timeline scope pressure require architectural attention.

### Category Analysis

| Category | Status | Critical Issues |
| --- | --- | --- |
| 1. Problem Definition & Context | PASS | Problem, audience, and validation niche are clear. |
| 2. MVP Scope Definition | PARTIAL | Scope is well-bounded but still ambitious for three weeks. |
| 3. User Experience Requirements | PASS | Timeline-first UX and non-admin direction are explicit. |
| 4. Functional Requirements | PASS | Core MVP behavior is testable and mapped to epics. |
| 5. Non-Functional Requirements | PASS | Privacy, media limits, Hostinger validation path, and S3-compatible future path are defined. |
| 6. Epic & Story Structure | PASS | Epics are sequential and deliver incremental value. |
| 7. Technical Guidance | PASS | Existing Laravel/SaaSykit constraints are documented. |
| 8. Cross-Functional Requirements | PARTIAL | Localization and privacy are covered; operational monitoring detail remains light. |
| 9. Clarity & Communication | PASS | Terminology is consistent and MVP exclusions are explicit. |

### Top Issues by Priority

- **HIGH:** Reuse SaaSykit tenancy for the owner-to-collaborator invitation flow and cover shared editing through memory policies/services.
- **HIGH:** Architect private media access carefully so record photos cannot leak across tenant/workspace boundaries.
- **HIGH:** Reconfirm the three-week scope with implementation estimates; trip/period records, photo uploads, filters, localization, and family workspace access may need sequencing discipline.
- **MEDIUM:** Validate the initial media limits and conversion approach against the Hostinger environment before tester use.
- **MEDIUM:** Define the minimum validation reporting mechanism for three active tenants/workspaces over more than two weeks without building analytics infrastructure beyond MVP needs.

### MVP Scope Assessment

The MVP should not cut the timeline-first home, diary records, private tenant/workspace access, shared editing, photo attachment, or experience-date ordering because those are central to validation. Per-record missionary-vs-family visibility should remain cut from the MVP and outside the active roadmap. If scope must shrink further, the safest cuts are advanced filter polish, trip/period editing depth, detailed admin validation report UI, and structured email-source tracking. Manual email copy/paste should remain possible as normal diary content because it directly validates a major post-MVP bet without building inbound automation.

### Technical Readiness

The PRD is ready for story preparation because it gives clear platform constraints, product boundaries, privacy requirements, architecture direction, and implementation sequencing. Story preparation should preserve the tenant/workspace scoping, the two-user owner/collaborator workspace model, shared editing policies, private media access, localization approach, and a build sequence that produces a usable increment before adding optional polish.

### Recommendations

- Keep the first build sequence centered on profile setup, timeline shell, diary records, and tenant/workspace privacy before expanding record types.
- Use the existing SaaSykit invitation and membership flow if it satisfies the two-user owner/collaborator MVP model.
- Do not implement missionary-only vs family-visible record states in the MVP.
- Add workspace-level collaboration mode structure with `shared_editing` as the MVP default.
- Use automated feature and policy tests as the primary safety net for tenant/workspace boundaries and private media access.
- Keep validation reporting admin-only.
- Preserve roadmap optionality for AI, automated email, inline diary-text highlight selection, social/friend/visitor access, selective sharing, and curation without creating MVP UI promises for those capabilities.
- Move executable story files through the SM/PO workflow after architecture review, using the PRD story candidates as source material.

### Final Decision

**READY FOR STORY PREPARATION:** The PRD, architecture notes, and epic structure are comprehensive enough for SM/PO story preparation, with specific risks called out for implementation sequencing.

## Next Steps

### UX Expert Prompt

Create a focused UX specification for Life Memory Timeline for Missionaries using `docs/prd.md` as source. Prioritize the month-grouped vertical timeline-first home, simple memory cards, diary creation, trip/period creation, record detail, filters, owner/collaborator shared editing, private workspace cues, responsive web behavior, and English/Portuguese/Spanish layout safety. Do not design per-record missionary-only vs family-visible controls, masonry, map browsing, or complex timeline animations for the MVP. Keep the MVP modern, clean, emotional, and memory-first; do not use visible album-creation language.

### Story Manager Prompt

Create implementation-ready stories for Life Memory Timeline for Missionaries using `docs/prd.md` and `docs/architecture.md` as sources. Start with profile setup, tenant-safe product routing, workspace settings with default `shared_editing`, diary record creation/editing, and tenant boundary tests before trip/period records, media, localization, or reporting. Memory Highlights should be an optional dynamic "Add highlight" list stored as ordered items. Every story touching records, media, collaboration, localization, or validation reporting must include automated acceptance criteria.
