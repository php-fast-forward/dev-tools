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

namespace FastForward\DevTools\Workspace;

/**
 * Provides canonical repository-local paths for generated DevTools artifacts.
 */
final class ManagedWorkspace
{
    /**
     * @var string the repository-local root directory for generated artifacts
     */
    public const string ROOT = '.dev-tools';

    /**
     * @var string the repository-local root directory for generated tool caches
     */
    public const string CACHE = self::ROOT . '/cache';

    /**
     * @var string the default repository-local path for coverage artifacts
     */
    public const string COVERAGE = self::ROOT . '/coverage';

    /**
     * @var string the default repository-local path for metrics artifacts
     */
    public const string METRICS = self::ROOT . '/metrics';

    /**
     * @var string the default repository-local path for release-notes previews
     */
    public const string RELEASE_NOTES = self::ROOT . '/release-notes.md';

    /**
     * Returns the default phpDocumentor cache directory.
     */
    public static function phpDocumentorCache(): string
    {
        return self::CACHE . '/phpdoc';
    }

    /**
     * Returns the default PHPUnit cache directory.
     */
    public static function phpUnitCache(): string
    {
        return self::CACHE . '/phpunit';
    }

    /**
     * Returns the default Rector cache directory.
     */
    public static function rectorCache(): string
    {
        return self::CACHE . '/rector';
    }

    /**
     * Returns the default PHP-CS-Fixer cache directory.
     */
    public static function phpCsFixerCache(): string
    {
        return self::CACHE . '/php-cs-fixer';
    }
}
