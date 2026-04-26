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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;

use function Safe\getcwd;

/**
 * Provides canonical repository-root paths that are not part of the managed workspace.
 */
final class WorkingProjectPathResolver
{
    /**
     * @var list<string> repository-local directories ignored by tooling
     */
    public const array TOOLING_EXCLUDED_DIRECTORIES = [
        '.dev-tools',
        'backup',
        'cache',
        'public',
        'resources',
        'tmp',
        'vendor',
        '*/vendor',
        '*/vendor/*',
        '**/vendor',
        '**/vendor/*',
    ];

    /**
     * Returns the current working project directory or a path under it.
     *
     * @param string $path the optional relative segment to append under the project directory
     */
    public static function getProjectPath(string $path = ''): string
    {
        if ('' !== $path && Path::isAbsolute($path)) {
            return $path;
        }

        return Path::join(getcwd(), $path);
    }

    /**
     * Returns the project directories that static-analysis and coding-style tooling SHOULD skip.
     *
     * @param string $baseDir the optional repository base directory used to materialize absolute paths
     *
     * @return list<string>
     */
    public static function getToolingExcludedDirectories(string $baseDir = ''): array
    {
        $excludeFromBaseDir = [
            '.dev-tools' => ManagedWorkspace::getOutputDirectory(baseDir: $baseDir),
            'backup' => Path::join($baseDir, 'backup'),
            'cache' => Path::join($baseDir, 'cache'),
            'public' => Path::join($baseDir, 'public'),
            'resources' => Path::join($baseDir, 'resources'),
            'tmp' => Path::join($baseDir, 'tmp'),
            'vendor' => Path::join($baseDir, 'vendor'),
            '*/vendor' => Path::join($baseDir, '*/vendor'),
            '*/vendor/*' => Path::join($baseDir, '*/vendor/*'),
            '**/vendor' => Path::join($baseDir, '**/vendor'),
            '**/vendor/*' => Path::join($baseDir, '**/vendor/*'),
        ];

        $directories = [];

        foreach (self::TOOLING_EXCLUDED_DIRECTORIES as $excludedDirectory) {
            $directories[] = $excludeFromBaseDir[$excludedDirectory];
        }

        return $directories;
    }

    /**
     * Returns PHP source files that tooling SHOULD inspect without traversing generated directories.
     *
     * @param string $baseDir the optional repository base directory used to materialize absolute paths
     *
     * @return list<string>
     */
    public static function getToolingSourcePaths(string $baseDir = ''): array
    {
        $workingDirectory = '' === $baseDir ? getcwd() : $baseDir;
        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in($workingDirectory)
            ->exclude(self::TOOLING_EXCLUDED_DIRECTORIES)
            ->sortByName();
        $paths = [];

        foreach ($finder as $file) {
            $realPath = $file->getRealPath();

            if (false === $realPath) {
                continue;
            }

            $relativePath = Path::makeRelative($realPath, $workingDirectory);
            $paths[] = Path::join($baseDir, $relativePath);
        }

        return $paths;
    }
}
