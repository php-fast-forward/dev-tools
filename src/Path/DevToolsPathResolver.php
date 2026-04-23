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

use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;

/**
 * Resolves canonical paths for the DevTools package itself.
 */
final class DevToolsPathResolver
{
    /**
     * @var string the relative path to the packaged DevTools binary
     */
    public const string BINARY = 'bin/dev-tools';

    /**
     * @var string the resources directory segment within the package
     */
    public const string RESOURCES = 'resources';

    /**
     * @var string the vendor install path fragment used when DevTools runs as a dependency
     */
    private const string VENDOR_PACKAGE_PATH = \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR
        . 'fast-forward' . \DIRECTORY_SEPARATOR . 'dev-tools';

    /**
     * Returns the DevTools package directory or a path under it.
     *
     * @param string $path the optional relative segment to append under the package directory
     */
    public static function getPackagePath(string $path = ''): string
    {
        $packageDirectory = \dirname(__DIR__, 2);

        if ('' !== $path && Path::isAbsolute($path)) {
            throw new InvalidArgumentException('The DevTools package path MUST be relative to the package root.');
        }

        return Path::join($packageDirectory, $path);
    }

    /**
     * Returns the packaged DevTools binary path.
     */
    public static function getBinaryPath(): string
    {
        return self::getPackagePath(self::BINARY);
    }

    /**
     * Returns the packaged resources directory or a path under it.
     *
     * @param string $path the optional relative segment to append under resources
     */
    public static function getResourcesPath(string $path = ''): string
    {
        return self::getPackagePath(Path::join(self::RESOURCES, $path));
    }

    /**
     * Detects whether the provided path belongs to an installed vendor copy of DevTools.
     *
     * @param string $packagePath an optional path within the package; defaults to the package root
     */
    public static function isInstalledAsDependency(string $packagePath = ''): bool
    {
        return str_contains('' === $packagePath ? self::getPackagePath() : $packagePath, self::VENDOR_PACKAGE_PATH);
    }

    /**
     * Detects whether the provided path belongs to the DevTools repository checkout itself.
     *
     * @param string $packagePath an optional path within the package; defaults to the package root
     */
    public static function isRepositoryCheckout(string $packagePath = ''): bool
    {
        return ! self::isInstalledAsDependency($packagePath);
    }
}
