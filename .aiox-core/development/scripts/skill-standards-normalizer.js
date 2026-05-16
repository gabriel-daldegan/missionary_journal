#!/usr/bin/env node
'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const yaml = require('js-yaml');

const PROJECT_ROOT = process.cwd();
const AUDITOR_PATH = path.join(PROJECT_ROOT, '.aiox-core', 'development', 'scripts', 'skill-standards-auditor.js');

const DESCRIPTION_REPLACEMENTS = new Map(Object.entries({
  'guimkt-brandformance-planner': 'Plans brandformance strategy that connects brand positioning with measurable acquisition, creative systems, media planning, funnel stages, and growth metrics. Use when aligning brand and performance marketing, designing campaign architecture, choosing KPIs, or balancing demand creation with demand capture.',
  'guimkt-conversion-qa-auditor': 'Audits conversion journeys before launch, covering tracking, forms, checkout, CRM handoff, UX friction, message match, mobile behavior, consent, and analytics signals. Use when validating landing pages, campaigns, funnels, lead capture flows, or post-click experiences before media spend.',
  'guimkt-landing-page-optimization': 'Audits and improves landing pages using conversion UX, LPO process, and persuasive copywriting. Use when analyzing or creating landing pages, improving headlines, CTAs, proof sections, message match, CRO hypotheses, awareness levels, friction diagnosis, A/B test ideas, page wireframes, squeeze pages, or sales-page structure.',
  'guimkt-make-blueprint-expert': 'Designs and reviews Make.com automation blueprints for marketing, CRM, analytics, lead routing, data enrichment, and operational workflows. Use when planning scenarios, modules, routers, webhooks, error handling, idempotency, field mapping, API integrations, or production-ready automation documentation.',
  'guimkt-measurement-plan-architect': 'Defines measurement plans for marketing and revenue analytics, including GA4 events, conversions, attribution, CRM fields, consent-aware tracking, and implementation checklists. Use when creating or auditing tracking architecture, event taxonomies, funnel measurement, conversion definitions, GTM/GA4 plans, offline conversion imports, or revenue attribution.',
  'guimkt-offer-diagnosis': 'Diagnoses and improves marketing offers by checking audience, promise, mechanism, proof, risk reversal, urgency, pricing, bonuses, and conversion friction. Use when an offer is not converting, needs repositioning, requires a stronger value proposition, or must be prepared for ads, landing pages, launches, or sales pages.',
  'guimkt-sales-page-message-mining': 'Extracts and organizes customer language for sales pages, landing pages, ads, and offers. Use when mining reviews, calls, surveys, testimonials, communities, support logs, or competitor pages to find pains, desires, objections, proof points, hooks, claims, and voice-of-customer patterns.',
  'guimkt-utm-governance': 'Creates UTM governance for paid media, CRM attribution, GA4 channel grouping, and WhatsApp-first funnels. Use when defining naming conventions, UTM templates, URL validation rules, campaign taxonomy, traffic source cleanup, CTWA/WABA referral tracking, BSUID capture, or auditing messy UTMs and unassigned GA4 traffic.',
  'tlc-spec-driven': 'Supports spec-driven project and feature work through Specify, Design, Tasks, and Implement/Validate phases. Use when initializing a project, mapping an existing codebase, specifying requirements, designing a feature, creating task breakdowns, implementing with verification, or pausing and resuming work.',
}));

function parseArgs(argv = process.argv.slice(2)) {
  const options = {
    scope: 'all',
    dryRun: false,
  };

  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];

    if (arg === '--scope') {
      options.scope = argv[index + 1] || options.scope;
      index += 1;
    } else if (arg.startsWith('--scope=')) {
      options.scope = arg.slice('--scope='.length);
    } else if (arg === '--dry-run') {
      options.dryRun = true;
    }
  }

  if (!['local', 'global', 'all'].includes(options.scope)) {
    throw new Error(`Invalid scope "${options.scope}". Use local, global, or all.`);
  }

  return options;
}

function getRoots(scope) {
  const roots = [];

  if (scope === 'local' || scope === 'all') {
    roots.push(path.join(PROJECT_ROOT, '.codex', 'skills'));
  }

  if (scope === 'global' || scope === 'all') {
    roots.push(path.join(os.homedir(), '.codex', 'skills'));
  }

  return roots;
}

function findSkillFiles(root) {
  if (!fs.existsSync(root)) {
    return [];
  }

  return fs.readdirSync(root, { withFileTypes: true })
    .filter((entry) => entry.isDirectory())
    .map((entry) => path.join(root, entry.name, 'SKILL.md'))
    .filter((filePath) => fs.existsSync(filePath));
}

function parseFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n?/);

  if (!match) {
    return {
      data: {},
      body: content,
      valid: false,
    };
  }

  try {
    return {
      data: yaml.load(match[1]) || {},
      body: content.slice(match[0].length),
      valid: true,
    };
  } catch (_error) {
    const recoveredData = recoverInvalidFrontmatter(match[1]);

    if (recoveredData) {
      return {
        data: recoveredData,
        body: content.slice(match[0].length),
        valid: false,
      };
    }

    return {
      data: parseInvalidFrontmatter(match[1]),
      body: content.slice(match[0].length),
      valid: false,
    };
  }
}

function recoverInvalidFrontmatter(frontmatter) {
  const sanitized = frontmatter.replace(/^description:\s*(.+)$/m, (_match, value) => {
    return `description: ${JSON.stringify(value.trim())}`;
  });

  try {
    return yaml.load(sanitized) || null;
  } catch (_error) {
    return null;
  }
}

function parseInvalidFrontmatter(frontmatter) {
  const data = {};

  for (const line of frontmatter.split(/\r?\n/)) {
    const match = line.match(/^([a-zA-Z][\w-]*):\s*(.*)$/);

    if (!match) {
      continue;
    }

    const [, key, rawValue] = match;

    if (key === 'description') {
      continue;
    }

    const value = rawValue.trim().replace(/^"|"$/g, '');
    data[key] = value;
  }

  return data;
}

function collapseDescription(value) {
  return String(value || '')
    .replace(/\s+/g, ' ')
    .trim();
}

function titleFromName(name) {
  return String(name || 'skill')
    .split('-')
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function shouldCompactBody(parsed, content) {
  const bodyLines = parsed.body.split(/\r?\n/).length;

  return bodyLines > 500 || content.length > 50000;
}

function compactBody(name, description) {
  const title = titleFromName(name);

  return `# ${title}

## Activation

Use this skill when the user request matches the frontmatter description.

## Workflow

1. Clarify the requested output, target context, and constraints.
2. Inspect the relevant sections of \`references/original-skill.md\` before substantive work.
3. Follow the preserved domain workflow from the reference material.
4. Keep outputs concise, actionable, and grounded in the user's supplied artifacts.
5. Report assumptions, risks, and validation steps when they affect the result.

## Progressive Disclosure

- \`references/original-skill.md\` preserves the full pre-normalization instructions and examples.
- Load only the sections needed for the current task.
- Do not duplicate the reference material in the response unless the user asks for it.

## Summary

${description}
`;
}

function dumpFrontmatter(data) {
  return `---\n${yaml.dump(data, {
    lineWidth: -1,
    noRefs: true,
    sortKeys: false,
  })}---\n\n`;
}

function normalizeSkill(filePath, options) {
  const originalContent = fs.readFileSync(filePath, 'utf8');
  const parsed = parseFrontmatter(originalContent);
  const skillDir = path.dirname(filePath);
  const name = parsed.data.name || path.basename(skillDir);
  const replacementDescription = DESCRIPTION_REPLACEMENTS.get(name);
  const currentDescription = collapseDescription(parsed.data.description);
  const description = replacementDescription || currentDescription;
  const data = {
    ...parsed.data,
    name,
    description,
  };
  let body = parsed.body;
  const changes = [];

  if (!parsed.valid) {
    changes.push('rebuilt invalid YAML frontmatter');
  }

  if ((replacementDescription && replacementDescription !== currentDescription) || currentDescription !== parsed.data.description) {
    changes.push('normalized description');
  }

  if (shouldCompactBody(parsed, originalContent)) {
    const referencesDir = path.join(skillDir, 'references');
    const originalPath = path.join(referencesDir, 'original-skill.md');

    if (!options.dryRun) {
      fs.mkdirSync(referencesDir, { recursive: true });

      if (!fs.existsSync(originalPath)) {
        fs.writeFileSync(originalPath, originalContent, 'utf8');
      }
    }

    body = compactBody(name, description);
    changes.push(`compacted body and preserved original in ${path.relative(skillDir, originalPath)}`);
  }

  const nextContent = dumpFrontmatter(data) + body.replace(/^\s+/, '');

  if (nextContent !== originalContent && !options.dryRun) {
    fs.writeFileSync(filePath, nextContent.endsWith('\n') ? nextContent : `${nextContent}\n`, 'utf8');
  }

  return {
    filePath,
    changed: nextContent !== originalContent,
    changes,
  };
}

function main() {
  const options = parseArgs();
  const results = [];

  for (const root of getRoots(options.scope)) {
    for (const filePath of findSkillFiles(root)) {
      const content = fs.readFileSync(filePath, 'utf8');
      const parsed = parseFrontmatter(content);
      const name = parsed.data.name || path.basename(path.dirname(filePath));
      const description = String(parsed.data.description || '');
      const replacementDescription = DESCRIPTION_REPLACEMENTS.get(name);
      const hasDescriptionIssue = (replacementDescription && collapseDescription(description) !== replacementDescription) || description.includes('\n');
      const hasBodyIssue = shouldCompactBody(parsed, content);

      if (!parsed.valid || hasDescriptionIssue || hasBodyIssue) {
        results.push(normalizeSkill(filePath, options));
      }
    }
  }

  const changed = results.filter((result) => result.changed);

  console.log(JSON.stringify({
    dryRun: options.dryRun,
    inspected: results.length,
    changed: changed.length,
    results: changed,
    auditor: AUDITOR_PATH,
  }, null, 2));
}

main();
