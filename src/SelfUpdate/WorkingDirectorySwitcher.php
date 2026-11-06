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

use InvalidArgumentException;

use function Safe\chdir;
use function Safe\realpath;

/**
 * Applies Composer-like working-directory switching for the standalone binary.
 */
final class WorkingDirectorySwitcher implements WorkingDirectorySwitcherInterface
{
    /**
     * Switches to the provided working directory when one is configured.
     *
     * @param string|null $workingDirectory the target working directory, or null when no switch is requested
     */
    public function switchTo(?string $workingDirectory): void
    {
        if (null === $workingDirectory || '' === $workingDirectory) {
            return;
        }

        if (! is_dir($workingDirectory)) {
            throw new InvalidArgumentException(\sprintf(
                'The working directory "%s" does not exist.',
                $workingDirectory
            ));
        }

        chdir(realpath($workingDirectory));
    }
}
