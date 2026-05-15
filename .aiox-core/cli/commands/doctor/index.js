/**
 * Doctor Command Module
 *
 * CLI command for running AIOX environment diagnostics.
 *
 * @module cli/commands/doctor
 */

'use strict';

const { Command } = require('commander');
const { runDoctorChecks } = require('../../../core/doctor');

function createDoctorCommand() {
  const doctor = new Command('doctor');

  doctor
    .description('Run AIOX environment diagnostics')
    .option('--fix', 'Auto-correct fixable issues')
    .option('--json', 'Output results as JSON')
    .option('--dry-run', 'Show what --fix would do without applying changes')
    .option('--quiet', 'Show minimal text output')
    .action(async (options) => {
      const result = await runDoctorChecks(options);

      console.log(result.formatted);

      if (result.data.summary.fail > 0) {
        process.exitCode = 1;
      }
    });

  return doctor;
}

module.exports = {
  createDoctorCommand,
};
