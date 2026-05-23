#!/usr/bin/env node
'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const yaml = require('js-yaml');

const LIMITS = {
  descriptionChars: 1024,
  bodyLines: 500,
  fileChars: 50000,
};

function parseArgs(argv = process.argv.slice(2)) {
  const options = {
    scope: 'all',
    json: false,
    strict: false,
  };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];

    if (arg === '--json') {
      options.json = true;
    } else if (arg === '--strict') {
      options.strict = true;
    } else if (arg === '--scope') {
      options.scope = argv[i + 1] || options.scope;
      i += 1;
    } else if (arg.startsWith('--scope=')) {
      options.scope = arg.slice('--scope='.length);
    }
  }

  if (!['local', 'global', 'all'].includes(options.scope)) {
    throw new Error(`Invalid scope "${options.scope}". Use local, global, or all.`);
  }

  return options;
}

function getRoots(projectRoot, scope) {
  const roots = [];

  if (scope === 'local' || scope === 'all') {
    roots.push({
      scope: 'local',
      root: path.join(projectRoot, '.codex', 'skills'),
    });
  }

  if (scope === 'global' || scope === 'all') {
    roots.push({
      scope: 'global',
      root: path.join(os.homedir(), '.codex', 'skills'),
    });
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
      data: null,
      body: content,
      error: 'Missing YAML frontmatter',
    };
  }

  try {
    return {
      data: yaml.load(match[1]) || {},
      body: content.slice(match[0].length),
      error: null,
    };
  } catch (error) {
    return {
      data: null,
      body: content.slice(match[0].length),
      error: `Invalid YAML frontmatter: ${error.message}`,
    };
  }
}

function isKebabCase(value) {
  return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(String(value || ''));
}

function hasXmlTag(value) {
  return /<\/?[a-z][\w:-]*(?:\s[^>]*)?>/i.test(String(value || ''));
}

function auditSkill(filePath, rootInfo, projectRoot) {
  const content = fs.readFileSync(filePath, 'utf8');
  const parsed = parseFrontmatter(content);
  const relativePath = path.relative(projectRoot, filePath);
  const folderName = path.basename(path.dirname(filePath));
  const result = {
    scope: rootInfo.scope,
    path: relativePath.startsWith('..') ? filePath : relativePath,
    name: null,
    descriptionChars: 0,
    bodyLines: 0,
    fileChars: content.length,
    errors: [],
    warnings: [],
  };

  if (parsed.error) {
    result.errors.push(parsed.error);
    return result;
  }

  const name = parsed.data.name;
  const description = parsed.data.description;
  result.name = name || null;
  result.descriptionChars = String(description || '').length;
  result.bodyLines = parsed.body.split(/\r?\n/).length;

  if (!name) {
    result.errors.push('Missing frontmatter field: name');
  } else if (!isKebabCase(name)) {
    result.errors.push(`Skill name is not kebab-case: ${name}`);
  }

  if (!description) {
    result.errors.push('Missing frontmatter field: description');
  } else {
    if (result.descriptionChars > LIMITS.descriptionChars) {
      result.errors.push(`Description is ${result.descriptionChars} chars; limit is ${LIMITS.descriptionChars}`);
    }

    if (String(description).includes('\n')) {
      result.warnings.push('Description should be a single concise paragraph');
    }

    if (hasXmlTag(description)) {
      result.errors.push('Description contains XML/HTML tags');
    }
  }

  if (name && folderName !== name) {
    result.warnings.push(`Folder name "${folderName}" does not match frontmatter name "${name}"`);
  }

  if (result.bodyLines > LIMITS.bodyLines) {
    result.warnings.push(`Body is ${result.bodyLines} lines; recommended limit is ${LIMITS.bodyLines}`);
  }

  if (result.fileChars > LIMITS.fileChars) {
    result.warnings.push(`File is ${result.fileChars} chars; consider moving detail to references/`);
  }

  return result;
}

function audit(options = {}) {
  const projectRoot = options.projectRoot || process.cwd();
  const roots = getRoots(projectRoot, options.scope || 'all');
  const results = [];
  const missingRoots = [];

  for (const rootInfo of roots) {
    if (!fs.existsSync(rootInfo.root)) {
      missingRoots.push(rootInfo);
      continue;
    }

    for (const filePath of findSkillFiles(rootInfo.root)) {
      results.push(auditSkill(filePath, rootInfo, projectRoot));
    }
  }

  const errors = results.reduce((count, item) => count + item.errors.length, 0);
  const warnings = results.reduce((count, item) => count + item.warnings.length, 0);

  return {
    ok: errors === 0 && (!options.strict || warnings === 0),
    limits: LIMITS,
    scanned: results.length,
    errors,
    warnings,
    missingRoots,
    results,
  };
}

function formatReport(report) {
  const lines = [
    '# Codex/OpenAI Skill Standards Audit',
    '',
    `Scanned: ${report.scanned} skills`,
    `Errors: ${report.errors}`,
    `Warnings: ${report.warnings}`,
    '',
  ];

  if (report.missingRoots.length > 0) {
    lines.push('Missing roots:');
    for (const item of report.missingRoots) {
      lines.push(`- ${item.scope}: ${item.root}`);
    }
    lines.push('');
  }

  const flagged = report.results
    .filter((item) => item.errors.length > 0 || item.warnings.length > 0)
    .sort((a, b) => (b.errors.length - a.errors.length) || (b.warnings.length - a.warnings.length));

  if (flagged.length === 0) {
    lines.push('No skill standards issues found.');
    return lines.join('\n');
  }

  lines.push('Flagged skills:');
  for (const item of flagged) {
    lines.push(`- ${item.path}`);
    lines.push(`  scope: ${item.scope}`);
    if (item.name) {
      lines.push(`  name: ${item.name}`);
    }
    lines.push(`  description chars: ${item.descriptionChars}`);
    lines.push(`  body lines: ${item.bodyLines}`);
    for (const error of item.errors) {
      lines.push(`  ERROR: ${error}`);
    }
    for (const warning of item.warnings) {
      lines.push(`  WARN: ${warning}`);
    }
  }

  return lines.join('\n');
}

function main() {
  try {
    const options = parseArgs();
    const report = audit(options);

    if (options.json) {
      console.log(JSON.stringify(report, null, 2));
    } else {
      console.log(formatReport(report));
    }

    process.exitCode = report.ok ? 0 : 1;
  } catch (error) {
    console.error(error.message);
    process.exitCode = 2;
  }
}

if (require.main === module) {
  main();
}

module.exports = {
  LIMITS,
  audit,
  formatReport,
  parseArgs,
};
