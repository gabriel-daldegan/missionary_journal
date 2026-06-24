# MVP validation runbook

Use this runbook to validate the Memory Timeline MVP with real operators and
validation tenants. The goal is to confirm whether users complete setup, create
records, return over time, invite family or collaborators, attach photos, and
use the timeline without exposing private memory content.

## Operator checklist

Run these checks from the admin-only validation surface:

1. Sign in as an admin user.
2. Open **Admin**.
3. Open **Validation Report** under **Product Management**.
4. Record the aggregate counts for active validation tenants, completed
   profiles, tenants with activity over two weeks, collaborator engagement,
   media attachment usage, and optional email-source markers.
5. Do not copy private diary bodies, notes, highlight text, location free text,
   media filenames, media paths, or tenant-private notes into validation notes.

The reporting surface is `App\Filament\Admin\Pages\MemoryValidationReport`.
Workspace users must not access it.

## Registration and email verification

Validate access setup with these steps:

1. Register a new user through the normal application registration flow.
2. Verify the user's email address.
3. Confirm that an unverified user cannot enter the authenticated memory area
   before email verification.
4. Confirm that a verified user can reach the tenant memory route after tenant
   membership is available.

Capture whether validation participants complete verification without help. If
they need assistance, record the friction as validation feedback.

## Memory profile and language selection

Validate onboarding with these steps:

1. Open the memory profile setup route for a tenant.
2. Enter the display name and mission context.
3. Select English, Portuguese, or Spanish.
4. Save the profile.
5. Confirm that the memory shell uses the selected language for navigation and
   interface labels.
6. Confirm that user-generated memory content is not translated.

Use the validation report to confirm that profile completion increases for the
tenant.

## Diary creation

Validate diary capture with these steps:

1. Open the tenant memory timeline.
2. Start a new diary record.
3. Enter a diary body and experience date.
4. Optional: add tags, highlights, and a location.
5. Save the record.
6. Confirm that the record appears in the correct month group on the timeline.
7. Confirm that the full diary body remains on the record detail surface, not
   in the validation report.

Use the validation report to confirm that first record creation and active
validation tenant counts update.

## Trip and period creation

Validate period capture with these steps:

1. Open the tenant memory timeline.
2. Start a new trip or period record.
3. Enter a title, start date, and end date.
4. Optional: add notes, people, tags, highlights, and a location.
5. Save the record.
6. Confirm that the record appears by period start date in the month-grouped
   timeline.
7. Confirm that private notes are not shown in the validation report.

Use the validation report to confirm record activity across the tenant without
copying record content into validation notes.

## Private photo attachment

Validate media behavior with these steps:

1. Create or edit a diary or trip/period record.
2. Attach one or more supported photo files.
3. Save the record.
4. Confirm that tenant members can view the photo through the authorized media
   route.
5. Confirm that non-members and members of other tenants cannot view the photo.
6. Confirm that private media filenames and storage paths do not appear in the
   validation report.

Use the validation report only for aggregate media usage counts.

## Timeline browsing and filters

Validate timeline browsing with these steps:

1. Open the tenant memory timeline.
2. Confirm records are grouped by month and ordered by the correct timeline
   date.
3. Filter by date range.
4. Filter by tag.
5. Filter by location.
6. Clear filters and confirm the full tenant timeline returns.
7. Apply a filter that has no matches and confirm the no-results state appears.

Date and tag filters may be URL-backed. Location search is transient and must
not be treated as shareable state in validation notes.

## Tenant access and family workspace use

Validate workspace access with these steps:

1. Invite or add a family member or collaborator to the tenant workspace.
2. Confirm the new member can access the tenant memory timeline.
3. Confirm a user outside the tenant receives a generic denial without tenant
   names or private record content.
4. Confirm the collaborator can create a record when shared editing is active.
5. Confirm collaborator activity appears only as aggregate engagement in the
   validation report.

Use the validation report to confirm member count, collaborator participants,
and cross-edit activity.

## Shared editing

Validate shared editing with these steps:

1. Sign in as one tenant member and create a record.
2. Sign in as a different tenant member.
3. Edit the same record.
4. Confirm the record remains tenant-scoped and visible only to members.
5. Confirm the validation report counts collaborator engagement without showing
   the record body, notes, highlights, private locations, filenames, or paths.

Shared editing is the MVP collaboration mode. Do not turn requests for
per-record visibility controls into MVP success criteria.

## Optional email-source marker

If the non-blocking email-source marker is present, validate it with these
steps:

1. Create a diary record from copied mission email content.
2. Mark the record with the simple email source marker if the UI or import flow
   provides one.
3. Confirm the validation report counts email-source records as an aggregate.
4. Confirm email headers, recipients, subject lines, original filenames, and raw
   email content are not stored or displayed for validation reporting.

Manual copy and paste into a diary body remains valid MVP behavior even when
the email-source marker is not present.

## Post-MVP request capture

Capture post-MVP requests separately from MVP validation results. Common
post-MVP requests include:

- Automated inbound email capture.
- AI metadata extraction or summaries.
- Per-record missionary-only or family-visible access controls.
- Public sharing, visitor access, or social feeds.
- Album generation, print layouts, maps, masonry browsing, or advanced life
  graphs.

Record each request with the requester, tenant, date, context, and expected
outcome. Do not count a post-MVP request as an MVP failure unless it blocks the
required MVP validation flows in this runbook.
