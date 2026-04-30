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
    private const string VENDOR_PACKAGE_PATH = '/vendor/fast-forward/dev-tools';

    /**
     * Returns the DevTools package directory or a path under it.
     *
     * @param string $path the optional relative segment to append under the package directory
     */
    public static function getPackagePath(string $path = ''): string
    {
        return self::resolvePackageRelativePath($path);
    }

    /**
     * Returns the packaged DevTools binary path.
     */
    public static function getBinaryPath(): string
    {
        return self::getPackagePath(self::BINARY);
    }

    /**
     * Returns the packaged DevTools binary command with a subcommand.
     *
     * @param string $command the DevTools subcommand to append
     */
    public static function getBinaryCommand(string $command): string
    {
        $binaryPath = self::getPackagePath(self::BINARY);

        return \sprintf('%s %s', $binaryPath, $command);
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
     * Returns a packaged path rendered relative to the active project root when possible.
     *
     * When the project root and package root do not share a filesystem root,
     * the packaged absolute path MUST be returned unchanged so globally
     * installed DevTools can still point hooks at the packaged fallback file.
     *
     * @param string $path the relative path under the package root
     * @param string $projectPath an optional project root path; defaults to the working project root
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    public static function getPackagePathRelativeToProject(
        string $path,
        string $projectPath = '',
        string $packagePath = '',
    ): string {
        return self::relativizePathFromProject(
            self::resolvePackageRelativePath($path, $packagePath),
            self::resolveProjectPath($projectPath),
        );
    }

    /**
     * Returns the active Composer autoload file for the current DevTools installation mode.
     *
     * When DevTools runs as a dependency, the runtime autoloader lives at the
     * Composer vendor root. Repository checkouts instead use the package-local
     * `vendor/autoload.php`.
     *
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    public static function getRuntimeAutoloadPath(string $packagePath = ''): string
    {
        return Path::join(self::getRuntimeVendorRoot($packagePath), 'autoload.php');
    }

    /**
     * Returns the active Composer runtime binary path for the current DevTools installation mode.
     *
     * Repository checkouts use the package-local `vendor/bin/<binary>`, while
     * dependency installs resolve binaries from the active Composer vendor root.
     *
     * @param string $binary the binary name relative to `vendor/bin`
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    public static function getRuntimeToolBinaryPath(string $binary, string $packagePath = ''): string
    {
        return self::getRuntimeVendorPath(Path::join('bin', $binary), $packagePath);
    }

    /**
     * Returns the active Composer vendor path for the current DevTools installation mode.
     *
     * Relative vendor paths MAY be passed either with or without a leading
     * `vendor/` prefix.
     *
     * @param string $path the vendor-relative path to resolve
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    public static function getRuntimeVendorPath(string $path, string $packagePath = ''): string
    {
        return Path::join(self::getRuntimeVendorRoot($packagePath), self::normalizeVendorRelativePath($path));
    }

    /**
     * Returns the preferred tooling binary path for the active project and DevTools runtime.
     *
     * Consumer projects SHOULD take precedence when they provide a local
     * `vendor/bin/<binary>` entry. If the binary is absent locally, the method
     * MUST fall back to the active DevTools runtime binary path.
     *
     * @param string $binary the binary name relative to `vendor/bin`
     * @param string $projectPath an optional project root path; defaults to the working project root
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    public static function getPreferredToolBinaryPath(
        string $binary,
        string $projectPath = '',
        string $packagePath = '',
    ): string {
        return self::preferExistingPath(
            self::getProjectVendorPath(Path::join('bin', $binary), $projectPath),
            self::getRuntimeToolBinaryPath($binary, $packagePath),
        );
    }

    /**
     * Returns the preferred Composer vendor path for the active project and DevTools runtime.
     *
     * Consumer projects SHOULD take precedence when they provide the requested
     * vendor path locally. If the path is absent locally, the method MUST fall
     * back to the active DevTools runtime vendor path.
     *
     * @param string $path the vendor-relative path to resolve
     * @param string $projectPath an optional project root path; defaults to the working project root
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    public static function getPreferredVendorPath(
        string $path,
        string $projectPath = '',
        string $packagePath = '',
    ): string {
        return self::preferExistingPath(
            self::getProjectVendorPath($path, $projectPath),
            self::getRuntimeVendorPath($path, $packagePath),
        );
    }

    /**
     * Detects whether the provided path belongs to an installed vendor copy of DevTools.
     *
     * @param string $packagePath an optional path within the package; defaults to the package root
     */
    public static function isInstalledAsDependency(string $packagePath = ''): bool
    {
        return str_contains(self::resolvePackageRoot($packagePath), self::VENDOR_PACKAGE_PATH);
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

    /**
     * Normalizes a path relative to the Composer vendor root.
     *
     * @param string $path the vendor-relative path to normalize
     */
    private static function normalizeVendorRelativePath(string $path): string
    {
        $path = Path::canonicalize($path);

        if (str_starts_with($path, 'vendor/')) {
            return substr($path, 7);
        }

        return $path;
    }

    /**
     * Ensures packaged paths stay relative to the DevTools package root.
     *
     * @param string $path the package-relative path to validate
     */
    private static function assertRelativePackagePath(string $path): void
    {
        if ('' !== $path && Path::isAbsolute($path)) {
            throw new InvalidArgumentException('The DevTools package path MUST be relative to the package root.');
        }
    }

    /**
     * Returns a canonical path under the DevTools package root.
     *
     * @param string $path the package-relative path to resolve
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    private static function resolvePackageRelativePath(string $path = '', string $packagePath = ''): string
    {
        self::assertRelativePackagePath($path);

        return Path::canonicalize(Path::join(self::resolvePackageRoot($packagePath), $path));
    }

    /**
     * Returns the canonical DevTools package root.
     *
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    private static function resolvePackageRoot(string $packagePath = ''): string
    {
        return Path::canonicalize('' === $packagePath ? \dirname(__DIR__, 2) : $packagePath);
    }

    /**
     * Returns the canonical working project root.
     *
     * @param string $projectPath an optional project root path; defaults to the working project root
     */
    private static function resolveProjectPath(string $projectPath = ''): string
    {
        return Path::canonicalize(WorkingProjectPathResolver::getProjectPath($projectPath));
    }

    /**
     * Returns the active Composer vendor root for the current DevTools installation mode.
     *
     * @param string $packagePath an optional package root path; defaults to the current package root
     */
    private static function getRuntimeVendorRoot(string $packagePath = ''): string
    {
        $packagePath = self::resolvePackageRoot($packagePath);

        if (self::isInstalledAsDependency($packagePath)) {
            return Path::canonicalize(Path::join($packagePath, '..', '..'));
        }

        return Path::join($packagePath, 'vendor');
    }

    /**
     * Returns a vendor path under the active project root.
     *
     * @param string $path the vendor-relative path to resolve
     * @param string $projectPath an optional project root path; defaults to the working project root
     */
    private static function getProjectVendorPath(string $path, string $projectPath = ''): string
    {
        return Path::join(self::resolveProjectPath($projectPath), 'vendor', self::normalizeVendorRelativePath($path));
    }

    /**
     * Returns the preferred path when a project-local candidate exists.
     *
     * @param string $preferredPath the project-local candidate path
     * @param string $fallbackPath the runtime fallback path
     */
    private static function preferExistingPath(string $preferredPath, string $fallbackPath): string
    {
        if (file_exists($preferredPath)) {
            return $preferredPath;
        }

        return $fallbackPath;
    }

    /**
     * Returns a path relative to the project root when possible.
     *
     * When paths do not share the same filesystem root, the original absolute
     * path MUST be returned unchanged so callers still receive a usable path.
     *
     * @param string $path the absolute path to relativize
     * @param string $projectPath the absolute project root used as base path
     */
    private static function relativizePathFromProject(string $path, string $projectPath): string
    {
        try {
            return Path::makeRelative($path, $projectPath);
        } catch (InvalidArgumentException) {
            return $path;
        }
    }
}
