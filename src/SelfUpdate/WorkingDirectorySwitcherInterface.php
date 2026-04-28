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

namespace FastForward\DevTools\SelfUpdate;

/**
 * Switches the process working directory before command execution starts.
 */
interface WorkingDirectorySwitcherInterface
{
    /**
     * Switches to the provided working directory when one is configured.
     *
     * @param string|null $workingDirectory the target working directory, or null when no switch is requested
     */
    public function switchTo(?string $workingDirectory): void;
}
