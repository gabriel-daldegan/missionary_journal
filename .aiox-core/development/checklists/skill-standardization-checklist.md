# Skill Standardization Checklist

Use this checklist before declaring a Codex/OpenAI skill cleanup complete.

## Frontmatter

- [ ] `SKILL.md` starts with YAML frontmatter.
- [ ] `name` exists and is kebab-case.
- [ ] `description` exists.
- [ ] `description` is <= 1024 characters.
- [ ] `description` says what the skill does and when to use it.
- [ ] `description` includes natural trigger phrases.
- [ ] `description` has no XML tags.

## Body

- [ ] Body is focused on actionable workflow.
- [ ] Body is <= 500 lines or has a documented exception.
- [ ] Long background material moved to `references/`.
- [ ] Repeatable deterministic logic moved to `scripts/`.
- [ ] Reusable output files moved to `assets/`.
- [ ] Instructions do not duplicate common agent knowledge.

## Package Hygiene

- [ ] Folder name matches frontmatter `name`, or mismatch is documented.
- [ ] Relative references resolve from the skill directory.
- [ ] No secrets or credentials are present.
- [ ] No generated third-party skill was overwritten without approval.
- [ ] Global `~/.codex/skills` writes were explicitly approved.

## Validation

- [ ] `node .aiox-core/development/scripts/skill-standards-auditor.js --scope all` was run.
- [ ] AIOX generated skills passed `node .aiox-core/infrastructure/scripts/codex-skills-sync/validate.js` when applicable.
- [ ] Findings were reported with exact file paths.
