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
    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem()
    ) {}

    /**
     * Checks if a path exists as a file or directory.
     *
     * @param string $basePath the repository base path used to resolve the candidate
     * @param string $path The path to check (e.g., "/.github/" or "/.editorconfig")
     *
     * @return bool True if the path exists as a file or directory
     */
    public function exists(string $basePath, string $path): bool
    {
        return $this->filesystem->exists($this->absolutePath($basePath, $path));
    }

    /**
     * Filters a list of paths to only those that exist.
     *
     * @param string $basePath the repository base path used to resolve the candidates
     * @param list<string> $paths The paths to filter
     *
     * @return list<string> Only the paths that exist
     */
    public function filterExisting(string $basePath, array $paths): array
    {
        return array_values(array_filter($paths, fn(string $path): bool => $this->exists($basePath, $path)));
    }

    /**
     * Checks if a path is a directory.
     *
     * @param string $basePath the repository base path used to resolve the candidate
     * @param string $path The path to check (e.g., "/.github/")
     *
     * @return bool True if the path exists and is a directory
     */
    public function isDirectory(string $basePath, string $path): bool
    {
        return is_dir($this->absolutePath($basePath, $path));
    }

    /**
     * Checks if a path is a file.
     *
     * @param string $basePath the repository base path used to resolve the candidate
     * @param string $path The path to check (e.g., "/.editorconfig")
     *
     * @return bool True if the path exists and is a file
     */
    public function isFile(string $basePath, string $path): bool
    {
        return is_file($this->absolutePath($basePath, $path));
    }

    /**
     * Resolves a candidate path against the repository base path.
     *
     * @param string $basePath the repository base path
     * @param string $path the candidate path in canonical form
     *
     * @return string the absolute path used for filesystem checks
     */
    private function absolutePath(string $basePath, string $path): string
    {
        return rtrim($basePath, '/\\') . $path;
    }
}
