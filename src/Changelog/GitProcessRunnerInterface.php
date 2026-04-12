<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Changelog;

/**
 * Executes git-aware shell commands for changelog automation services.
 *
 * The GitProcessRunnerInterface defines a contract for executing git-related commands in the context of changelog automation.
 * Implementations of this interface MUST run specified git commands in a given working directory and return the trimmed output.
 * The run method takes a list of command arguments and a working directory as input, and it returns the output from the executed command,
 * allowing changelog automation services to interact with git repositories effectively.
 */
interface GitProcessRunnerInterface
{
    /**
     * Runs a command in the provided working directory and returns stdout.
     *
     * The method SHOULD execute the given command and return the trimmed output.
     * The implementation MUST handle any necessary process execution and error handling,
     * ensuring that the command is executed in the context of the specified working directory.
     *
     * @param list<string> $command Git command to execute (e.g., ['git', 'log', '--oneline']).
     * @param string $workingDirectory Directory in which to execute the command (e.g., repository root).
     *
     * @return string trimmed output from the executed command
     */
    public function run(array $command, string $workingDirectory): string;
}
