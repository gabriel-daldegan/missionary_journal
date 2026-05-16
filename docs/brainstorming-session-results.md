# Brainstorming session results

**Session date:** May 16, 2026
**Facilitator:** Analyst Atlas
**Participant:** Jose

## Executive summary

**Topic:** Missionary Journal, a micro-SaaS for missionaries to register,
organize, preserve, and share mission memories.

**Session goals:** Define a viable MVP for three weeks of work, focused on
structured mission journals, private records, optional sharing, multilingual
support, and future album creation.

**Techniques used:** First principles thinking, resource constraints, MoSCoW
prioritization.

**Total ideas generated:** 17

**Scope decisions captured:** 20

### Key themes identified

- Preserve names, places, simple daily facts, and memorable events.
- Make mission memories accessible to missionaries, families, churches, and
  future generations.
- Remove the burden of organizing photos, structuring text, and formatting
  content during the mission.
- Treat privacy as the default, with optional sharing when the user chooses.
- Build toward a high-quality thematic mission album, digital or printed.
- Support the missionary and family as distinct user profiles in the first
  paid tier.

## Technique sessions

### First principles thinking

**Description:** The session separated the essence of the product from
features, screens, and implementation details.

#### Ideas generated

1. Preserve names of people met during the mission.
2. Preserve places connected to mission experiences.
3. Preserve remarkable facts and simple daily events.
4. Serve all affected audiences: missionary, family, church, and future
   generations.
5. Make memories accessible instead of distant from the lived reality.
6. Store who registered each journal entry.
7. Store text, images, date, location, people, feeling, scripture or quote,
   learning, and tags.
8. Let the user avoid organizing photos manually.
9. Let the user avoid structuring and formatting text manually.
10. Support a thematic album with meaningful memories, scriptures, quotes,
    experiences, photos, and names of people.
11. Offer album design based on themes and visual styles.

#### Insights discovered

- The main emotional problem is not only writing a diary. It is preventing the
  separation between memory and the reality of what was lived.
- The product must be useful during the mission, but its strongest emotional
  payoff appears at the end, when the mission can become an album.
- Capture must be lightweight because mission life leaves little time and
  energy for organizing records.
- The system's value grows when it turns scattered content into structured
  memory.

#### Notable connections

- Email capture can reduce friction because families and missionaries already
  communicate through email.
- Private journal entries and optional blog-style sharing can serve different
  audiences without forcing one publishing model.
- Album generation depends on structured metadata from the beginning, such as
  date, location, people, tags, feelings, scriptures, and lessons learned.

### Resource constraints

**Description:** The session constrained the idea to a three-week MVP that can
be used by one real missionary and validated without building every future
automation.

#### Ideas generated

1. The ideal long-term capture flow includes both platform/app entry and email
   capture.
2. The MVP should stay limited, but the first paid tier includes missionary and
   family access rather than a single-user-only model.
3. Album creation can come after the MVP.
4. Blog-style sharing is not required for the MVP.
5. The first version must not lose the ability to register entries in the
   platform.
6. Email-to-journal can be validated manually at first by copying email content
   into the platform.

#### Insights discovered

- The MVP should prove that structured mission records are worth keeping before
  investing in automation.
- Automated inbound email is desirable, but not required for the first usable
  version.
- The MVP's critical path is reliable journal entry creation, not sharing or
  album production.
- A missionary-plus-family MVP increases scope, but matches the emotional buyer
  and validation path more accurately.

#### Notable connections

- Manual copy and paste from email can validate the email capture behavior
  before building parsing, attachments, and delivery infrastructure.
- Album generation should influence the entry fields now, even if the album
  feature ships later.
- Private-first design remains important even without public sharing.

### MoSCoW prioritization

**Description:** The session classified candidate MVP features as must have,
should have, could have, or will not have now.

#### Must have

1. Login and registration.
2. Missionary and family user profiles.
3. Create journal entry.
4. Edit journal entry.
5. List entries in a friendly timeline layout with fragments from each record.
6. View an individual entry.
7. Filter entries by date, tag, and location.
8. Multilingual interface.
9. Entry visibility for missionary-only and family-visible records.

#### Metadata decision

The MVP will use a hybrid model. Date, location, tags, and photos are
structured fields. People, feeling, scripture or quote, and learning stay in
the free-form journal body for the first version.

#### Could have

1. Search.
2. Future album data preparation.
3. Automated email capture that creates an entry.

#### Will not have now

1. Draft status.
2. Simple PDF export.

#### Insights discovered

- The timeline is not just a list. It is part of the emotional experience and
  must show fragments of memory, not only titles.
- Filters by date, tag, and location are more important than full-text search
  for the first version because the product depends on memory retrieval.
- The MVP needs simple visibility choices because family access is part of the
  first tier.
- Multilingual interface is part of the MVP because mission users may not share
  one language.
- The hybrid entry model balances low-friction writing with reliable filters
  and future album readiness.
- Family access means the MVP needs role-aware access rules, even if social
  sharing waits for a later release.

## Idea categorization

### Immediate opportunities

1. **Structured journal entries**
   - Description: Create entries with author, text, images, date, location,
     people, feeling, scripture or quote, learning, and tags.
   - Why immediate: This is the core memory-preservation unit.
   - Resources needed: Basic journal model, media upload, and entry form.

2. **Private by default**
   - Description: Keep entries private unless the user chooses to share them.
   - Why immediate: Mission records can include sensitive people, places, and
     personal experiences.
   - Resources needed: Visibility field and authorization rules.

3. **Manual email capture workflow**
   - Description: Let the user copy mission email content into a structured
     journal entry.
   - Why immediate: This validates email-based capture without building inbound
     email automation.
   - Resources needed: Entry form that accepts long text, images, dates,
     people, tags, and notes.

4. **Memory-first timeline**
   - Description: Show journal entries as a timeline with date, location, tags,
     photos, and short fragments from the entry body.
   - Why immediate: The list view must help users rediscover memories, not only
     manage records.
   - Resources needed: Timeline layout, excerpt generation, filters, and empty
     states.

### Future innovations

1. **Thematic album builder**
   - Description: Transform journal entries into a digital or print-ready
     mission album.
   - Development needed: Selection flow, layout themes, captions, export, and
     print-ready formatting.
   - Timeline estimate: After MVP validation.

2. **Multilingual publishing**
   - Description: Let entries and shared pages work across languages.
   - Development needed: Language metadata, translation flow, and localized UI.
   - Timeline estimate: MVP foundation, expanded after launch.

3. **Automated inbound email capture**
   - Description: Let users copy the system on an email and convert the message
     into a draft journal entry.
   - Development needed: Inbound email handling, parser, attachment extraction,
     and draft review flow.
   - Timeline estimate: After the manual capture workflow proves useful.

### Moonshots

1. **AI-assisted mission memoir**
   - Description: Generate a polished narrative from entries, images, tags, and
     scriptures.
   - Transformative potential: Turns fragmented memories into a book-like
     testimony.
   - Challenges to overcome: Voice preservation, privacy, theological accuracy,
     and review controls.

## Open questions

- What level of album quality is required for the first release: simple PDF,
  designed digital album, or print-ready layout?
- Which countries and mission contexts create specific legal or safety
  requirements for names, photos, locations, and religious content?
- What storage limits should apply to each plan?

## MVP scope

### Included

1. Login and registration.
2. Missionary and family user profiles in the first tier.
3. Create journal entry.
4. Edit journal entry.
5. View individual entry.
6. Timeline layout with entry fragments.
7. Photo upload.
8. Structured date, location, and tags.
9. Filters by date, tag, and location.
10. Multilingual interface.
11. Private journal by default.
12. Entry visibility for missionary-only and family-visible records.
13. Manual email capture through copy and paste into the platform.

### Excluded

1. Draft status.
2. Public web/blog sharing.
3. Platform-wide public feed.
4. Follow system similar to social networks.
5. Shared family editing.
6. Simple PDF export.
7. Thematic album builder.
8. Automated inbound email capture.

## Privacy and access model

### Product plan direction

The product can have up to three plan types. The first tier should include two
user profiles:

1. Missionary.
2. Family.

The missionary creates entries. The family can access permitted memories and
see the missionary's progress.

### Entry visibility direction

The long-term product should support three visibility levels:

1. **Missionary-only:** Only the missionary can view the entry.
2. **Family-visible:** The missionary and family can view the entry.
3. **Platform-visible:** Other platform users can view the entry inside the
   platform, with a future follow model similar to social products.

For the three-week MVP, the minimum is missionary-only and family-visible.
Platform-visible entries and following are roadmap items and will not ship in
the MVP.

### Family access in MVP

Family access is part of the MVP because the first tier includes the family and
because family validation is central to the product's emotional value.

### Privacy and terms

Users should accept terms of use and a privacy policy during account creation.
Because the journal can contain names, photos, locations, religious context,
and potentially sensitive personal data, the product should require legal
review before public launch.

### Deletion and recovery

The MVP should use soft deletion. When a user deletes an entry or photo, it is
hidden from the user but remains recoverable until a permanent deletion policy
is defined.

### Indexing

The authenticated app should not be indexed by search engines. Public
marketing, promotion, and sales pages can be indexed.

### Architecture implication

The product can live in the same Laravel SaaSykit monolith as both authenticated
application and public marketing site. The app area and public website need
separate routing, middleware, indexing, and authorization rules.

### Legal reference notes

The LGPD treats collection, storage, access, sharing, deletion, and other
operations with personal data as data processing. It also recognizes sensitive
personal data categories, including religious or philosophical conviction.
Missionary Journal should treat privacy, transparency, and access controls as
product requirements, not only policy text.

## Plans and monetization

### Tier 1

Tier 1 includes:

1. Two users:
   - One missionary profile.
   - One family profile.
2. No AI functionality.
3. Access to the platform social layer to follow missionaries inside the
   platform.

### Tier 2

Tier 2 includes:

1. All Tier 1 functionality.
2. Thirty AI credits per month.
3. Five guest friend invitations to follow only that missionary.

### Tier 3

Tier 3 includes:

1. All Tier 1 and Tier 2 functionality.
2. Fifty AI credits per month.
3. Unlimited friend invitations.

### Limits still to define

Storage limits still need to be defined. The product already has user and
feature limits by tier, but photo storage can materially affect cost and should
be priced deliberately.

### Album monetization

The album should be sold as an add-on purchase, not bundled into the base plan.

### Billing options

The product should support monthly payment, annual payment, and recurring
payment for a defined mission period.

### Scope implication

The plan structure includes social access, but the three-week MVP will not
implement the social layer. Social following remains part of the commercial
roadmap after the journal and family-access MVP is validated. Implementing
social following adds feed, discovery, follow permissions, visibility rules,
moderation, and safety considerations.

## Persona and initial customer

### Primary user

The primary user is a missionary serving a mission of approximately 18 months
to three years. The missionary needs a low-friction way to preserve daily
experiences, names, places, photos, lessons, and meaningful moments while life
in the field is still happening.

### Initial buyer or sponsor

The family is the strongest emotional buyer or sponsor. The missionary creates
the journal, but the family has strong motivation to pay for, encourage, or
support the use of the product because they want to preserve memories and stay
connected to the mission experience.

### Pilot validation

The first validation case is the founder's daughter, with two additional
missionary or family cases available for early feedback.

### Main pains

1. Lack of time to write consistently.
2. Difficulty organizing photos and memories.
3. Difficulty writing well or turning raw experiences into polished records.

### MVP audience statement

The MVP is for missionaries and their families who want to preserve mission
experiences in an organized, private, and emotionally meaningful way.

### Future audience expansion

Over time, the product can expand beyond the mission period to support broader
life progress, secular activity, professional development, and post-mission
memory preservation.

## Value proposition

### Core promise

Organize, preserve, and transform mission experiences into a living legacy for
the missionary and their family.

### Supporting messages

1. Register mission memories without turning journaling into a burden.
2. Keep stories, photos, people, places, scriptures, and lessons in one
   organized place.
3. Help families preserve a meaningful season that cannot be repeated.
4. Prepare today's records to become tomorrow's mission album.
5. Protect private memories while leaving room for future sharing when the user
   chooses.

### Strategic implication

The product should not position itself as a generic diary. It should position
itself as a memory-preservation system for mission life, with the emotional
outcome of legacy and the practical outcome of organization.

## MVP user journey

### First screen after account creation

After creating an account, the user should configure general profile and mission
information. This gives the product enough context to understand the user,
personalize the experience, and make future journal entries more meaningful.

### Onboarding implication

The MVP should include a short onboarding step before the empty journal
timeline. This step should collect only the minimum context needed to support
organized mission records.

### First journal entry experience

The first entry should feel like a free-form writing experience, supported by a
rich text editor. The editor should allow formatted text, photos, captions,
tags, and eventually embeds. A familiar reference is a WordPress-style editor,
where the user can write naturally and add media inside the content.

### Entry header

The entry screen should include a lightweight structured header with:

1. Location.
2. Experience date.
3. Flexible date precision, allowing a full date or only month and year.

The experience date is separate from the creation date because the user may
record memories from a previous day, month, or specific past moment.

### Save behavior

The editor should offer two save actions:

1. Save and return to the timeline.
2. Save and create a new entry.

When the user saves and returns to the timeline, the timeline should show a
simple, modern loading animation before the updated entry appears.

### Photo behavior

The MVP should allow photos to be added while writing the entry, not only after
the text is saved. Photos should support captions because captions help preserve
names, places, and context for future album creation.

### Timeline ordering

The timeline should be organized by the experience date, not the creation date.
This keeps the memory structure aligned with what happened, even when entries
are created later.

### Future timeline roadmap

After the MVP, the timeline can support zoom levels similar to the iPhone Photos
gallery. Users could move from detailed daily entries to broader month and year
views.

## MVP success criteria

### Validation sample

The initial validation should include three testers.

### Usage threshold

Each tester should create at least two real journal entries. This is enough to
validate the create, edit, photo, tag, and timeline flows with real content.

### Usability signal

The MVP succeeds if testers say that the journal is easy and simple to use.

### Emotional signal

The MVP succeeds if the family feels joy from seeing mission memories in one
centralized place and can perceive the missionary's progress.

### Entry creation time

A simple entry should usually take around four to ten minutes. Longer entries
are acceptable because mission stories can naturally vary in depth, detail, and
emotional weight.

### Family validation

The family must see value in the MVP through its own access profile, even if
the first version does not include shared family editing.

### Desired family reaction

The desired reaction is a sense of relief and joy from having centralized access
to the missionary's memories and progress.

## Three-week MVP roadmap

### Week 1: Core journal foundation

**Goal:** A missionary can sign in, configure the mission, invite or define
family access, and create structured journal entries.

1. Set up authentication and authenticated app area.
2. Create missionary and family user profiles.
3. Create the journal entry data model.
4. Add fields for title, body, date, location, tags, photos, and visibility.
5. Build the create and edit entry flows.
6. Store entries as user-owned records with missionary-only or family-visible
   access.
7. Add basic multilingual interface structure.

**Exit criteria:**

- A missionary can create and edit a journal entry.
- Entries can include text, date, location, tags, and photos.
- Entries can be marked as missionary-only or family-visible.
- A family profile can access permitted entries.
- The interface can support at least two languages.

### Week 2: Memory retrieval and timeline experience

**Goal:** A user can revisit memories through an organized, pleasant timeline.

1. Build the timeline view.
2. Show each entry with date, location, tags, photo preview, and text fragment.
3. Add individual entry view.
4. Add filters by date, tag, and location.
5. Improve empty states and basic mobile usability.
6. Validate the manual email capture workflow by pasting email content into an
   entry.

**Exit criteria:**

- A user can browse entries in a timeline.
- A user can filter entries by date, tag, and location.
- A user can open a full entry from the timeline.
- Email content can be manually converted into a useful journal entry.

### Week 3: Polish, validation, and album readiness

**Goal:** The MVP is usable by a real missionary and preserves data needed for
future album creation.

1. Refine the writing experience for long-form mission records.
2. Improve photo handling and display.
3. Add simple prompts or placeholders that encourage useful album-ready
   content, such as people, scripture, learning, and memorable details.
4. Complete multilingual copy for the MVP screens.
5. Run validation with real or sample mission entries.
6. Document future automation opportunities, including inbound email capture
   and album generation.

**Exit criteria:**

- The product can be used for real mission journaling.
- The timeline feels meaningful, not just administrative.
- Journal data is structured enough to support a future album builder.
- The next feature bets are clear.

---

*Session facilitated using the AIOX-Method brainstorming framework.*
