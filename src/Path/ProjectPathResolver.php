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

namespace FastForward\DevTools\Path;

use Symfony\Component\Filesystem\Path;

/**
 * Provides canonical repository-root paths that are not part of the managed workspace.
 */
final class ProjectPathResolver
{
    /**
     * @var string the repository resources directory segment
     */
    public const string RESOURCES = 'resources';

    /**
     * @var string the vendor directory segment
     */
    public const string VENDOR = 'vendor';

    /**
     * Returns a repository-local resources path.
     *
     * @param ?string $path the optional relative segment to append under the resources directory
     * @param ?string $baseDir the optional repository root that SHOULD prefix the resources directory
     */
    public static function getResourcesDirectory(?string $path = null, ?string $baseDir = null): string
    {
        return self::joinProjectPath(self::RESOURCES, $path, $baseDir);
    }

    /**
     * Returns a repository-local vendor path.
     *
     * @param ?string $path the optional relative segment to append under the vendor directory
     * @param ?string $baseDir the optional repository root that SHOULD prefix the vendor directory
     */
    public static function getVendorDirectory(?string $path = null, ?string $baseDir = null): string
    {
        return self::joinProjectPath(self::VENDOR, $path, $baseDir);
    }

    /**
     * Returns the project directories that static-analysis and coding-style tooling SHOULD skip.
     *
     * @param ?string $baseDir the optional repository base directory used to materialize absolute paths
     *
     * @return list<string>
     */
    public static function getToolingExcludedDirectories(?string $baseDir = null): array
    {
        return [
            ManagedWorkspace::getOutputDirectory(baseDir: $baseDir),
            self::getResourcesDirectory(baseDir: $baseDir),
            self::getVendorDirectory(baseDir: $baseDir),
        ];
    }

    /**
     * Joins an optional relative path under a project-root segment.
     *
     * @param string $rootSegment the root segment to resolve under the project base directory
     * @param ?string $path the optional relative path to append under the root segment
     * @param ?string $baseDir the optional repository base directory
     */
    private static function joinProjectPath(string $rootSegment, ?string $path = null, ?string $baseDir = null): string
    {
        $root = null === $baseDir || '' === $baseDir
            ? $rootSegment
            : Path::join($baseDir, $rootSegment);

        return null === $path || '' === $path
            ? $root
            : Path::join($root, $path);
    }
}
