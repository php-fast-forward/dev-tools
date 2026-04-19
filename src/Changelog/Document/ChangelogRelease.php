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
 * Represents one Keep a Changelog release section.
 */
final class ChangelogRelease
{
    /**
     * @var array<string, list<string>>
     */
    private array $entries = [];

    /**
     * @param array<string, list<string>> $entries
     * @param string $version
     * @param ?string $date
     */
    public function __construct(
        private readonly string $version,
        private readonly ?string $date = null,
        array $entries = [],
    ) {
        foreach (ChangelogEntryType::ordered() as $type) {
            $this->entries[$type->value] = array_values(array_unique($entries[$type->value] ?? []));
        }
    }

    /**
     * Returns the section version label.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Returns the release date when present.
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * Returns whether this section is the active Unreleased section.
     */
    public function isUnreleased(): bool
    {
        return ChangelogDocument::UNRELEASED_VERSION === $this->version;
    }

    /**
     * Returns all entries keyed by changelog category.
     *
     * @return array<string, list<string>>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Returns the entries for a specific changelog category.
     *
     * @param ChangelogEntryType $type
     *
     * @return list<string>
     */
    public function getEntriesFor(ChangelogEntryType $type): array
    {
        return $this->entries[$type->value];
    }

    /**
     * Returns whether the section contains at least one meaningful entry.
     */
    public function hasEntries(): bool
    {
        foreach ($this->entries as $entries) {
            if ([] !== $entries) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a copy with an additional entry appended to the given category.
     *
     * @param ChangelogEntryType $type
     * @param string $entry
     */
    public function withEntry(ChangelogEntryType $type, string $entry): self
    {
        $entries = $this->entries;
        $entry = trim($entry);

        if ('' === $entry) {
            return $this;
        }

        $entries[$type->value][] = $entry;
        $entries[$type->value] = array_values(array_unique($entries[$type->value]));

        return new self($this->version, $this->date, $entries);
    }

    /**
     * Returns a copy with all entries replaced by the supplied map.
     *
     * @param array<string, list<string>> $entries
     */
    public function withEntries(array $entries): self
    {
        return new self($this->version, $this->date, $entries);
    }

    /**
     * Returns a copy with the release date replaced.
     *
     * @param ?string $date
     */
    public function withDate(?string $date): self
    {
        return new self($this->version, $date, $this->entries);
    }
}
