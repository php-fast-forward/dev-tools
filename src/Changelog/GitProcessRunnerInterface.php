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
 */
interface GitProcessRunnerInterface
{
    /**
     * Runs a command in the provided working directory and returns stdout.
     *
     * @param list<string> $command
     * @param string $workingDirectory
     *
     * @return string
     */
    public function run(array $command, string $workingDirectory): string;
}
