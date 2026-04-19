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

namespace FastForward\DevTools\Changelog\Manager;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;

/**
 * Applies changelog mutations and derives release metadata.
 */
interface ChangelogManagerInterface
{
    /**
     * Adds a changelog entry to the selected release section.
     *
     * @param string $file
     * @param ChangelogEntryType $type
     * @param string $message
     * @param string $version
     * @param ?string $date
     */
    public function addEntry(
        string $file,
        ChangelogEntryType $type,
        string $message,
        string $version = ChangelogDocument::UNRELEASED_VERSION,
        ?string $date = null,
    ): void;

    /**
     * Promotes the Unreleased section into a published release.
     *
     * @param string $file
     * @param string $version
     * @param string $date
     */
    public function promote(string $file, string $version, string $date): void;

    /**
     * Returns the next semantic version inferred from unreleased entries.
     *
     * @param string $file
     * @param ?string $currentVersion
     */
    public function inferNextVersion(string $file, ?string $currentVersion = null): string;

    /**
     * Returns the rendered notes body for a specific released version.
     *
     * @param string $file
     * @param string $version
     */
    public function renderReleaseNotes(string $file, string $version): string;

    /**
     * Loads and parses the changelog file.
     *
     * @param string $file
     */
    public function load(string $file): ChangelogDocument;
}
