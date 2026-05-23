#!/usr/bin/env node

'use strict';

const [, , command, ...args] = process.argv;

if (command === 'doctor') {
  const { runDoctorChecks } = require('../core/doctor');

  const options = {
    fix: args.includes('--fix'),
    json: args.includes('--json'),
    dryRun: args.includes('--dry-run'),
    quiet: args.includes('--quiet'),
  };

  runDoctorChecks(options)
    .then((result) => {
      console.log(result.formatted);

      if (result.data.summary.fail > 0) {
        process.exitCode = 1;
      }
    })
    .catch((error) => {
      console.error(`Error: ${error.message}`);
      process.exit(1);
    });
} else {
  const { run } = require('../cli');

  run();
}
