<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Detects console output capabilities without requiring PTY-backed processes.
 */
interface OutputCapabilityDetectorInterface
{
    /**
     * Determines whether the output supports ANSI-capable human interaction.
     *
     * @param OutputInterface $output the output to inspect
     *
     * @return bool true when the output is decorated or connected to a TTY
     */
    public function supportsAnsi(OutputInterface $output): bool;
}
