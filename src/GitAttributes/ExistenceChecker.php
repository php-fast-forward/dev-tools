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

use Symfony\Component\Filesystem\Filesystem;

/**
 * Checks the existence of files and directories in a given base path.
 *
 * This class determines which candidate paths from the canonical list
 * actually exist in the target repository, enabling selective export-ignore rules.
 */
final readonly class ExistenceChecker implements ExistenceCheckerInterface
{
    private string $basePath;

    /**
     * @param string $basePath The base directory to check paths against
     * @param Filesystem $filesystem
     */
    public function __construct(
        string $basePath,
        private Filesystem $filesystem = new Filesystem()
    ) {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Checks if a path exists as a file or directory.
     *
     * @param string $path The path to check (e.g., "/.github/" or "/.editorconfig")
     *
     * @return bool True if the path exists as a file or directory
     */
    public function exists(string $path): bool
    {
        $fullPath = $this->basePath . $path;

        return $this->filesystem->exists($fullPath);
    }

    /**
     * Filters a list of paths to only those that exist.
     *
     * @param list<string> $paths The paths to filter
     *
     * @return list<string> Only the paths that exist
     */
    public function filterExisting(array $paths): array
    {
        return array_values(array_filter($paths, $this->exists(...)));
    }

    /**
     * Checks if a path is a directory.
     *
     * @param string $path The path to check (e.g., "/.github/")
     *
     * @return bool True if the path exists and is a directory
     */
    public function isDirectory(string $path): bool
    {
        $fullPath = $this->basePath . $path;

        return is_dir($fullPath);
    }

    /**
     * Checks if a path is a file.
     *
     * @param string $path The path to check (e.g., "/.editorconfig")
     *
     * @return bool True if the path exists and is a file
     */
    public function isFile(string $path): bool
    {
        $fullPath = $this->basePath . $path;

        return is_file($fullPath);
    }
}
