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

use Throwable;
use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Git\GitClientInterface;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use FastForward\DevTools\Changelog\Renderer\MarkdownRendererInterface;
use RuntimeException;

/**
 * Applies changelog mutations and derived release metadata.
 */
final readonly class ChangelogManager implements ChangelogManagerInterface
{
    /**
     * @param FilesystemInterface $filesystem
     * @param ChangelogParserInterface $parser
     * @param MarkdownRendererInterface $renderer
     * @param GitClientInterface $gitClient
     */
    public function __construct(
        private FilesystemInterface $filesystem,
        private ChangelogParserInterface $parser,
        private MarkdownRendererInterface $renderer,
        private GitClientInterface $gitClient,
    ) {}

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
    ): void {
        $document = $this->load($file);
        $release = $document->getRelease($version) ?? new ChangelogRelease($version, $date);

        if (null !== $date && $release->getDate() !== $date) {
            $release = $release->withDate($date);
        }

        $document = $document->withRelease($release->withEntry($type, $message));

        $this->persist($file, $document);
    }

    /**
     * Promotes the Unreleased section into a published release.
     *
     * @param string $file
     * @param string $version
     * @param string $date
     */
    public function promote(string $file, string $version, string $date): void
    {
        $document = $this->load($file);

        if (! $document->getUnreleased()->hasEntries()) {
            throw new RuntimeException(\sprintf('%s does not contain unreleased entries to promote.', $file));
        }

        $document = $document->promoteUnreleased($version, $date);

        $this->persist($file, $document);
    }

    /**
     * Returns the next semantic version inferred from unreleased entries.
     *
     * @param string $file
     * @param ?string $currentVersion
     */
    public function inferNextVersion(string $file, ?string $currentVersion = null): string
    {
        $document = $this->load($file);
        $unreleased = $document->getUnreleased();

        if (! $unreleased->hasEntries()) {
            throw new RuntimeException(\sprintf(
                '%s does not contain unreleased entries to infer a version from.',
                $file
            ));
        }

        $currentVersion ??= $document->getLatestPublishedRelease()?->getVersion() ?? '0.0.0';
        [$major, $minor, $patch] = array_map(intval(...), explode('.', $currentVersion));

        if ([] !== $unreleased->getEntriesFor(ChangelogEntryType::Removed)
            || [] !== $unreleased->getEntriesFor(ChangelogEntryType::Deprecated)
        ) {
            return \sprintf('%d.0.0', $major + 1);
        }

        if ([] !== $unreleased->getEntriesFor(ChangelogEntryType::Added)
            || [] !== $unreleased->getEntriesFor(ChangelogEntryType::Changed)
        ) {
            return \sprintf('%d.%d.0', $major, $minor + 1);
        }

        return \sprintf('%d.%d.%d', $major, $minor, $patch + 1);
    }

    /**
     * Returns the rendered notes body for a specific released version.
     *
     * @param string $file
     * @param string $version
     */
    public function renderReleaseNotes(string $file, string $version): string
    {
        $release = $this->load($file)
            ->getRelease($version);

        if (! $release instanceof ChangelogRelease) {
            throw new RuntimeException(\sprintf('%s does not contain a [%s] section.', $file, $version));
        }

        return $this->renderer->renderReleaseBody($release);
    }

    /**
     * Loads and parses the changelog file.
     *
     * @param string $file
     */
    public function load(string $file): ChangelogDocument
    {
        if (! $this->filesystem->exists($file)) {
            return ChangelogDocument::create();
        }

        return $this->parser->parse($this->filesystem->readFile($file));
    }

    /**
     * Resolves the canonical repository URL for compare links.
     *
     * @param string $workingDirectory
     */
    private function resolveRepositoryUrl(string $workingDirectory): ?string
    {
        try {
            $repositoryUrl = $this->gitClient->getConfig('remote.origin.url', $workingDirectory);
        } catch (Throwable) {
            return null;
        }

        return '' === $repositoryUrl ? null : $repositoryUrl;
    }

    /**
     * Persists the rendered changelog document to disk.
     *
     * @param string $file
     * @param ChangelogDocument $document
     */
    private function persist(string $file, ChangelogDocument $document): void
    {
        $this->filesystem->dumpFile(
            $file,
            $this->renderer->render($document, $this->resolveRepositoryUrl($this->filesystem->dirname($file))),
        );
    }
}
