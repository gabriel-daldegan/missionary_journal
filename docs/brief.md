# Project Brief: Life Memory Timeline for Missionaries

## Executive Summary

Life Memory Timeline for Missionaries is a private-first micro-SaaS that helps missionaries and their families capture, organize, preserve, and revisit meaningful mission memories through a chronological timeline of structured records inside a private workspace. It is the first validation experience for the broader Life Memory Timeline platform, so the long-term product is not limited to missions. It focuses on registering facts, experiences, places, periods, people, and memories in a way that can grow from a simple mission timeline into a richer life-memory system over time.

The primary problem is that important life memories often become scattered across emails, photos, notes, messages, locations, trips, celebrations, and conversations, making them difficult to retrieve, preserve, or share meaningfully later. The initial target users are missionaries and their families because mission life creates a strong, time-bound, emotionally meaningful memory-preservation need. The key value proposition is a private-first, multilingual memory timeline that turns lived experiences into structured, searchable, and emotionally meaningful records without forcing users to manually organize everything from scratch.

## Problem Statement

People often create meaningful records during important seasons of life, but those memories are fragmented across emails, photos, private notes, messages, calendars, location history, and informal conversations. Names of people, places visited, daily experiences, lessons, scriptures or quotes, feelings, trips, celebrations, and important events can become disconnected from their original context. Over time, this makes life experiences harder to revisit, share with family, or preserve for future generations.

Existing lightweight tools do not fully solve this problem. Generic journals preserve text but often lack timeline structure and connections to people, places, trips, and periods. Photo libraries preserve images but not the story around them. Email threads and messages contain valuable experiences but are difficult to organize later. Public blogs can help with sharing, but they are not private-first and may be inappropriate for sensitive personal or family records.

This matters now because the best time to preserve memories is while life is happening. If capture is delayed until later, key details are lost, photos become harder to contextualize, and the emotional connection between the record and the lived experience weakens. The MVP must therefore make structured capture simple enough to use in real life, while preserving enough metadata to support filtering, private workspace access, future collaboration modes, future curation, and deeper memory organization.

## Proposed Solution

Life Memory Timeline for Missionaries proposes a private-first, multilingual platform experience for recording mission memories as structured timeline records. Each record can combine text, photos, date or period, location, people, tags, and simple highlights. The core MVP experience is a memory-first timeline that shows fragments from each record to help users rediscover moments, people, places, periods, lessons, and meaningful experiences.

The solution begins with reliable manual capture inside the platform, with missionaries and their families serving as the first validation niche. The MVP should support diary-style records and trip/period records on the same timeline, including the ability to copy content from mission emails into structured diary records. This validates the desired email-to-diary behavior without requiring automated inbound email handling in the MVP. The initial structure also prepares the product for later capabilities such as automated capture, selective sharing, life chapters, private audience loops, and thematic digital or print curation.

The product differentiates itself by combining privacy by default, a timeline-first memory model, authenticated family workspace access, and long-term preservation. Rather than acting only as a generic journal, photo gallery, calendar, or public blog, Life Memory Timeline for Missionaries helps organize lived mission experiences in a way that is simple during the moment and meaningful after it.

## Target Users

### Initial Validation Segment: Missionaries and Their Families

The initial validation users are active missionaries and their close family members who want to preserve mission experiences while the mission is still happening. Missionaries need a low-friction way to capture memories without spending significant time organizing photos, formatting text, or maintaining a public publishing workflow. Families need authenticated access to the shared private workspace so they can feel connected to the mission and help preserve the story over time.

Current behavior likely includes sending emails, taking photos, keeping informal notes, sharing updates with family, and occasionally posting or messaging selected experiences. These records are valuable, but they are usually scattered and difficult to organize later.

Their main needs are:

- A simple way to create and edit diary records.
- A private workspace for sensitive memories.
- Shared workspace editing where the owner and collaborator can both create and edit records in the MVP.
- A collaboration model that can later support selectable modes without changing the memory record structure.
- Timeline-based browsing that feels emotional, not administrative.
- Filters by date, tag, and location.
- Multilingual interface support for English, Portuguese, and Spanish.
- Photo support connected to the story around each image.
- A way to mark meaningful people, scriptures, lessons, quotes, or moments manually in the first paid tier.

Their goals are to remember people, places, lessons, scriptures, experiences, and everyday moments from the mission, while keeping those memories accessible for future reflection, family connection, and eventual curation.

### Broader Product Audience: People and Families Preserving Life Memories

The broader product audience includes people and families who want a private, organized timeline for important life occasions beyond missionary service, such as trips, periods of study, family milestones, celebrations, meaningful places, personal growth seasons, and inherited memories.

This broader audience should shape the product model from the beginning: the timeline should not be hard-coded around a mission object. Instead, mission life should function as the first use case, template, or life chapter that validates the memory model before expanding to other life contexts.

### Secondary Access Segment: Friends and Existing Email Recipients

Secondary users are friends, relatives, peer missionaries, and trusted people who are already commonly copied on missionary update emails by missionaries or their families. They are not the core MVP users, but they help define the future private audience loop because they already participate in the missionary's informal communication network.

Their needs are likely to emerge through later features such as selective sharing, private access links, shared collections, and curated memory previews. For the MVP, their relevance is mainly indirect: the system should preserve enough structure now so that future private sharing can be built without reworking the memory model.

## Goals & Success Metrics

### Business Objectives

- Validate that a timeline-first memory product creates enough value for missionaries and families to use it during an active mission.
- Launch a usable MVP within three weeks, focused on personal profile setup, timeline browsing, diary records, trip/period records, shared private workspace access, and UI localization.
- Convert the first real missionary/family group into an active paid or payment-ready validation account.
- Establish a clear tiering path where simple Memory Highlights belong to tier 1 and AI-assisted metadata extraction belongs to tier 2.
- Preserve future product optionality for broader life-memory use cases and album-style curation without building those advanced features in the MVP.

### User Success Metrics

- A user can complete a lightweight personal profile and land on a usable timeline without being forced into a mission-specific setup.
- A missionary can create, edit, and view diary records without needing manual formatting or complex organization.
- A user can create a trip/period record with title, location, dates, people, photos, and notes.
- A family member or collaborator can access and edit workspace records inside the authenticated tenant boundary.
- Users can browse memories through a timeline ordered by experience date that shows meaningful fragments, photos, dates or periods, locations, people, and tags.
- Users can filter records by date, tag, and location.
- Users can use the interface in English, Portuguese, or Spanish.
- Users can save simple Memory Highlights in a record without needing to classify them technically.

### Key Performance Indicators (KPIs)

- **Activation:** At least one missionary/family workspace completes registration, personal profile setup, timeline access, and first record creation.
- **Capture frequency:** The missionary creates multiple records during the validation period, showing that capture fits real mission life.
- **Family engagement:** At least one family member or collaborator views and contributes to workspace records during the validation period.
- **Email behavior signal:** Observe whether copied mission email content becomes real diary records during validation. Structured email-source tracking is useful, but not blocking for the MVP.
- **Memory retrieval:** Users successfully find records using timeline browsing and filters by date, tag, or location.
- **Localization validation:** The interface is reviewed by one person for each MVP language: English, Portuguese, and Spanish.
- **Emotional validation:** At least three missionary/family testers create two real records each, and family members report that centralized access makes the mission easier to follow, revisit, and preserve.
- **Curation readiness:** At least a subset of records contains structured date or period, location, tags, photos, people, and simple Memory Highlights.

## MVP Scope

### Core Features (Must Have)

- **Authentication and registration:** Users can create accounts, log in, and access the product securely.
- **Personal profile setup:** After registration, users complete a lightweight personal profile before landing on the timeline. Mission context is optional and should not be required as the foundation of the whole product.
- **Timeline-first home:** After setup, users land on the memory timeline as the primary product surface.
- **Missionary and family workspace:** The first paid tier supports one owner and one collaborator within the same workspace for the initial validation niche.
- **Diary record creation:** Users can create structured diary records with body text, experience date, location, tags, photos, and Memory Highlights.
- **Diary record editing:** Users can edit existing diary records as memories are refined or corrected.
- **Trip/period record creation:** Users can add a trip or period to the timeline with title, location, start/end dates, people, photos, notes, and Memory Highlights.
- **Individual record view:** Users can open and read a complete timeline record.
- **Memory-first timeline:** Records appear in a friendly vertical timeline grouped by month and ordered by experience date, with visual month separators, date or period, optional location, primary photo, emotional excerpt, simple tags, photo count, and a clear way to open the full record.
- **Filters:** Users can filter records by date, tag, and location.
- **Photo upload:** Records can include photos connected to the memory being recorded, with private storage and web-optimized image handling.
- **Shared workspace editing:** The owner and collaborator can both create and edit records inside the private workspace in the MVP.
- **Future collaboration-mode structure:** The architecture should preserve a workspace-level collaboration setting so later plans can support selectable modes such as shared editing, owner review, or read-only collaborator access.
- **UI localization:** The interface and user navigation support English, Portuguese, and Spanish, with English as the default. User-generated content is not translated in the MVP.
- **Manual email capture:** Users can paste content from mission emails into a structured diary record body.
- **Memory Highlights:** Users can optionally save important names, scriptures, lessons, quotes, or moments they may want to remember later. The MVP interaction should be a simple dynamic list with an "Add highlight" action, not a textarea dump, advanced metadata form, or inline annotation workflow.
- **Private-by-default behavior:** Records and media remain private to the tenant/workspace unless a future feature explicitly introduces external sharing.

### Should Have (Non-blocking)

- **Email-source marker:** If implementation capacity allows, diary records can include a simple source marker for copied mission email content. This should not block diary creation, timeline browsing, or validation readiness.

### Out of Scope for MVP

- Automated inbound email capture.
- AI-assisted metadata extraction.
- Inline selection of diary text for Memory Highlights.
- User-generated content translation.
- Public blog-style sharing.
- Public mission pages or feeds.
- Follow system or social-network behavior.
- Per-record missionary-only versus family-visible visibility controls.
- Selectable collaboration modes beyond the MVP default of shared editing.
- Draft status.
- Simple PDF export.
- Full album generation.
- Print-ready album layout.
- Full second-brain behavior.
- Advanced life graph or knowledge graph relationships.
- Additional record types beyond diary and trip/period records.
- Advanced family collaboration workflows.

### MVP Success Criteria

The MVP is successful if one real missionary/family workspace can register, complete personal profile setup, land on a timeline-first home, create and edit diary records, create a trip/period record, attach photos, browse memories through an experience-date timeline, filter records by date/tag/location, collaborate inside the shared private workspace, use the UI in English/Portuguese/Spanish, and save simple Memory Highlights for future curation. Emotional validation matters: at least three testers should create two real records each, and family members should report that centralized access makes the mission easier to follow, revisit, and preserve.

## Post-MVP Vision

### Phase 2A: AI Metadata and Review Workflow

The primary post-MVP bet is AI-assisted metadata extraction with user review. Tier 2 can analyze timeline records and Memory Highlights, then suggest meaningful metadata such as people, places, scriptures, quotes, lessons, feelings, themes, and moments. Suggestions should remain reviewable, with users able to accept, edit, or reject them before they become part of the curation layer.

This phase builds directly on the MVP's Memory Highlights. Highlights validate which memories users consider important in tier 1; AI assistance improves classification, speed, and convenience in tier 2 without removing user control.

### Phase 2B: Email Capture

Automated inbound email capture becomes a strong next bet if MVP validation shows that copied mission emails are a meaningful source of diary records. Early qualitative input suggests that 4 out of 5 missionaries maintain weekly family communication by phone, while 3 out of 5 send weekly emails to family and friends. Among those who send emails, messages often include 10 to 30 copied recipients, including family, friends, and other missionaries serving at the same time.

This behavior suggests that email is not just a capture source; it is already a private mission-update network. The MVP should validate this manually through copy/paste before building inbound email parsing, attachment extraction, and automated record creation.

### Phase 2C: Private Audience Loop

Because mission emails often include a wider circle of family, friends, and peer missionaries, a later product opportunity is a private audience loop. This could allow selected people to receive or access specific shared records, collections, or curated previews. This should remain controlled and private, not a public feed or social-network model.

### Long-term Vision

The broader Life Memory Timeline platform can become the memory system of record for meaningful life experiences, turning scattered records, photos, scriptures or quotes, places, names, periods, trips, and lessons into a preserved personal and family archive. Over time, the product can evolve from a diary and timeline tool into a broader memory and curation platform, helping users transform fragmented life memories into curated digital or print-ready artifacts.

The long-term product should preserve the user's voice and privacy while reducing the burden of organization. AI can assist with extraction, grouping, summarization, captions, curation, and album structure, but the user should remain in control of what is accepted, shared, and published.

### Expansion Opportunities

- **Thematic album builder:** Generate digital or print-ready curated artifacts from accepted records, photos, and metadata.
- **Selective sharing:** Allow users to share specific records, collections, or curated previews with family, friends, existing email recipients, or private groups.
- **Additional record types:** Add life events, celebrations, places, people, milestones, inherited memories, and other timeline objects after diary and trip/period records are validated.
- **Second-brain evolution:** Explore connected memories, people, places, themes, and AI-assisted retrieval as a long-term direction after the timeline model proves valuable.
- **User-generated content translation:** Keep as a future backlog idea, not an active roadmap item. The MVP only localizes the interface.
- **Inline Memory Highlights:** Allow users to select text inside a diary record and save it as a Memory Highlight. This should reuse the same `memory_highlights` model instead of introducing a separate annotation system.
- **AI-assisted memoir generation:** Generate a longer narrative from selected records while preserving user review and control.
- **Plan-based feature tiers:** Keep simple Memory Highlights in tier 1 and AI-assisted extraction in tier 2.
- **Advanced media handling:** Better photo organization, captions, album layout preparation, and storage limits by plan.

## Technical Considerations

### Platform Requirements

- **Target Platforms:** Web application, optimized for desktop, tablet, and standard browser use.
- **Browser/OS Support:** Modern browsers on macOS, Windows, Linux, iOS, and Android. Responsive web support is required, but native mobile app behavior is not part of the MVP.
- **Performance Requirements:** Record creation, timeline browsing, and filtering should feel fast for a small active workspace. Photo uploads should be handled reliably, with storage limits defined by plan and web-optimized conversions for responsive delivery. Long-running future tasks such as image conversions, AI metadata extraction, or inbound email processing should run asynchronously through queues when practical.

### Technology Preferences

- **Frontend:** Laravel Blade, Livewire, Alpine.js, Tailwind CSS, DaisyUI, and Filament where appropriate. The customer-facing experience should prioritize a polished timeline and writing workflow rather than an admin-table feel.
- **Backend:** Laravel with SaaSykit tenancy conventions, service-layer organization, policies/gates for privacy, queued jobs for heavier processing, and events/listeners for side effects.
- **Database:** Relational Laravel/Eloquent model structure. Core timeline data should be tenant/workspace scoped and designed around multiple record types, future curation metadata, and future collaboration modes without requiring album or second-brain features in the MVP.
- **Media:** Use the existing Spatie Media Library integration for timeline photos. Mission-related media should be private to the tenant/workspace. The MVP should validate local private storage on Hostinger while keeping the media layer portable to S3-compatible object storage such as AWS S3 or Cloudflare R2.
- **Localization:** Use Laravel localization for UI strings and user navigation in English, Portuguese, and Spanish, with English as the default. Users should select their preferred language during registration or in their profile. User-generated content should remain in the language entered by the user. Content translation is backlog-only, not MVP or active roadmap.
- **Editor:** Use a rich text editor approach for diary records, but keep Memory Highlights outside of an advanced editor annotation workflow in the MVP. A simple dynamic list with "Add highlight" is enough to capture important names, scriptures, lessons, quotes, or moments for later classification.
- **AI:** No AI extraction in the MVP. Future AI metadata extraction should run as a tier 2 asynchronous workflow with reviewable suggestions.

### Architecture Considerations

- **Repository Structure:** Follow existing SaaSykit/Laravel conventions: models in `app/Models`, services in `app/Services`, policies in `app/Policies`, Filament/Livewire components in their existing areas, and migrations/factories/tests in standard Laravel directories.
- **Service Architecture:** Timeline behavior should be organized through a domain service layer, likely around records, record types, workspace settings, Memory Highlights, media handling, and future curation preparation.
- **Data Model Direction:** Initial models should support timeline records, record types, photos, tags, people references, Memory Highlights, and a workspace-level collaboration mode setting. Highlights should be stored as separate ordered items even though the MVP UI presents them as a simple dynamic list. Future classification can transform highlights into people, places, scriptures, quotes, lessons, feelings, themes, or album moments.
- **Access Control:** Records must be private by default and scoped by workspace/tenant. In the MVP, the owner and collaborator can both create and edit records inside the workspace. The architecture should keep create/update permissions behind policies and services so future collaboration modes can restrict editing, require owner review, or allow read-only collaborator access without introducing per-record missionary-vs-family visibility in the MVP.
- **Tiering:** Simple Memory Highlights belong to tier 1. AI-assisted metadata extraction and review belongs to tier 2.
- **Integration Requirements:** The MVP does not require automated inbound email. Users can paste mission email content into diary records. A structured email-source marker is a non-blocking should-have, not a must-have.
- **Security/Compliance:** Sensitive records may include names, photos, locations, family context, religious context, and other personal data. The system should avoid accidental public exposure, use authorization consistently, keep media privacy explicit, and be designed with LGPD readiness in mind.

### UX/UI Considerations

- **Elegant memory-first experience:** The customer-facing timeline should feel modern, clean, and emotionally appropriate. It should not look like an administrative CRUD interface.
- **Timeline as the aha moment:** The timeline is the primary product screen and should be the first meaningful experience after profile setup. It must open as a memory experience, not a table. The MVP timeline should be vertical, grouped by month, and separated by clear month labels. Timeline cards should show date, optional location, primary photo, emotional excerpt, simple tags, photo count, and a clear action to open the full record.
- **Timeline card reference:** A representative card should feel like a memory fragment: "Sunday, May 17, 2026", "Beira, Mozambique", "Today we visited a family who reminded me why small acts of service matter...", tags such as Service, Faith, and Family, photo count, and a clear private-workspace cue where needed.
- **Lightweight writing flow:** The diary record form should prioritize fast writing first. Date, location, tags, photos, and Memory Highlights should support the record without making capture feel heavy.
- **Trip/period flow:** Trip or period records should be faster and more structured than diary records, focused on title, location, period, people, photos, and notes.
- **Non-intrusive highlights:** Memory Highlights should not interrupt writing or feel like technical metadata work. Users should be able to write first and optionally add highlights afterward through an "Add highlight" list using human language such as "Add important names, scriptures, lessons, quotes, or moments you may want to remember later."
- **Clear private-workspace cues:** Records and media are private to the authenticated workspace. The MVP should avoid per-record private/shared labels that imply missionary-only versus family-visible classification.
- **Shared editing with future flexibility:** Owner and collaborator can both create and edit records in the MVP. This is the starting collaboration mode, not a permanent permission limit; later plans may make collaboration type selectable at the workspace level.
- **Emotional timeline:** The timeline should show meaningful fragments, dates or periods, locations, people, tags, and photos. It should help users rediscover memories, not merely manage records.
- **No overbuilt timeline patterns:** The MVP should not use masonry, maps, complex animations, or advanced visual grouping. Month grouping plus simple cards is enough for validation.
- **Responsive web baseline:** The MVP should work in modern browsers and responsive layouts, but native mobile app behavior is not required.
- **No album language in MVP UI:** The MVP interface should not advertise or foreground album creation. Album readiness remains a technical/product capability, not visible UI positioning.
- **Localization-safe layouts:** English, Portuguese, and Spanish UI strings should fit cleanly without breaking buttons, filters, cards, or empty states.

## Constraints & Assumptions

### Constraints

- **Budget:** No fixed budget has been defined yet. MVP decisions should prioritize reuse of SaaSykit/Laravel capabilities and avoid new paid infrastructure unless required for validation.
- **Timeline:** The MVP target is three weeks.
- **Resources:** The MVP should be scoped for a small implementation effort and validation with one real missionary/family workspace first.
- **Technical:** The product should follow the existing SaaSykit tenancy architecture, Laravel conventions, and available stack. New dependencies should be avoided unless clearly necessary.
- **Scope:** The product model should support broader life memories, but the MVP should validate only diary records and trip/period records with missionaries and families as the first niche.
- **UX/UI:** The customer-facing timeline experience must be cleaner and more emotional than a standard admin CRUD interface, but without overbuilding a full custom design system during MVP.
- **Localization:** UI localization covers English, Portuguese, and Spanish, starting from English as the default. User navigation and product UI should be translated; user-generated content translation is excluded from MVP and not part of the active roadmap.
- **Mobile/App:** Native mobile app behavior is excluded. The product should work responsively in modern browsers.
- **Privacy:** Records and media must stay private to the tenant/workspace. The MVP does not include missionary-only versus family-visible record states.
- **Album/Curation:** Album generation is excluded from the MVP. The UI should not foreground album language, even though the data model should preserve future curation readiness.
- **AI:** AI-assisted metadata extraction is excluded from the MVP and reserved for tier 2 after validation.

### Key Assumptions

- Missionaries and families value preservation of mission memories enough to validate the first niche of a broader life-memory product.
- A timeline-first product with diary records, trip/period records, filters, photos, shared workspace editing, and family access is enough to validate the MVP.
- Missionaries already create valuable raw material through emails, calls, photos, notes, and messages.
- Manual copy/paste from mission emails is sufficient to validate email capture before building inbound email automation.
- Memory Highlights are acceptable in tier 1 as an optional dynamic list if they stay lightweight and do not interrupt writing.
- AI-assisted metadata extraction can become a meaningful tier 2 differentiator after highlight behavior is validated.
- Family collaborators can create and edit records in the MVP, while future collaboration mode changes can be handled through workspace-level settings and policies.
- English, Portuguese, and Spanish UI localization can be reviewed by people fluent in each language, with English as the default interface language.
- A polished memory-first timeline will create more perceived value than a generic list of records or events.
- Sensitive mission records require private-by-default behavior and careful authorization from the beginning.

## Risks & Further Research

### Key Risks

- **MVP scope pressure:** Three weeks may be tight for diary records, trip/period records, shared workspace access, photo upload, filters, timeline UX, UI localization, and Memory Highlights.
- **Positioning risk:** Expanding from a mission-specific product to a broader life-memory product may dilute the MVP unless missionaries and families remain the first validation niche.
- **Admin-like UX risk:** Reusing SaaSykit/Filament patterns too heavily could make the timeline feel like a CRUD tool instead of a memory product.
- **Privacy mistakes:** Mission records may include sensitive names, photos, locations, and religious context. Any accidental exposure outside the tenant/workspace boundary could damage trust.
- **Highlight friction:** If saving highlights feels like administrative metadata work, users may ignore it. The MVP should avoid classification-heavy UX.
- **Collaboration-mode ambiguity:** The MVP starts with shared editing, but the product must avoid hard-coding that mode so future workspace-level collaboration choices can be added cleanly.
- **Localization workload:** English, Portuguese, and Spanish UI support may create layout, review, and translation overhead.
- **Photo storage and privacy:** Media upload adds storage cost, privacy concerns, and performance requirements.
- **Email behavior uncertainty:** Email appears promising, but the MVP must measure whether copied emails actually become diary records before building automation.
- **Album expectation risk:** The product vision includes future curation readiness, but the MVP UI must not make users expect album generation before it exists.

### Areas Needing Further Research

- Interview missionaries and families about current memory capture habits, especially email, phone calls, photos, and informal notes.
- Test whether owner and collaborator users understand the shared private workspace and editing model.
- Prototype the diary record form, trip/period record form, and month-grouped vertical timeline before full implementation.
- Prototype Memory Highlights as a simple dynamic "Add highlight" list, not as advanced editor annotation.
- Validate storage needs and photo upload expectations for mission records against the Hostinger MVP environment and future S3-compatible storage plans.
- Review legal/safety expectations around names, locations, photos, and mission context in relevant countries or organizations.
- Validate whether AI-assisted metadata extraction is attractive enough to justify tier 2.

## Appendices

### A. Research Summary

The project brief is based on a structured brainstorming session held on May 16, 2026 with Jose and facilitated by Atlas. The session used first principles thinking, resource constraints, and MoSCoW prioritization.

Key findings from the brainstorm:

- Mission memories are not only diary entries; they are records of people, places, feelings, scriptures, lessons, daily events, and meaningful experiences.
- The strongest emotional value may appear at the end of the mission, when preserved records can become a meaningful family archive or future curated artifact.
- Capture must be lightweight because mission life leaves limited time and energy for organization.
- Private-first design is required because mission records may include sensitive people, places, photos, and religious context.
- The MVP should prove structured timeline capture before investing in automation.
- The first tier should support both missionary and family access for the initial validation niche.
- The MVP should include login, personal profile setup, timeline-first home, diary record creation/editing, trip/period record creation, individual record view, private photo upload, filters, UI localization, shared workspace editing, and manual email copy/paste for mission validation.
- Date, location, tags, and photos should be structured in the MVP.
- People, feelings, scriptures/quotes, lessons, and meaningful moments can begin as free-form content and optional Memory Highlights.
- Memory Highlights should start as a simple post-writing dynamic list with an "Add highlight" action, not advanced inline text annotation or technical classification.
- Automated email capture, AI metadata extraction, public sharing, PDF export, and album generation are out of scope for the MVP.

Additional qualitative input from project discussion:

- 4 out of 5 missionaries maintain weekly communication with family by phone.
- 3 out of 5 missionaries send weekly emails to family and friends.
- Missionary emails often include 10 to 30 copied recipients, including family, friends, and other missionaries serving at the same time.
- This suggests phone communication supports emotional connection but does not create structured archives, while email is a promising future capture source and private audience loop.
- If copied email content is used in the MVP, structured tracking is useful but non-blocking. Qualitative validation can still capture whether users paste email content into diary records.
- UI localization should cover English, Portuguese, and Spanish in the MVP.
- User-generated content translation is not part of the MVP or active roadmap.
- Memory Highlights belong to tier 1; AI-assisted metadata extraction belongs to tier 2.
- The MVP UI should not mention album creation, even though the data model should preserve future curation readiness.
- Owner/collaborator shared editing and privacy boundaries require validation with real users.
- Privacy review should include media visibility, not only record text.
- Photo handling should follow web image best practices: validate allowed file types, reject oversized uploads, strip unnecessary metadata, generate responsive web renditions, and serve private media only after authorization.

### B. Stakeholder Input

Jose clarified that the document should remain in English while working conversation can happen in Portuguese. He also clarified that UI multilingual support should be limited to interface strings and user navigation, not user-generated content. English, Portuguese, and Spanish are required because there are reviewers or users available in each language context, with English as the default. Users should select their preferred language during registration or in their profile.

Jose also clarified the UX direction: the product should look elegant, modern, and clean; it should not foreground album language in the MVP UI. The owner and collaborator can both create and edit records in the MVP. This shared editing mode should be structured so future collaboration modes can become selectable at the workspace level.

Jose later reframed the product direction: the missionary context should inspire the first validation niche, but the final product should not be restricted to a missionary journal. The product should be timeline-first, centered on memories and life facts. After registration, users should complete a basic personal profile and then land on a timeline where they can add records such as diary entries and trips or periods. Mission context should be optional, not the required organizing object for the entire system.

Jose also clarified that manual metadata marking may be too heavy for the MVP. The user-facing concept should be Memory Highlights: a simple optional dynamic list for saving important names, scriptures, lessons, quotes, or moments without forcing technical classification. Architecturally, highlights can remain structured enough to support later classification into people, places, scriptures, quotes, lessons, feelings, themes, or album moments.

### C. References

- `docs/brainstorming-session-results.md`
- SaaSykit/Laravel codebase capabilities reviewed locally:
  - Laravel localization via `__()` and language files.
  - `kkomelin/laravel-translatable-string-exporter`.
  - Filament and Livewire form/UI patterns.
  - Spatie Media Library integration.
  - SaaSykit tenancy, roles, invitations, subscriptions, and email provider foundations.
  - Hostinger-compatible local private storage as the MVP validation target, with future migration path to AWS S3 or Cloudflare R2 through S3-compatible object storage.

## Next Steps

### Immediate Actions

1. Review and approve this Project Brief as the source input for PRD creation.
2. Create or update the PRD from this brief, keeping the MVP limited to personal profile setup, timeline-first home, diary records, trip/period records, filters, private photos, shared workspace editing, family access, UI localization, manual email copy/paste for the mission validation niche, and simple Memory Highlights.
3. Define the first workspace role model as owner plus collaborator, with both able to create and edit records in the MVP.
4. Prototype the diary writing flow, trip/period creation flow, and memory-first timeline before implementation.
5. Prototype Memory Highlights as an optional post-writing dynamic list with an "Add highlight" action before advanced inline annotation.
6. Define the MVP data model for timeline records, record types, tags, people, private photos, workspace-level collaboration mode, optional email-source tracking, and ordered Memory Highlights.
7. Define localization scope for English, Portuguese, and Spanish UI strings and user navigation.
8. Define privacy rules for record text and media access.
9. Create implementation stories from the approved PRD.
10. Validate with one real missionary/family workspace.

### PM Handoff

This Project Brief provides the full context for the Life Memory Timeline for Missionaries validation experience and the broader Life Memory Timeline product direction. Please start in PRD Generation Mode, review the brief thoroughly, and work with the user to create the PRD section by section. Pay special attention to MVP scope control, the broader timeline-first positioning, missionaries and families as the first validation niche, the timeline as the primary product experience, tenant/workspace privacy, owner/collaborator shared editing, future collaboration-mode flexibility, Memory Highlights, UI localization, private media handling, and keeping album creation out of the visible MVP interface.
