#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Keeps Composer subprocesses plugin-free in dependency-health CI jobs.
 *
 * @license https://opensource.org/licenses/MIT MIT License
 */

$composerBinary = getenv('FAST_FORWARD_CI_COMPOSER_BINARY');

if (! is_string($composerBinary) || '' === $composerBinary || realpath($composerBinary) === realpath(__FILE__)) {
    fwrite(STDERR, "A separate Composer binary is required for plugin-free CI checks.\n");
    exit(1);
}

$process = proc_open(
    [$composerBinary, '--no-plugins', ...array_slice($argv, 1)],
    [STDIN, STDOUT, STDERR],
    $pipes,
);

if (! is_resource($process)) {
    exit(1);
}

$exitCode = proc_close($process);
exit(-1 === $exitCode ? 1 : $exitCode);
