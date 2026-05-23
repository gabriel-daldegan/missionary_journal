#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const {
  normalizeCommands,
  parseAllAgents,
} = require('../../infrastructure/scripts/ide-sync/agent-parser');

const strict = process.argv.includes('--strict');
const projectRoot = process.cwd();
const agentsDir = path.join(projectRoot, '.aiox-core', 'development', 'agents');
const tasksDir = path.join(projectRoot, '.aiox-core', 'development', 'tasks');
const checklistsDir = path.join(projectRoot, '.aiox-core', 'development', 'checklists');

const errors = [];
const warnings = [];

function relative(filePath) {
  return path.relative(projectRoot, filePath);
}

function exists(filePath) {
  return fs.existsSync(filePath);
}

function requireField(value, field, agentId) {
  if (value === undefined || value === null || value === '') {
    errors.push(`${agentId}: missing required field ${field}`);
  }
}

function validateDependencies(agent) {
  const dependencies = agent.dependencies || {};
  const tasks = dependencies.tasks || [];
  const checklists = dependencies.checklists || [];

  for (const task of tasks) {
    const taskPath = path.join(tasksDir, task);
    if (!exists(taskPath)) {
      warnings.push(`${agent.id}: missing task dependency ${relative(taskPath)}`);
    }
  }

  for (const checklist of checklists) {
    const checklistPath = path.join(checklistsDir, checklist);
    if (!exists(checklistPath)) {
      warnings.push(`${agent.id}: missing checklist dependency ${relative(checklistPath)}`);
    }
  }
}

function validateCommands(agent) {
  const rawCommands = agent.commands || [];
  const commands = normalizeCommands(Array.isArray(rawCommands)
    ? rawCommands
    : Object.entries(rawCommands).map(([name, description]) => ({ name, description })));

  if (commands.length === 0) {
    errors.push(`${agent.id}: no commands defined`);
    return;
  }

  for (const command of commands) {
    if (!command.name || command.name === 'unknown') {
      errors.push(`${agent.id}: command missing name`);
    }

    if (!command.description || command.description === 'No description') {
      warnings.push(`${agent.id}: command ${command.name || '<unknown>'} missing description`);
    }
  }
}

function validateActivation(agent) {
  const activation = agent.yaml && agent.yaml['activation-instructions'];

  if (!Array.isArray(activation) || activation.length === 0) {
    errors.push(`${agent.id}: activation-instructions missing or invalid`);
    return;
  }

  const activationText = activation
    .map((instruction) => {
      if (typeof instruction === 'string') {
        return instruction;
      }

      if (instruction && typeof instruction === 'object') {
        return Object.entries(instruction)
          .map(([key, value]) => `${key}: ${value}`)
          .join('\n');
      }

      return String(instruction || '');
    })
    .join('\n');

  for (const step of ['STEP 1', 'STEP 2', 'STEP 3', 'STEP 4', 'STEP 5']) {
    if (!activationText.includes(step)) {
      warnings.push(`${agent.id}: activation-instructions does not mention ${step}`);
    }
  }

  if (!activationText.includes('unified-activation-pipeline.js')) {
    warnings.push(`${agent.id}: activation fallback does not reference unified-activation-pipeline.js`);
  }
}

function validateAgent(agent) {
  if (agent.error) {
    errors.push(`${agent.id}: ${agent.error}`);
    return;
  }

  const definition = agent.agent || {};
  const persona = (agent.yaml && agent.yaml.persona) || {};
  const profile = (agent.yaml && agent.yaml.persona_profile) || {};

  requireField(definition.id, 'agent.id', agent.id);
  requireField(definition.name, 'agent.name', agent.id);
  requireField(definition.title, 'agent.title', agent.id);
  requireField(definition.whenToUse, 'agent.whenToUse', agent.id);
  requireField(persona.role, 'persona.role', agent.id);
  requireField(profile.communication && profile.communication.greeting_levels, 'persona_profile.communication.greeting_levels', agent.id);

  if (definition.id && definition.id !== agent.id) {
    errors.push(`${agent.id}: agent.id ${definition.id} does not match filename`);
  }

  validateActivation(agent);
  validateCommands(agent);
  validateDependencies(agent);
}

const agents = parseAllAgents(agentsDir);
const seenIds = new Set();

for (const agent of agents) {
  if (seenIds.has(agent.id)) {
    errors.push(`duplicate agent id ${agent.id}`);
  }

  seenIds.add(agent.id);
  validateAgent(agent);
}

console.log('AIOX agent validation');
console.log(`Agents scanned: ${agents.length}`);
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
