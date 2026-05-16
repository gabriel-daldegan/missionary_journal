# Project Brief: Missionary Journal

## Executive Summary

Missionary Journal is a micro-SaaS that helps missionaries and their families capture, organize, preserve, and selectively share mission memories through structured private journal entries. The product focuses on lightweight daily capture during the mission, while preparing the content for future high-quality digital or printed mission albums.

The primary problem is that mission memories often become scattered across emails, photos, notes, and conversations, making them difficult to retrieve, preserve, or share meaningfully later. The initial target users are missionaries and their families, with churches and future generations as secondary audiences. The key value proposition is a private-first, multilingual memory system that turns lived mission experiences into structured, searchable, and emotionally meaningful records without requiring the missionary to manually organize everything.

## Problem Statement

Missionaries often create meaningful records during their missions, but those memories are fragmented across emails, photos, private notes, messages, and informal conversations. Names of people, places visited, daily experiences, spiritual lessons, scriptures, feelings, and important events can become disconnected from their original context. Over time, this makes the mission harder to revisit, share with family, or preserve for future generations.

Existing lightweight tools do not fully solve this problem. Generic journals lack mission-specific structure. Photo libraries preserve images but not the story around them. Email threads contain valuable experiences but are difficult to organize later. Public blogs can help with sharing, but they are not private-first and may be inappropriate for sensitive mission records.

This matters now because the best time to preserve mission memories is while the mission is happening. If capture is delayed until the end, key details are lost, photos become harder to contextualize, and the emotional connection between the record and the lived experience weakens. The MVP must therefore make structured capture simple enough to use during the mission, while preserving enough metadata to support filtering, family visibility, and future album creation.

## Proposed Solution

Missionary Journal proposes a private-first, multilingual platform for recording mission memories as structured journal entries. Each entry can combine text, photos, date, location, and tags, with user-controlled visibility between missionary-only records and family-visible records. The core MVP experience is a memory-first timeline that shows fragments from each entry to help users rediscover moments, people, places, and lessons from the mission.

The solution begins with reliable manual capture inside the platform, including the ability to copy content from mission emails into structured journal entries. This validates the desired email-to-journal behavior without requiring automated inbound email handling in the MVP. The initial structure also prepares the product for later capabilities such as automated email capture, selective sharing, and thematic digital or print album generation.

The product differentiates itself by combining privacy by default, mission-specific context, family access, and long-term memory preservation. Rather than acting only as a generic journal, photo gallery, or public blog, Missionary Journal helps organize lived mission experiences in a way that is simple during the mission and meaningful after it.

## Target Users

### Primary User Segment: Missionaries and Their Families

The primary users are active missionaries and their close family members who want to preserve mission experiences while the mission is still happening. Missionaries need a low-friction way to capture memories without spending significant time organizing photos, formatting text, or maintaining a public publishing workflow. Families need selective access to meaningful records so they can feel connected to the mission and help preserve the story over time.

Current behavior likely includes sending emails, taking photos, keeping informal notes, sharing updates with family, and occasionally posting or messaging selected experiences. These records are valuable, but they are usually scattered and difficult to organize later.

Their main needs are:

- A simple way to create and edit journal entries.
- A private default space for sensitive memories.
- Family-visible entries for selected records.
- Timeline-based browsing that feels emotional, not administrative.
- Filters by date, tag, and location.
- Multilingual interface support for English, Portuguese, and Spanish.
- Photo support connected to the story around each image.
- A way to mark album-relevant content manually in the first paid tier.

Their goals are to remember people, places, lessons, scriptures, experiences, and everyday moments from the mission, while keeping those memories accessible for future reflection, family connection, and eventual album creation.

### Secondary User Segment: Churches and Future Generations

Secondary users are churches, ministry communities, descendants, and future readers who may later benefit from curated or shared mission memories. They are not the core MVP users, but they help define the long-term value of the product.

Their needs are likely to emerge through later features such as selective sharing, public or semi-private mission pages, and thematic albums. For the MVP, their relevance is mainly indirect: the system should preserve enough structure now so that future sharing and album experiences can be built without reworking the memory model.

## Goals & Success Metrics

### Business Objectives

- Validate that missionaries and families see enough value in structured mission memory preservation to use the product during an active mission.
- Launch a usable MVP within three weeks, focused on core journal capture, timeline browsing, family visibility, and UI localization.
- Convert the first real missionary/family group into an active paid or payment-ready validation account.
- Establish a clear tiering path where manual album metadata marking belongs to tier 1 and AI-assisted metadata extraction belongs to tier 2.
- Preserve future product optionality for album generation without building the album feature in the MVP.

### User Success Metrics

- A missionary can create, edit, and view journal entries without needing manual formatting or complex organization.
- A family member can access family-visible entries without seeing missionary-only private records.
- Users can browse mission memories through a timeline that shows meaningful fragments, photos, dates, locations, and tags.
- Users can filter entries by date, tag, and location.
- Users can use the interface in English, Portuguese, or Spanish.
- Users can manually mark album-relevant content in a journal entry in tier 1.

### Key Performance Indicators (KPIs)

- **Activation:** At least one missionary/family workspace completes registration, profile setup, and first journal entry creation.
- **Capture frequency:** The missionary creates multiple entries during the validation period, showing that capture fits real mission life.
- **Family engagement:** At least one family member views family-visible entries during the validation period.
- **Email-source validation:** Track how many journal entries are created from copied mission emails during the validation period.
- **Memory retrieval:** Users successfully find entries using timeline browsing and filters by date, tag, or location.
- **Localization validation:** The interface is reviewed by one person for each MVP language: English, Portuguese, and Spanish.
- **Album readiness:** At least a subset of entries contains structured date, location, tags, photos, and manually marked album-relevant content.

## MVP Scope

### Core Features (Must Have)

- **Authentication and registration:** Users can create accounts, log in, and access the product securely.
- **Missionary and family workspace:** The first paid tier supports at least one missionary and family access within the same workspace.
- **Journal entry creation:** Missionaries can create structured journal entries with body text, date, location, tags, photos, and visibility.
- **Journal entry editing:** Missionaries can edit existing entries as memories are refined or corrected.
- **Individual entry view:** Users can open and read a complete journal entry.
- **Memory-first timeline:** Entries appear in a friendly timeline layout with date, location, tags, photos, and fragments from the body text.
- **Filters:** Users can filter entries by date, tag, and location.
- **Photo upload:** Entries can include photos connected to the memory being recorded.
- **Visibility controls:** Entries can be missionary-only or family-visible.
- **UI localization:** The interface supports English, Portuguese, and Spanish. User-generated journal content is not translated in the MVP.
- **Manual email capture:** Users can copy content from mission emails into a structured journal entry.
- **Manual album metadata marking:** Tier 1 users can manually mark selected content as album-relevant metadata, such as people, scriptures, lessons, quotes, or album moments.
- **Private-by-default behavior:** New entries default to private unless the user intentionally makes them visible to family.

### Out of Scope for MVP

- Automated inbound email capture.
- AI-assisted metadata extraction.
- Journal content translation.
- Public blog-style sharing.
- Public mission pages or feeds.
- Follow system or social-network behavior.
- Draft status.
- Simple PDF export.
- Full album generation.
- Print-ready album layout.
- Advanced family collaboration workflows.

### MVP Success Criteria

The MVP is successful if one real missionary/family workspace can register, create and edit mission journal entries, attach photos, browse memories through a timeline, filter records by date/tag/location, control family visibility, use the UI in English/Portuguese/Spanish, and manually mark selected content for future album use.

## Post-MVP Vision

### Phase 2A: AI Metadata and Review Workflow

The primary post-MVP bet is AI-assisted metadata extraction with user review. Tier 2 can analyze journal entries and suggest album-relevant metadata such as people, places, scriptures, quotes, lessons, feelings, themes, and album moments. Suggestions should remain reviewable, with users able to accept, edit, or reject them before they become part of the album preparation layer.

This phase builds directly on the MVP's manual metadata marking. Manual marking validates the metadata model in tier 1; AI assistance improves speed and convenience in tier 2 without removing user control.

### Phase 2B: Email Capture

Automated inbound email capture becomes a strong next bet if MVP validation shows that copied mission emails are a meaningful source of journal entries. Early qualitative input suggests that 4 out of 5 missionaries maintain weekly family communication by phone, while 3 out of 5 send weekly emails to family and friends. Among those who send emails, messages often include 10 to 30 copied recipients, including family, friends, and other missionaries serving at the same time.

This behavior suggests that email is not just a capture source; it is already a private mission-update network. The MVP should validate this manually through copy/paste before building inbound email parsing, attachment extraction, and automated entry creation.

### Phase 2C: Private Audience Loop

Because mission emails often include a wider circle of family, friends, and peer missionaries, a later product opportunity is a private audience loop. This could allow selected people to receive or access specific family-visible entries, collections, or album previews. This should remain controlled and private, not a public feed or social-network model.

### Long-term Vision

Missionary Journal can become the memory system of record for a mission experience, turning scattered entries, photos, scriptures, places, names, and lessons into a preserved personal and family archive. Over time, the product can evolve from a journal into an album and memoir creation platform, helping users transform fragmented mission memories into curated digital or print-ready artifacts.

The long-term product should preserve the missionary's voice and privacy while reducing the burden of organization. AI can assist with extraction, grouping, summarization, captions, and album structure, but the user should remain in control of what is accepted, shared, and published.

### Expansion Opportunities

- **Thematic album builder:** Generate digital or print-ready mission albums from accepted entries, photos, and metadata.
- **Selective sharing:** Allow users to share specific entries, collections, or album previews with churches, family, or private groups.
- **Journal content translation:** Keep as a future backlog idea, not an active roadmap item. The MVP only localizes the interface.
- **AI-assisted memoir generation:** Generate a longer narrative from selected entries while preserving user review and control.
- **Plan-based feature tiers:** Keep manual album metadata marking in tier 1 and AI-assisted extraction in tier 2.
- **Advanced media handling:** Better photo organization, captions, album layout preparation, and storage limits by plan.

## Technical Considerations

### Platform Requirements

- **Target Platforms:** Web application, optimized for desktop, tablet, and standard browser use.
- **Browser/OS Support:** Modern browsers on macOS, Windows, Linux, iOS, and Android. Responsive web support is required, but native mobile app behavior is not part of the MVP.
- **Performance Requirements:** Journal creation, timeline browsing, and filtering should feel fast for a small active workspace. Photo uploads should be handled reliably, with storage limits defined by plan. Long-running future tasks such as AI metadata extraction or inbound email processing should run asynchronously through queues.

### Technology Preferences

- **Frontend:** Laravel Blade, Livewire, Alpine.js, Tailwind CSS, DaisyUI, and Filament where appropriate. The customer-facing journal experience should prioritize a polished timeline and writing workflow rather than an admin-table feel.
- **Backend:** Laravel with SaaSykit tenancy conventions, service-layer organization, policies/gates for privacy, queued jobs for heavier processing, and events/listeners for side effects.
- **Database:** Relational Laravel/Eloquent model structure. Core journal data should be tenant/workspace scoped and designed around future album metadata without requiring the album feature in the MVP.
- **Media:** Use the existing Spatie Media Library integration for journal photos. Mission-related media should be treated as private unless explicitly shared.
- **Localization:** Use Laravel localization for UI strings in English, Portuguese, and Spanish. User-generated journal content should remain in the language entered by the user. Content translation is backlog-only, not MVP or active roadmap.
- **Editor:** Use a rich text editor approach that can support future manual annotation/metadata actions. The MVP should keep the writing experience simple, while preserving a path to classify selected text as people, scriptures, lessons, quotes, or meaningful moments.
- **AI:** No AI extraction in the MVP. Future AI metadata extraction should run as a tier 2 asynchronous workflow with reviewable suggestions.

### Architecture Considerations

- **Repository Structure:** Follow existing SaaSykit/Laravel conventions: models in `app/Models`, services in `app/Services`, policies in `app/Policies`, Filament/Livewire components in their existing areas, and migrations/factories/tests in standard Laravel directories.
- **Service Architecture:** Journal behavior should be organized through a domain service layer, likely around journal entries, visibility, metadata marking, and future album preparation.
- **Data Model Direction:** Initial models should support journal entries, photos, tags, visibility, and manual annotations. A future `journal_entry_annotations`-style model can store type, selected text, source, confidence, status, and link back to the journal entry.
- **Access Control:** Entries must be private by default and scoped by workspace/tenant. The missionary controls whether an entry is missionary-only or family-visible in the MVP. Family users may create or edit entries, but should not control privacy classification until validation proves that behavior is safe and useful.
- **Tiering:** Manual metadata marking belongs to tier 1. AI-assisted metadata extraction and review belongs to tier 2.
- **Integration Requirements:** The MVP does not require automated inbound email. Manual copy/paste from email should be tracked so future email capture can be prioritized from real behavior.
- **Security/Compliance:** Sensitive mission records may include names, photos, locations, and religious context. The system should avoid accidental public exposure, use authorization consistently, and keep media privacy explicit.

### UX/UI Considerations

- **Elegant memory-first experience:** The customer-facing journal should feel modern, clean, and emotionally appropriate. It should not look like an administrative CRUD interface.
- **Lightweight writing flow:** The journal entry form should prioritize fast writing first. Date, location, tags, photos, and visibility should support the record without making capture feel heavy.
- **Non-intrusive manual metadata:** Manual metadata marking should not interrupt writing. Users should be able to write first and optionally classify selected content afterward.
- **Clear privacy controls:** New entries should be private by default. The missionary controls whether an entry is missionary-only or family-visible.
- **Family editing with limited privacy control:** Family users may be able to create or edit entries, but they should not control privacy classification in the MVP. This assumption should be tested during validation.
- **Emotional timeline:** The timeline should show meaningful fragments, dates, locations, tags, and photos. It should help users rediscover memories, not merely manage records.
- **Responsive web baseline:** The MVP should work in modern browsers and responsive layouts, but native mobile app behavior is not required.
- **No album language in MVP UI:** The MVP interface should not advertise or foreground album creation. Album readiness remains a technical/product capability, not visible UI positioning.
- **Localization-safe layouts:** English, Portuguese, and Spanish UI strings should fit cleanly without breaking buttons, filters, cards, or empty states.

## Constraints & Assumptions

### Constraints

- **Budget:** No fixed budget has been defined yet. MVP decisions should prioritize reuse of SaaSykit/Laravel capabilities and avoid new paid infrastructure unless required for validation.
- **Timeline:** The MVP target is three weeks.
- **Resources:** The MVP should be scoped for a small implementation effort and validation with one real missionary/family workspace first.
- **Technical:** The product should follow the existing SaaSykit tenancy architecture, Laravel conventions, and available stack. New dependencies should be avoided unless clearly necessary.
- **UX/UI:** The customer-facing journal experience must be cleaner and more emotional than a standard admin CRUD interface, but without overbuilding a full custom design system during MVP.
- **Localization:** UI localization covers English, Portuguese, and Spanish. Journal content translation is excluded from MVP and not part of the active roadmap.
- **Mobile/App:** Native mobile app behavior is excluded. The product should work responsively in modern browsers.
- **Privacy:** Missionary-only vs family-visible access must be clear and reliable. New entries default to private.
- **Album:** Album generation is excluded from the MVP. The UI should not foreground album language, even though the data model should preserve future album readiness.
- **AI:** AI-assisted metadata extraction is excluded from the MVP and reserved for tier 2 after validation.

### Key Assumptions

- Missionaries and families value preservation of mission memories enough to use a dedicated product.
- A structured journal with timeline, filters, photos, visibility, and family access is enough to validate the MVP.
- Missionaries already create valuable raw material through emails, calls, photos, notes, and messages.
- Manual copy/paste from mission emails is sufficient to validate email capture before building inbound email automation.
- Manual metadata marking is acceptable in tier 1 if it is optional, lightweight, and does not interrupt writing.
- AI-assisted metadata extraction can become a meaningful tier 2 differentiator after manual metadata behavior is validated.
- Family members may create or edit records, but privacy classification should remain controlled by the missionary in the MVP.
- English, Portuguese, and Spanish UI localization can be reviewed by people fluent in each language.
- A polished memory-first timeline will create more perceived value than a generic list of journal entries.
- Sensitive mission records require private-by-default behavior and careful authorization from the beginning.

## Risks & Open Questions

### Key Risks

- **MVP scope pressure:** Three weeks may be tight for journal entries, family access, visibility rules, photo upload, filters, timeline UX, UI localization, and manual metadata marking.
- **Admin-like UX risk:** Reusing SaaSykit/Filament patterns too heavily could make the journal feel like a CRUD tool instead of a memory product.
- **Privacy mistakes:** Mission records may include sensitive names, photos, locations, and religious context. Any confusion between missionary-only and family-visible records could damage trust.
- **Manual metadata friction:** If marking people, scriptures, lessons, quotes, or meaningful moments feels like extra work, users may ignore it.
- **Family editing ambiguity:** Allowing family members to create or edit entries while preventing them from controlling privacy may be confusing or require careful UX.
- **Localization workload:** English, Portuguese, and Spanish UI support may create layout, review, and translation overhead.
- **Photo storage and privacy:** Media upload adds storage cost, privacy concerns, and performance requirements.
- **Email behavior uncertainty:** Email appears promising, but the MVP must measure whether copied emails actually become journal entries before building automation.
- **Album expectation risk:** The product vision includes album readiness, but the MVP UI must not make users expect album generation before it exists.

### Open Questions

- What exact roles are needed in the first workspace: missionary, family member, admin/owner, or something simpler?
- Can family members create entries, edit only their own entries, edit missionary entries, or edit all family-visible records?
- Should family-created entries default to missionary review before becoming visible?
- What is the simplest manual metadata marking interaction that is useful but not disruptive?
- Should manual metadata marking be required for validation, or only available as an optional tier 1 capability?
- What photo storage limit should apply to the first tier?
- What languages are required at launch beyond English, Portuguese, and Spanish, if any?
- Which parts of the product must be localized first: auth, dashboard, journal, billing, emails, or all visible UI?
- How should the product track entries created from copied emails?
- What minimum timeline design is emotional enough to validate the product without overbuilding?

### Areas Needing Further Research

- Interview missionaries and families about current memory capture habits, especially email, phone calls, photos, and informal notes.
- Test whether family users understand visibility restrictions and editing boundaries.
- Prototype the journal entry form and timeline before full implementation.
- Prototype manual metadata marking in the editor or as a post-writing action.
- Validate storage needs and photo upload expectations for mission records.
- Review legal/safety expectations around names, locations, photos, and mission context in relevant countries or organizations.
- Validate whether AI-assisted metadata extraction is attractive enough to justify tier 2.

## Appendices

### A. Research Summary

The project brief is based on a structured brainstorming session held on May 16, 2026 with Jose and facilitated by Atlas. The session used first principles thinking, resource constraints, and MoSCoW prioritization.

Key findings from the brainstorm:

- Mission memories are not only diary entries; they are records of people, places, feelings, scriptures, lessons, daily events, and meaningful experiences.
- The strongest emotional value may appear at the end of the mission, when preserved records can become a meaningful family archive or future album.
- Capture must be lightweight because mission life leaves limited time and energy for organization.
- Private-first design is required because mission records may include sensitive people, places, photos, and religious context.
- The MVP should prove structured journal capture before investing in automation.
- The first tier should support both missionary and family access.
- The MVP should include login, missionary/family profiles, journal entry creation/editing, individual entry view, timeline, photo upload, filters, UI localization, visibility controls, and manual email copy/paste.
- Date, location, tags, and photos should be structured in the MVP.
- People, feelings, scriptures/quotes, lessons, and meaningful moments can begin as free-form content and optional manual metadata marking.
- Manual metadata marking may start as a simple post-writing classification flow, not necessarily advanced inline text annotation.
- Automated email capture, AI metadata extraction, public sharing, PDF export, and album generation are out of scope for the MVP.

Additional qualitative input from project discussion:

- 4 out of 5 missionaries maintain weekly communication with family by phone.
- 3 out of 5 missionaries send weekly emails to family and friends.
- Missionary emails often include 10 to 30 copied recipients, including family, friends, and other missionaries serving at the same time.
- This suggests phone communication supports emotional connection but does not create structured archives, while email is a promising future capture source and private audience loop.
- If copied email content is used in the MVP, entries should track whether they were created from email.
- UI localization should cover English, Portuguese, and Spanish in the MVP.
- Journal content translation is not part of the MVP or active roadmap.
- Manual metadata marking belongs to tier 1; AI-assisted metadata extraction belongs to tier 2.
- The MVP UI should not mention album creation, even though the data model should preserve future album readiness.
- Family editing and privacy boundaries require validation with real users.
- Privacy review should include media visibility, not only journal text.

### B. Stakeholder Input

Jose clarified that the document should remain in English while working conversation can happen in Portuguese. He also clarified that UI multilingual support should be limited to interface strings, not user-generated content. English, Portuguese, and Spanish are required because there are reviewers or users available in each language context.

Jose also clarified the UX direction: the product should look elegant, modern, and clean; it should not foreground album language in the MVP UI. Families may be allowed to create or edit records, but privacy classification should remain a missionary responsibility in the MVP and should be tested during validation.

### C. References

- `docs/brainstorming-session-results.md`
- SaaSykit/Laravel codebase capabilities reviewed locally:
  - Laravel localization via `__()` and language files.
  - `kkomelin/laravel-translatable-string-exporter`.
  - Filament and Livewire form/UI patterns.
  - Spatie Media Library integration.
  - SaaSykit tenancy, roles, invitations, subscriptions, and email provider foundations.

## Next Steps

### Immediate Actions

1. Review and approve this Project Brief as the source input for PRD creation.
2. Create the PRD from this brief, keeping the MVP limited to journal capture, timeline, filters, photos, visibility, family access, UI localization, manual email copy/paste, and simple manual metadata marking.
3. Define the first workspace role model: missionary, family member, and ownership/admin responsibilities.
4. Prototype the journal writing flow and memory-first timeline before implementation.
5. Decide the simplest MVP version of manual metadata marking, preferably post-writing classification before advanced inline annotation.
6. Define the MVP data model for journal entries, tags, photos, visibility, email-source tracking, and manual metadata.
7. Define localization scope for English, Portuguese, and Spanish UI strings.
8. Define privacy rules for journal text and media visibility.
9. Create implementation stories from the approved PRD.
10. Validate with one real missionary/family workspace.

### PM Handoff

This Project Brief provides the full context for Missionary Journal. Please start in PRD Generation Mode, review the brief thoroughly, and work with the user to create the PRD section by section. Pay special attention to MVP scope control, privacy, family editing boundaries, manual metadata marking, UI localization, and keeping album creation out of the visible MVP interface.
