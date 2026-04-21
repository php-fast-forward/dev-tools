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

namespace FastForward\DevTools\Console\Command\Traits;

use Composer\Console\GithubActionError;

/**
 * Provides reusable GitHub Actions error annotations for Composer commands.
 */
trait EmitsGithubActionErrors
{
    /**
     * Emits a GitHub Actions error annotation when supported by the current environment.
     *
     * @param string $message the annotation message
     * @param string|null $file the related file when known
     * @param int|null $line the related line when known
     *
     * @return void
     */
    private function emitGithubActionError(string $message, ?string $file = null, ?int $line = null): void
    {
        (new GithubActionError($this->getIO()))->emit($message, $file, $line);
    }
}
