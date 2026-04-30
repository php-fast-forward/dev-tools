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
        if (Path::isAbsolute($path)) {
            throw new InvalidArgumentException('The DevTools package path MUST be relative to the package root.');
        }

        $projectPath = Path::canonicalize(WorkingProjectPathResolver::getProjectPath($projectPath));
        $packagePath = Path::canonicalize('' === $packagePath ? self::getPackagePath() : $packagePath);
        $packageFilePath = Path::canonicalize(Path::join($packagePath, $path));

        try {
            return Path::makeRelative($packageFilePath, $projectPath);
        } catch (InvalidArgumentException) {
            return $packageFilePath;
        }
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
        $packagePath = Path::canonicalize('' === $packagePath ? self::getPackagePath() : $packagePath);

        if (self::isInstalledAsDependency($packagePath)) {
            return Path::canonicalize(Path::join($packagePath, '..', '..', 'autoload.php'));
        }

        return Path::join($packagePath, 'vendor', 'autoload.php');
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
        $packagePath = Path::canonicalize('' === $packagePath ? self::getPackagePath() : $packagePath);

        if (self::isInstalledAsDependency($packagePath)) {
            return Path::canonicalize(Path::join($packagePath, '..', '..', 'bin', $binary));
        }

        return Path::join($packagePath, 'vendor', 'bin', $binary);
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
        $packagePath = Path::canonicalize('' === $packagePath ? self::getPackagePath() : $packagePath);
        $vendorPath = self::normalizeVendorRelativePath($path);

        if (self::isInstalledAsDependency($packagePath)) {
            return Path::canonicalize(Path::join($packagePath, '..', '..', $vendorPath));
        }

        return Path::join($packagePath, 'vendor', $vendorPath);
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
        $projectPath = '' === $projectPath ? WorkingProjectPathResolver::getProjectPath() : $projectPath;
        $projectBinaryPath = Path::join($projectPath, 'vendor', 'bin', $binary);

        if (file_exists($projectBinaryPath)) {
            return $projectBinaryPath;
        }

        return self::getRuntimeToolBinaryPath($binary, $packagePath);
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
        $projectPath = '' === $projectPath ? WorkingProjectPathResolver::getProjectPath() : $projectPath;
        $vendorPath = self::normalizeVendorRelativePath($path);
        $projectVendorPath = Path::join($projectPath, 'vendor', $vendorPath);

        if (file_exists($projectVendorPath)) {
            return $projectVendorPath;
        }

        return self::getRuntimeVendorPath($vendorPath, $packagePath);
    }

    /**
     * Detects whether the provided path belongs to an installed vendor copy of DevTools.
     *
     * @param string $packagePath an optional path within the package; defaults to the package root
     */
    public static function isInstalledAsDependency(string $packagePath = ''): bool
    {
        $packagePath = Path::canonicalize('' === $packagePath ? self::getPackagePath() : $packagePath);

        return str_contains($packagePath, self::VENDOR_PACKAGE_PATH);
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
}
