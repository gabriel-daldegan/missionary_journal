#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');

const strict = process.argv.includes('--strict');
const projectRoot = process.cwd();
const errors = [];
const warnings = [];

function relative(filePath) {
  return path.relative(projectRoot, filePath);
}

function exists(filePath) {
  return fs.existsSync(filePath);
}

function requirePath(filePath, label) {
  if (!exists(filePath)) {
    errors.push(`${label} missing: ${relative(filePath)}`);
  }
}

function warnPath(filePath, label) {
  if (!exists(filePath)) {
    warnings.push(`${label} missing: ${relative(filePath)}`);
  }
}

function loadYaml(filePath) {
  try {
    return yaml.load(fs.readFileSync(filePath, 'utf8'));
  } catch (error) {
    errors.push(`invalid YAML ${relative(filePath)}: ${error.message}`);
    return null;
  }
}

function validateCoreConfig() {
  const configPath = path.join(projectRoot, '.aiox-core', 'core-config.yaml');
  requirePath(configPath, 'core config');

  if (!exists(configPath)) {
    return null;
  }

  const config = loadYaml(configPath);

  if (!config) {
    return null;
  }

  if (!Array.isArray(config.devLoadAlwaysFiles) || config.devLoadAlwaysFiles.length === 0) {
    errors.push('devLoadAlwaysFiles must contain at least one file');
  } else {
    for (const file of config.devLoadAlwaysFiles) {
      requirePath(path.join(projectRoot, file), 'devLoadAlwaysFiles entry');
    }
  }

  if (Array.isArray(config.devLoadAlwaysFilesFallback)) {
    for (const file of config.devLoadAlwaysFilesFallback) {
      warnPath(path.join(projectRoot, file), 'devLoadAlwaysFilesFallback entry');
    }
  }

  if (config.frameworkDocsLocation) {
    requirePath(path.join(projectRoot, config.frameworkDocsLocation), 'frameworkDocsLocation');
  }

  if (config.prd && config.prd.prdFile) {
    requirePath(path.join(projectRoot, config.prd.prdFile), 'prdFile');
  }

  if (config.architecture && config.architecture.architectureFile) {
    requirePath(path.join(projectRoot, config.architecture.architectureFile), 'architectureFile');
  }

  if (config.devStoryLocation) {
    requirePath(path.join(projectRoot, config.devStoryLocation), 'devStoryLocation');
  }

  return config;
}

function validateFrameworkDocs(config) {
  const docsLocation = (config && config.frameworkDocsLocation) || 'docs/framework';
  const docsDir = path.join(projectRoot, docsLocation);

  for (const file of ['coding-standards.md', 'tech-stack.md', 'source-tree.md']) {
    const filePath = path.join(docsDir, file);
    requirePath(filePath, 'framework doc');

    if (exists(filePath)) {
      const content = fs.readFileSync(filePath, 'utf8');
      if (!content.startsWith('# ')) {
        warnings.push(`framework doc should start with an H1: ${relative(filePath)}`);
      }
    }
  }
}

function validateHandoffs() {
  const handoffsDir = path.join(projectRoot, '.aiox', 'handoffs');

  if (!exists(handoffsDir)) {
    warnings.push('handoffs directory missing: .aiox/handoffs');
    return;
  }

  const files = fs.readdirSync(handoffsDir).filter((file) => file.endsWith('.yaml') || file.endsWith('.yml'));

  for (const file of files) {
    const filePath = path.join(handoffsDir, file);
    const parsed = loadYaml(filePath);

    if (!parsed || !parsed.handoff) {
      errors.push(`handoff YAML missing handoff root: ${relative(filePath)}`);
      continue;
    }

    for (const field of ['version', 'timestamp', 'from_agent', 'to_agent', 'consumed']) {
      if (parsed.handoff[field] === undefined || parsed.handoff[field] === null) {
        warnings.push(`handoff missing ${field}: ${relative(filePath)}`);
      }
    }
  }
}

requirePath(path.join(projectRoot, '.aiox-core'), 'AIOX core directory');
requirePath(path.join(projectRoot, '.aiox'), 'AIOX state directory');
requirePath(path.join(projectRoot, 'AGENTS.md'), 'AGENTS.md');

const config = validateCoreConfig();
validateFrameworkDocs(config);
validateHandoffs();

console.log('AIOX structure validation');
console.log(`Errors: ${errors.length}`);
console.log(`Warnings: ${warnings.length}`);

for (const error of errors) {
  console.error(`ERROR ${error}`);
}

for (const warning of warnings) {
  console.warn(`WARN ${warning}`);
}

if (errors.length > 0 || (strict && warnings.length > 0)) {
  process.exit(1);
}
