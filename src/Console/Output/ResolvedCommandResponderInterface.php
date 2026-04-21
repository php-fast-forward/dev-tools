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

/**
 * Responds to a command after its output format has already been resolved.
 */
interface ResolvedCommandResponderInterface
{
    /**
     * Renders a success response and returns the selected exit code.
     *
     * @param string $message the human-readable summary
     * @param array<string, mixed> $context structured response context
     * @param int $exitCode the exit code to return
     *
     * @return int the selected exit code
     */
    public function success(string $message, array $context = [], int $exitCode = 0): int;

    /**
     * Renders a failure response and returns the selected exit code.
     *
     * @param string $message the human-readable summary
     * @param array<string, mixed> $context structured response context
     * @param int $exitCode the exit code to return
     *
     * @return int the selected exit code
     */
    public function failure(string $message, array $context = [], int $exitCode = 1): int;
}
