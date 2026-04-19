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

namespace FastForward\DevTools\Changelog\Checker;

use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Git\GitClientInterface;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use Throwable;

use function array_diff;
use function array_values;

/**
 * Compares unreleased changelog entries against the current branch or a base ref.
 */
final readonly class UnreleasedEntryChecker implements UnreleasedEntryCheckerInterface
{
    /**
     * Constructs a new UnreleasedEntryChecker.
     *
     * @param GitClientInterface $gitClient the Git client used for baseline inspection
     * @param FilesystemInterface $filesystem
     * @param ChangelogParserInterface $parser
     */
    public function __construct(
        private FilesystemInterface $filesystem,
        private GitClientInterface $gitClient,
        private ChangelogParserInterface $parser,
    ) {}

    /**
     * Checks if there are pending unreleased entries in the changelog compared to a given reference.
     *
     * @param string $file the changelog file path to inspect
     * @param string|null $againstReference The reference to compare against (e.g., a branch or commit hash).
     *
     * @return bool true if there are pending unreleased entries, false otherwise
     */
    public function hasPendingChanges(string $file, ?string $againstReference = null): bool
    {
        try {
            $content = $this->filesystem->readFile($file);
        } catch (Throwable) {
            return false;
        }

        $currentEntries = $this->flattenEntries($this->parser->parse($content)->getUnreleased());

        if ([] === $currentEntries) {
            return false;
        }

        if (null === $againstReference) {
            return true;
        }

        try {
            $baseline = $this->gitClient->show($againstReference, $file, $this->filesystem->dirname($file));
        } catch (Throwable) {
            return true;
        }

        $baselineEntries = $this->flattenEntries($this->parser->parse($baseline)->getUnreleased());

        return [] !== array_values(array_diff($currentEntries, $baselineEntries));
    }

    /**
     * Flattens release entries for set comparison.
     *
     * @param ChangelogRelease $release
     *
     * @return list<string>
     */
    private function flattenEntries(ChangelogRelease $release): array
    {
        $entries = [];

        foreach (ChangelogEntryType::ordered() as $type) {
            $entries = [...$entries, ...$release->getEntriesFor($type)];
        }

        return array_values(array_unique($entries));
    }
}
