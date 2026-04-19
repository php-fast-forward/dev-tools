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

namespace FastForward\DevTools\Git;

/**
 * Provides semantic Git operations for repository-aware services.
 */
interface GitClientInterface
{
    /**
     * Returns a Git config value for the selected repository.
     *
     * @param string $key
     * @param ?string $workingDirectory
     */
    public function getConfig(string $key, ?string $workingDirectory = null): string;

    /**
     * Returns the file contents shown from a specific Git reference.
     *
     * @param string $reference
     * @param string $path
     * @param ?string $workingDirectory
     */
    public function show(string $reference, string $path, ?string $workingDirectory = null): string;
}
