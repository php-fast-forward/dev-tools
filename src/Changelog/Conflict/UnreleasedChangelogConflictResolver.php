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

namespace FastForward\DevTools\Changelog\Conflict;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use FastForward\DevTools\Changelog\Renderer\MarkdownRendererInterface;

/**
 * Rebuilds the active Unreleased section from predictable changelog conflicts.
 */
final readonly class UnreleasedChangelogConflictResolver
{
    /**
     * @param ChangelogParserInterface $parser
     * @param MarkdownRendererInterface $renderer
     */
    public function __construct(
        private ChangelogParserInterface $parser,
        private MarkdownRendererInterface $renderer,
    ) {}

    /**
     * Merges source Unreleased entries into the target changelog document.
     *
     * The target SHOULD be the current base branch changelog. This preserves
     * newly published release sections when a release happened while the pull
     * request branch was waiting, then re-adds branch-only Unreleased entries to
     * the current top-level Unreleased section.
     *
     * @param string $targetContents
     * @param list<string> $sourceContents
     * @param ?string $repositoryUrl
     */
    public function resolve(string $targetContents, array $sourceContents, ?string $repositoryUrl = null): string
    {
        $targetDocument = $this->parser->parse($targetContents);
        $targetEntries = $targetDocument->getUnreleased()
            ->getEntries();
        $knownTargetEntries = $this->flattenEntries($targetDocument);

        foreach ($sourceContents as $sourceContent) {
            $sourceUnreleased = $this->parser->parse($sourceContent)
                ->getUnreleased();

            foreach (ChangelogEntryType::ordered() as $type) {
                foreach ($sourceUnreleased->getEntriesFor($type) as $entry) {
                    if (\in_array($entry, $targetEntries[$type->value], true)) {
                        continue;
                    }

                    if (\in_array($entry, $knownTargetEntries, true)) {
                        continue;
                    }

                    $targetEntries[$type->value][] = $entry;
                    $knownTargetEntries[] = $entry;
                }
            }
        }

        $document = $targetDocument->withRelease(new ChangelogRelease(
            ChangelogDocument::UNRELEASED_VERSION,
            null,
            $targetEntries,
        ));

        return $this->renderer->render($document, $repositoryUrl);
    }

    /**
     * @param ChangelogDocument $document
     *
     * @return list<string>
     */
    private function flattenEntries(ChangelogDocument $document): array
    {
        $entries = [];

        foreach ($document->getReleases() as $release) {
            foreach (ChangelogEntryType::ordered() as $type) {
                $entries = [...$entries, ...$release->getEntriesFor($type)];
            }
        }

        return array_values(array_unique($entries));
    }
}
