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

namespace FastForward\DevTools\GitAttributes;

/**
 * Checks the existence of files and directories in a given base path.
 *
 * This interface defines the contract for determining which candidate
 * paths actually exist in the target repository.
 */
interface ExistenceCheckerInterface
{
    /**
     * Checks if a path exists as a file or directory.
     *
     * @param string $path The path to check (e.g., "/.github/" or "/.editorconfig")
     *
     * @return bool True if the path exists as a file or directory
     */
    public function exists(string $path): bool;

    /**
     * Filters a list of paths to only those that exist.
     *
     * @param list<string> $paths The paths to filter
     *
     * @return list<string> Only the paths that exist
     */
    public function filterExisting(array $paths): array;

    /**
     * Checks if a path is a directory.
     *
     * @param string $path The path to check (e.g., "/.github/")
     *
     * @return bool True if the path exists and is a directory
     */
    public function isDirectory(string $path): bool;

    /**
     * Checks if a path is a file.
     *
     * @param string $path The path to check (e.g., "/.editorconfig")
     *
     * @return bool True if the path exists and is a file
     */
    public function isFile(string $path): bool;
}
