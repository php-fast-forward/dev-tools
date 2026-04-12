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

use Symfony\Component\Process\Process;

use function trim;

/**
 * Executes git processes for changelog-related repository introspection.
 */
final readonly class GitProcessRunner implements GitProcessRunnerInterface
{
    /**
     * Executes a git command in the specified working directory and returns the trimmed output.
     *
     * @param list<string> $command Git command to execute (e.g., ['git', 'log', '--oneline']).
     * @param string $workingDirectory Directory in which to execute the command (e.g., repository root).
     *
     * @return string trimmed output from the executed command
     */
    public function run(array $command, string $workingDirectory): string
    {
        $process = new Process($command, $workingDirectory);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
