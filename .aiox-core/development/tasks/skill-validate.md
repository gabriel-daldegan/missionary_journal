# Skill Validate

**Task ID:** skill-validate
**Agent:** skill-standardizer
**Purpose:** Verify that Codex/OpenAI skills comply with local standards after audit or normalization.

## Inputs

- `scope`: `local`, `global`, or `all` (default: `all`)
- `strict`: optional. Treat warnings as failures.

## Procedure

1. Run:
   - `node .aiox-core/development/scripts/skill-standards-auditor.js --scope all`
2. If validating AIOX-generated local skills, also run when available:
   - `node .aiox-core/infrastructure/scripts/codex-skills-sync/validate.js`
3. Confirm the checklist in `checklists/skill-standardization-checklist.md`.
4. Inspect any remaining failures directly.

## Pass Criteria

- Every scanned skill has valid YAML frontmatter.
- Every scanned skill has `name` and `description`.
- Every `description` is <= 1024 characters.
- Every `SKILL.md` body is <= 500 lines or has a documented exception.
- No global skill was rewritten without explicit approval.

## Output

Report:
- validation commands run
- pass/fail status
- unresolved files and exact issues
- recommended handoff if another agent must act
