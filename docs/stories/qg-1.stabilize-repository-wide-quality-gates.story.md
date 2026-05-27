---
story_id: "QG.1"
epic: "Technical Quality Gates"
title: "Stabilize Repository-Wide Quality Gates"
status: "Ready for Review"
jira:
  project_key: "MIS"
  project_id: "10148"
  cloud_id: "287a75f4-4fb2-497a-986e-82a5e566d271"
  site_url: "https://vulpelabs.atlassian.net"
  issue_key: ""
  parent_key: ""
  previous_story_key: ""
  issue_type: "História"
  issue_type_id: "10116"
  category: "devtools"
  tags:
    - "story"
    - "project-missionary-journal"
    - "technical-quality"
    - "quality-gates"
    - "devops"
    - "phpstan"
    - "tests"
    - "sail"
    - "pricing"
  labels:
    - "story"
    - "project-missionary-journal"
    - "category-devtools"
    - "technical-quality"
    - "quality-gates"
    - "devops"
    - "phpstan"
    - "tests"
    - "sail"
    - "pricing"
  jira_fields:
    category: "customfield_10053"
    tags: "customfield_10246"
    labels: "labels"
  url: ""
  last_sync: ""
  sync_note: "Local technical story draft only. Jira child story has not been created or synced for QG.1."
---

# Story QG.1: Stabilize Repository-Wide Quality Gates

## Status

Ready for Review

## Executor Assignment

```yaml
executor: "@devops"
quality_gate: "@architect"
quality_gate_tools:
  - "coderabbit doctor"
  - "coderabbit review --agent --type uncommitted"
  - "npm run typecheck"
  - "npm test -- --stop-on-failure"
  - "npm run lint"
  - "vendor/bin/sail artisan test --compact tests/Feature/AppTest.php tests/Feature/Http/Controllers/HomeControllerTest.php"
```

## Story

**As a** release owner,
**I want** the repository-wide quality gates to run reliably from the documented package scripts,
**so that** Story 1.3 and later memory MVP work can enter PR/release review without carrying inherited waiver risk from Stories 1.1 and 1.2.

## Acceptance Criteria

1. `npm run typecheck` completes through Sail without PHPStan cache permission/runtime failures and exits successfully, or any remaining PHPStan debt is explicitly captured in an approved baseline/scope policy.
2. `npm test` runs through Sail with the correct testing database credentials and no longer fails before application assertions because of MySQL `root` access denial against `saasykit_tenancy_test`.
3. The global test suite no longer fails when public pricing renders a usage-based plan without a meter; implementation either hardens the pricing render path or ensures test data cannot create a visible invalid usage-based pricing scenario without an associated meter.
4. Package scripts remain aligned with the repository's Sail-first workflow and the commands documented in `AGENTS.md`.
5. Story-specific focused tests for Stories 1.1, 1.2, and 1.3 remain runnable after the quality gate fixes.
6. CodeRabbit CLI is configured as an active local quality gate and reports no CRITICAL findings for the technical-story changes.

## Integration Verification

1. **IV1:** Existing Story 1.1 memory route access tests still pass after gate stabilization.
2. **IV2:** Existing Story 1.2 memory profile setup and Livewire tests still pass after gate stabilization.
3. **IV3:** Story 1.3 implementation checks can run without requiring a new waiver for inherited repo-wide `typecheck` or `test` failures.
4. **IV4:** CodeRabbit remains runnable from the local repository through `coderabbit doctor` and `coderabbit review --agent`.

## 🤖 CodeRabbit Integration

**Story Type Analysis**

- **Primary Type:** Deployment / DevOps
- **Secondary Types:** Testing, configuration, Laravel quality tooling
- **Complexity:** Medium. The story touches repository-wide scripts, PHPStan execution, test database configuration, and a public pricing render edge case.

**Specialized Agent Assignment**

- **Primary Agents:**
  - `@devops` for package-script, Sail, and repository gate stabilization.
  - `@architect` for quality-gate review and scope validation.
- **Supporting Agents:**
  - `@qa` for final gate evidence and regression-risk review.
  - `@dev` if the pricing render path or test fixtures require product-code changes.

**Quality Gate Tasks**

- [x] CodeRabbit readiness: run `coderabbit doctor` before implementation review.
- [x] Pre-Commit / implementation review: run `coderabbit review --agent --type uncommitted` before marking the story ready for review.
- [x] Pre-PR: run `coderabbit review --agent --base main` before creating the pull request.
- [x] Block release handoff on any remaining CRITICAL CodeRabbit findings unless the project owner explicitly approves a waiver.

**Self-Healing Configuration**

- **Primary Agent:** `@devops` in check mode.
- **Max Iterations:** 0 automatic iterations.
- **Timeout:** Manual review timeout according to DevOps workflow.
- **Severity Filter:** Report all findings; block on CRITICAL.
- **Predicted Behavior:**
  - CRITICAL issues: block PR/release handoff until fixed or explicitly waived.
  - HIGH issues: document in the gate and recommend fix before merge.
  - MEDIUM/LOW issues: document as follow-up debt if outside this story's scope.

**CodeRabbit Focus Areas**

- Repository script safety for `npm run typecheck`, `npm test`, and `npm run lint`.
- Sail/testing environment configuration and credential handling.
- PHPStan configuration, cache/runtime behavior, and baseline/scope policy.
- Public pricing render path around usage-based plans and nullable meters.
- Regression risk to memory Story 1.1, Story 1.2, and Story 1.3 focused checks.

## Tasks / Subtasks

- [x] Stabilize repository-wide PHPStan execution. (AC: 1)
  - [x] Reproduce `npm run typecheck` from a clean Sail environment.
  - [x] Identify whether the failure is cache permissions, PHPStan timeout/runtime behavior, real static-analysis findings, or a combination.
  - [x] Fix transient cache or runtime configuration so PHPStan can complete consistently.
  - [x] If real legacy findings remain, either fix them or introduce an explicit approved baseline/scope policy with rationale.
  - [x] Keep focused PHPStan checks for memory story files passing.

- [x] Stabilize the default repository test script. (AC: 2, 4)
  - [x] Reproduce `npm test -- --stop-on-failure` using the package script.
  - [x] Align the package script, `.env.testing`, or Sail test invocation with the working credentials used by QA gates.
  - [x] Confirm the script does not use MySQL `root` without the required password for the test database.
  - [x] Keep the script compatible with local Sail usage and CI/PR enforcement.

- [x] Fix or isolate the public pricing usage-based meter failure. (AC: 3)
  - [x] Reproduce the global suite failure where a visible usage-based plan renders without an associated meter.
  - [x] Inspect the public pricing component and test data paths that create usage-based plans.
  - [x] Choose the narrowest defensible fix: add a render guard for nullable meters, enforce meter presence for visible usage-based plans, or update test fixtures/factories to produce valid usage-based plans.
  - [x] Add or update focused coverage so the selected behavior is locked.
  - [x] Avoid changing billing, checkout, payment provider, or plan semantics outside the specific invalid meter scenario.

- [x] Preserve memory MVP story validation. (AC: 5; IV: 1, 2, 3)
  - [x] Rerun Story 1.1 focused memory route tests.
  - [x] Rerun Story 1.2 focused memory profile and Livewire tests.
  - [x] Rerun Story 1.3 focused tests if Story 1.3 has been implemented before this technical story is picked up. Not applicable: Story 1.3 implementation test files are not present yet.
  - [x] Confirm the remaining Story 1.3 PR/release path does not need a reused waiver from Stories 1.1 or 1.2.

- [x] Run CodeRabbit local quality gates. (AC: 6; IV: 4)
  - [x] Confirm local CLI readiness with `coderabbit doctor`.
  - [x] Run `coderabbit review --agent --type uncommitted` before story review.
  - [x] Run `coderabbit review --agent --base main` before PR creation.
  - [x] Fix or explicitly account for any CRITICAL findings before release handoff.

## Dev Notes

### Source Gate Context

- Story 1.1 gate is `WAIVED` because story-scoped behavior passed while repository-wide `npm run typecheck` and `npm test` failures were accepted as external debt. [Source: docs/qa/gates/1.1-tenant-safe-memory-route-and-layout-shell.yml#top_issues]
- Story 1.2 gate is `CONCERNS` because story-focused implementation, focused tests, auth regression tests, lint, route inspection, and focused PHPStan passed while repository-wide `npm run typecheck` and global `npm test` remained unreliable. [Source: docs/qa/gates/1.2-memory-profile-setup-gate.yml#status_reason]
- The Story 1.2 waiver is scoped to previous publication only and lists repository-wide PHPStan debt, global pricing test failure, and local config/doc findings as outside Story 1.2 behavior. [Source: docs/qa/gates/1.2-memory-profile-setup-gate.yml#waiver]
- Story 1.3 already warns that repo-wide `npm run typecheck` and `npm test` still have known debt and must be rerun or explicitly accounted for before PR/release policy requires full green checks. [Source: docs/stories/1.3.workspace-settings-and-shared-editing-policy-foundation.story.md#testing]

### Existing Codebase Context

- `package.json` defines `typecheck` as `vendor/bin/sail up -d laravel.test && vendor/bin/sail bin phpstan analyse --memory-limit=1G`.
- `package.json` defines `test` as `vendor/bin/sail up -d laravel.test && vendor/bin/sail exec -T -e APP_ENV=testing -e DB_HOST=mysql laravel.test php artisan test --compact`.
- QA evidence shows focused Sail test commands passed when `DB_DATABASE=testing`, `DB_USERNAME=sail_missionaryjournal`, and `DB_PASSWORD=password_missionaryjournal` were supplied explicitly.
- The public pricing Blade component dereferences `$plan->meter->name` for usage-based plan price display.
- The usage-based billing migration keeps `plans.meter_id` nullable, so either the render path must tolerate missing meters or the data/model boundary must prevent visible usage-based plans without meters.
- CodeRabbit CLI is installed at `/home/jose/.local/bin/coderabbit`. `coderabbit doctor` passed locally on 2026-05-25 with 9 checks passing, including authentication, repository readiness, backend reachability, and WebSocket reachability.

### Scope Boundaries

- Do not implement Story 1.3 workspace settings as part of this technical story.
- Do not implement Story 1.4 diary record creation or any memory record CRUD.
- Do not broaden payment provider behavior unless required to fix the specific invalid usage-based pricing scenario.
- Do not replace Sail-first scripts with non-Sail local commands.
- Do not rely on a new waiver unless PO/project owner explicitly approves it for this technical story or Story 1.3 publication.

### Testing

- Required verification commands:
  - `coderabbit doctor`
  - `coderabbit review --agent --type uncommitted`
  - `coderabbit review --agent --base main`
  - `npm run typecheck`
  - `npm test -- --stop-on-failure`
  - `npm run lint`
  - `vendor/bin/sail artisan test --compact tests/Feature/AppTest.php tests/Feature/Http/Controllers/HomeControllerTest.php`
  - `vendor/bin/sail artisan test --compact tests/Feature/Memory/MemoryRouteAccessTest.php`
  - `vendor/bin/sail artisan test --compact tests/Feature/Memory/MemoryProfileSetupGateTest.php tests/Feature/Livewire/Memory/MemoryProfileSetupTest.php`
- If Story 1.3 is implemented before this story, also run:
  - `vendor/bin/sail artisan test --compact tests/Feature/Services/Memory/MemoryWorkspaceSettingsServiceTest.php`
  - `vendor/bin/sail artisan test --compact tests/Feature/Memory/MemoryProfileSetupGateTest.php tests/Feature/Memory/MemoryRouteAccessTest.php`

## Change Log

| Date | Version | Description | Author |
| --- | --- | --- | --- |
| 2026-05-25 | 0.3 | Approved by PO after validate-story-draft readiness review. | Pax |
| 2026-05-25 | 0.2 | Revised CodeRabbit planning after confirming local CLI installation and readiness. | River |
| 2026-05-25 | 0.1 | Initial technical story draft from QA gate findings for Stories 1.1 and 1.2. | River |
| 2026-05-26 | 1.0 | Implemented Sail quality gate stabilization, PHPStan baseline policy, pricing null-meter guards, and regression evidence. | Dex |

## Dev Agent Record

### Agent Model Used

GPT-5 Codex (AIOX Dev / Dex)

### Debug Log References

- `npm run lint` -> passed (`pint --dirty --format=agent`).
- `npm run typecheck` -> passed (`478/478`, `[OK] No errors`) with explicit PHPStan baseline.
- `npm test` -> passed (`414 passed`, `999 assertions`, exit code 0).
- `npm test -- --stop-on-failure` -> passed (`414 passed`, `999 assertions`, exit code 0).
- `vendor/bin/sail artisan test --compact tests/Feature/AppTest.php tests/Feature/Http/Controllers/HomeControllerTest.php` -> passed (`7 passed`, `10 assertions`).
- `vendor/bin/sail artisan test --compact tests/Feature/Memory/MemoryRouteAccessTest.php` -> passed (`5 passed`, `24 assertions`).
- `vendor/bin/sail artisan test --compact tests/Feature/Memory/MemoryProfileSetupGateTest.php tests/Feature/Livewire/Memory/MemoryProfileSetupTest.php` -> passed (`11 passed`, `39 assertions`).
- Story 1.3 optional focused tests were not run because `tests/Feature/Services/Memory/MemoryWorkspaceSettingsServiceTest.php` and `tests/Feature/Memory/MemoryWorkspaceSettingsTest.php` are not present yet.
- `coderabbit doctor` -> passed (`9 passed`, `0 warnings`, `0 failed`).
- `coderabbit review --agent --type uncommitted` -> completed with 12 findings. CRITICAL findings were on unrelated dirty workspace/config/story artifacts; QG.1 runtime changes had no CRITICAL finding. QG.1 PHPStan baseline size was reported as MAJOR and is tracked as explicit baseline policy debt for future reduction.
- `coderabbit review --agent --base main` -> completed with 19 findings. CRITICAL findings were broader branch/worktree debt in skill docs, MCP/config files, and handoff metadata; QG.1 handoff metadata was refined to separate external CodeRabbit checks from executable dev checks.
- `git diff --check` -> passed.
- Story DoD checklist -> completed; no new dependencies were added, and user-facing documentation/build artifacts were not applicable to this technical gate story.

### Completion Notes List

- `npm run typecheck` now completes under Sail by including a generated `phpstan-baseline.neon` for legacy static-analysis debt. New PHPStan execution is green, and the baseline is intentionally explicit rather than an implicit waiver.
- `npm test` and `npm test -- --stop-on-failure` now create/refresh the Sail testing database and execute with explicit `testing` credentials, avoiding root-access failures and stale PHPUnit result-cache exits.
- Public and Filament plan cards now tolerate visible usage-based plans without a meter by hiding meter-specific usage text instead of dereferencing `null`.
- PHPUnit no longer discovers helper-only test classes as empty tests; the base feature test and placeholder unit file are excluded from suites.
- `UserFactory` now generates UUID-backed example emails to avoid duplicate faker unique values during long full-suite runs.
- CodeRabbit CLI is operational, but current branch/worktree CodeRabbit reviews still report CRITICAL findings in unrelated pre-existing documentation/config artifacts. Those are not fixed in QG.1 scope and should be handled before release handoff if the broader branch is published.

### File List

- `.aiox/handoffs/20260525T110602-0300-po-to-dev-qg-1-quality-gates.yaml`
- `compose.yml`
- `database/factories/UserFactory.php`
- `docs/stories/qg-1.stabilize-repository-wide-quality-gates.story.md`
- `package.json`
- `phpstan-baseline.neon`
- `phpstan.neon`
- `phpunit.xml`
- `resources/views/components/filament/plans/one.blade.php`
- `resources/views/components/plans/one.blade.php`
- `tests/Feature/AppTest.php`
- `tests/Feature/FeatureTest.php`
- `tests/Unit/ExampleTest.php`

## QA Results

### Review Date: 2026-05-27

### Reviewed By: Quinn (Test Architect)

### Code Quality Assessment

QG.1 successfully stabilizes the previously failing repository gates: `npm run typecheck` passes with an explicit PHPStan baseline policy, `npm test -- --stop-on-failure` now provisions and uses the `testing` database credentials, and the public pricing nullable-meter regression is covered by a focused home-page test. The implementation is narrow and keeps the fix out of billing, checkout, and payment-provider semantics.

Gate result is `CONCERNS`, not `FAIL`, because current automated evidence is green for QG.1 runtime behavior and CodeRabbit reported no CRITICAL issue in the QG.1 runtime scope. The concern is release-governance clarity: CodeRabbit flagged that QG.1 should state more explicitly that scoped QG.1 changes are release-ready when scoped checks pass, while unrelated dirty workspace or broader branch CRITICAL findings still require separate remediation or project-owner waiver before branch publication.

### Refactoring Performed

None. QA did not modify production code during this review.

### Compliance Check

- Coding Standards: ✓ `npm run lint` passed through Sail/Pint.
- Project Structure: ✓ Changes stay within existing Sail, PHPStan, PHPUnit, Blade, factory, and story/gate paths.
- Testing Strategy: ✓ Full PHPUnit suite passed; pricing nullable-meter regression is covered in `tests/Feature/AppTest.php`.
- All ACs Met: ✓ AC1-AC6 are covered, with DOC-001 tracked as a non-blocking release-policy concern.

### Requirements Traceability

- AC1: Covered by `npm run typecheck`; PHPStan analysed 478 files with no errors using explicit baseline policy.
- AC2: Covered by `npm test -- --stop-on-failure`; package script creates/refreshes the testing database and injects testing credentials.
- AC3: Covered by `test_usage_based_pricing_without_meter_does_not_break_home_page` and Blade review of public/Filament nullable-meter guards.
- AC4: Covered by package script review; lint/typecheck/test remain Sail-first.
- AC5: Covered by the full PHPUnit suite passing (`414 passed`, `999 assertions`), including existing memory story feature tests.
- AC6: Covered with concerns by `coderabbit doctor`, `coderabbit review --agent --type uncommitted`, and `coderabbit review --agent --base main`; no CRITICAL QG.1 runtime finding was reported, but one QG.1 policy-documentation finding remains and `CLAUDE.md` has an out-of-scope CRITICAL branch-publication finding.

### Improvements Checklist

- [x] Verified CodeRabbit CLI readiness with `coderabbit doctor`.
- [x] Ran CodeRabbit uncommitted review and classified findings by QG.1 scope.
- [x] Verified lint, PHPStan, full PHPUnit, and whitespace checks locally.
- [x] Created the QG.1 quality gate file.
- [ ] Update non-QA story/handoff wording to codify scoped release-readiness and out-of-scope CodeRabbit waiver handling before branch publication.
- [ ] Reduce `phpstan-baseline.neon` incrementally in future quality-gate work.

### Security Review

No new security concern found in QG.1 runtime changes. Out-of-scope CodeRabbit findings in synced skill documentation and Story 1.3 command text should be handled separately if those files ship in the same publication branch.

### Performance Considerations

No blocking performance issue found. The pricing Blade guard prevents the nullable-meter render failure; a future cleanup can eager-load `meter` with visible plans to avoid lazy-loading if pricing card counts grow.

### Files Modified During Review

- `docs/qa/gates/qg-1-stabilize-repository-wide-quality-gates.yml`
- `docs/stories/qg-1.stabilize-repository-wide-quality-gates.story.md` (QA Results section only)

### Gate Status

Gate: CONCERNS → docs/qa/gates/qg-1-stabilize-repository-wide-quality-gates.yml

### Recommended Status

✓ Ready for Done with concerns. Before DevOps publishes the broader branch, out-of-scope CodeRabbit findings included in that branch should be remediated or explicitly project-owner-waived.
