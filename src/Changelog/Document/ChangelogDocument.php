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

namespace FastForward\DevTools\Changelog\Document;

use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;

/**
 * Represents the minimal Keep a Changelog document structure used by dev-tools.
 */
final readonly class ChangelogDocument
{
    public const string UNRELEASED_VERSION = 'Unreleased';

    /**
     * @param list<ChangelogRelease> $releases
     */
    public function __construct(
        private array $releases,
    ) {}

    /**
     * Creates a new document with an empty Unreleased section.
     */
    public static function create(): self
    {
        return new self([new ChangelogRelease(self::UNRELEASED_VERSION)]);
    }

    /**
     * Returns the release sections in document order.
     *
     * @return list<ChangelogRelease>
     */
    public function getReleases(): array
    {
        return $this->releases;
    }

    /**
     * Returns the Unreleased section, creating an empty one when needed.
     */
    public function getUnreleased(): ChangelogRelease
    {
        foreach ($this->releases as $release) {
            if ($release->isUnreleased()) {
                return $release;
            }
        }

        return new ChangelogRelease(self::UNRELEASED_VERSION);
    }

    /**
     * Returns the requested release section when present.
     *
     * @param string $version
     */
    public function getRelease(string $version): ?ChangelogRelease
    {
        foreach ($this->releases as $release) {
            if ($release->getVersion() === $version) {
                return $release;
            }
        }

        return null;
    }

    /**
     * Returns the newest published release section when available.
     */
    public function getLatestPublishedRelease(): ?ChangelogRelease
    {
        foreach ($this->releases as $release) {
            if (! $release->isUnreleased()) {
                return $release;
            }
        }

        return null;
    }

    /**
     * Returns a copy with the provided release inserted or replaced.
     *
     * @param ChangelogRelease $target
     */
    public function withRelease(ChangelogRelease $target): self
    {
        $releases = [];
        $replaced = false;

        foreach ($this->releases as $release) {
            if ($release->getVersion() === $target->getVersion()) {
                $releases[] = $target;
                $replaced = true;

                continue;
            }

            $releases[] = $release;
        }

        if (! $replaced) {
            if ($target->isUnreleased()) {
                array_unshift($releases, $target);
            } else {
                $inserted = false;

                foreach ($releases as $index => $release) {
                    if ($release->isUnreleased()) {
                        continue;
                    }

                    array_splice($releases, $index, 0, [$target]);
                    $inserted = true;

                    break;
                }

                if (! $inserted) {
                    $releases[] = $target;
                }
            }
        }

        return new self($this->normalizeUnreleasedPosition($releases));
    }

    /**
     * Returns a copy with the unreleased entries promoted into a published release.
     *
     * @param string $version
     * @param string $date
     */
    public function promoteUnreleased(string $version, string $date): self
    {
        $unreleased = $this->getUnreleased();

        $promoted = new ChangelogRelease($version, $date, $unreleased->getEntries());
        $currentVersion = $this->getRelease($version);

        if ($currentVersion instanceof ChangelogRelease) {
            $mergedEntries = $currentVersion->getEntries();

            foreach (ChangelogEntryType::ordered() as $type) {
                $mergedEntries[$type->value] = array_values(array_unique([
                    ...$currentVersion->getEntriesFor($type),
                    ...$unreleased->getEntriesFor($type),
                ]));
            }

            $promoted = new ChangelogRelease($version, $date, $mergedEntries);
        }

        $releases = [];

        foreach ($this->releases as $release) {
            if ($release->isUnreleased()) {
                $releases[] = new ChangelogRelease(self::UNRELEASED_VERSION);
                $releases[] = $promoted;

                continue;
            }

            if ($release->getVersion() === $version) {
                continue;
            }

            $releases[] = $release;
        }

        if ([] === $releases) {
            $releases = [new ChangelogRelease(self::UNRELEASED_VERSION), $promoted];
        }

        return new self($this->normalizeUnreleasedPosition($releases));
    }

    /**
     * Ensures the Unreleased section stays first in the document.
     *
     * @param list<ChangelogRelease> $releases
     *
     * @return list<ChangelogRelease>
     */
    private function normalizeUnreleasedPosition(array $releases): array
    {
        $unreleased = null;
        $published = [];

        foreach ($releases as $release) {
            if ($release->isUnreleased()) {
                $unreleased ??= $release;

                continue;
            }

            $published[] = $release;
        }

        return [$unreleased ?? new ChangelogRelease(self::UNRELEASED_VERSION), ...$published];
    }
}
