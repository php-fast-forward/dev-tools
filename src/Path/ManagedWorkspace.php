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
 * Provides canonical repository-local paths for generated DevTools artifacts.
 */
final class ManagedWorkspace
{
    /**
     * @var string the output segment used for coverage artifacts
     */
    public const string COVERAGE = 'coverage';

    /**
     * @var string the output segment used for metrics artifacts
     */
    public const string METRICS = 'metrics';

    /**
     * @var string the cache segment used for phpDocumentor
     */
    public const string PHPDOC = 'phpdoc';

    /**
     * @var string the cache segment used for PHPUnit
     */
    public const string PHPUNIT = 'phpunit';

    /**
     * @var string the cache segment used for Rector
     */
    public const string RECTOR = 'rector';

    /**
     * @var string the cache segment used for PHP-CS-Fixer
     */
    public const string PHP_CS_FIXER = 'php-cs-fixer';

    /**
     * @var string the repository-local root directory for generated artifacts
     */
    private const string WORKSPACE_ROOT = '.dev-tools';

    /**
     * @var string the repository-local root directory for generated tool caches
     */
    private const string CACHE_ROOT = 'cache';

    /**
     * Returns a repository-local managed output directory.
     *
     * The optional $path MUST be a relative segment within the managed
     * workspace, while $baseDir MAY provide the repository root used to
     * materialize the same `.dev-tools` structure under a different base path.
     *
     * @param ?string $path the optional relative segment to append under the managed output root
     * @param ?string $baseDir the optional repository root used to resolve the managed workspace path
     */
    public static function getOutputDirectory(?string $path = null, ?string $baseDir = null): string
    {
        $baseDir = null === $baseDir || '' === $baseDir
            ? self::WORKSPACE_ROOT
            : Path::join($baseDir, self::WORKSPACE_ROOT);

        return null === $path || '' === $path
            ? $baseDir
            : Path::join($baseDir, $path);
    }

    /**
     * Returns a repository-local managed cache directory.
     *
     * The optional $path MUST be a relative cache segment, while $baseDir MAY
     * resolve the managed workspace root before the `cache` directory is
     * appended.
     *
     * @param ?string $path the optional relative cache segment to append under the managed cache root
     * @param ?string $baseDir the optional repository root used to resolve the managed cache path
     */
    public static function getCacheDirectory(?string $path = null, ?string $baseDir = null): string
    {
        $baseDir = self::getOutputDirectory(self::CACHE_ROOT, $baseDir);

        return null === $path || '' === $path
            ? $baseDir
            : Path::join($baseDir, $path);
    }
}
