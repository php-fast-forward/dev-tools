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

namespace FastForward\DevTools\SelfUpdate;

/**
 * Describes the installed and latest known DevTools versions.
 */
final readonly class VersionCheckResult
{
    /**
     * @param string $currentVersion the currently installed DevTools version
     * @param string $latestVersion the latest stable DevTools version known to Composer
     */
    public function __construct(
        private string $currentVersion,
        private string $latestVersion,
    ) {}

    /**
     * Returns the currently installed DevTools version.
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    /**
     * Returns the latest stable DevTools version known to Composer.
     */
    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }

    /**
     * Detects whether the installed version is older than the latest stable version.
     */
    public function isOutdated(): bool
    {
        return version_compare($this->normalize($this->currentVersion), $this->normalize($this->latestVersion), '<');
    }

    /**
     * Normalizes common Composer tag prefixes before version comparison.
     *
     * @param string $version the version string returned by Composer metadata
     */
    private function normalize(string $version): string
    {
        return ltrim($version, 'v');
    }
}
