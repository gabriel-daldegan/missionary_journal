# Skill Audit

**Task ID:** skill-audit
**Agent:** skill-standardizer
**Purpose:** Audit installed Codex/OpenAI skills for size, frontmatter, and structure issues without modifying files.

## Inputs

- `scope`: `local`, `global`, or `all` (default: `all`)
- `strict`: optional. Treat warnings as failures.
- `json`: optional. Return machine-readable output.

## Procedure

1. Identify skill roots:
   - `local`: `.codex/skills`
   - `global`: `~/.codex/skills`
   - `all`: both roots
2. Run the deterministic auditor:
   - `node .aiox-core/development/scripts/skill-standards-auditor.js --scope all`
   - Use `--json` when another agent or script will consume the result.
3. Inspect only flagged `SKILL.md` files.
4. Classify findings:
   - **Error:** missing/invalid frontmatter, missing `name`, missing `description`, description over 1024 characters.
   - **Warning:** body over 500 lines, file too large, folder/name mismatch, weak or multi-line description.
5. Produce a concise remediation queue sorted by:
   - errors before warnings
   - global skills before local only when the global skill is the active installed copy
   - largest description/body overage first

## Output

Report:
- scanned roots
- total skills scanned
- errors and warnings count
- top offending files with exact violated limits
- recommended next command, usually `*normalize {path}` or `*normalize all --plan`

## Safety

This task is read-only. Do not edit skills during audit.
