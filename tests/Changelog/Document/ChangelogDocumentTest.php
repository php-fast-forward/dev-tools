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

namespace FastForward\DevTools\Tests\Changelog\Document;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangelogDocument::class)]
#[CoversClass(ChangelogRelease::class)]
#[UsesClass(ChangelogEntryType::class)]
final class ChangelogDocumentTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function withReleaseWillKeepTheUnreleasedSectionAtTheTop(): void
    {
        $document = ChangelogDocument::create()
            ->withRelease((new ChangelogRelease('1.1.0', '2026-04-10'))->withEntry(
                ChangelogEntryType::Added,
                'Ship changelog automation',
            ))
            ->withRelease((new ChangelogRelease('1.0.0', '2026-04-01'))->withEntry(
                ChangelogEntryType::Fixed,
                'Stabilize command output',
            ));

        self::assertSame(
            [ChangelogDocument::UNRELEASED_VERSION, '1.0.0', '1.1.0'],
            array_map(
                static fn(ChangelogRelease $release): string => $release->getVersion(),
                $document->getReleases(),
            ),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function promoteUnreleasedWillMergeWithAnExistingPublishedVersion(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
                ->withEntry(ChangelogEntryType::Added, 'Add release command')
                ->withEntry(ChangelogEntryType::Fixed, 'Preserve release sections'),
            (new ChangelogRelease('1.2.0', '2026-04-01'))
                ->withEntry(ChangelogEntryType::Added, 'Existing release note'),
        ]);

        $promoted = $document->promoteUnreleased('1.2.0', '2026-04-19');
        $release = $promoted->getRelease('1.2.0');

        self::assertInstanceOf(ChangelogRelease::class, $release);
        self::assertSame('2026-04-19', $release->getDate());
        self::assertSame(
            ['Existing release note', 'Add release command'],
            $release->getEntriesFor(ChangelogEntryType::Added),
        );
        self::assertSame(['Preserve release sections'], $release->getEntriesFor(ChangelogEntryType::Fixed));
        self::assertFalse($promoted->getUnreleased()->hasEntries());
    }

    /**
     * @return void
     */
    #[Test]
    public function documentAccessorsWillResolveExpectedReleaseVariants(): void
    {
        $document = new ChangelogDocument([
            new ChangelogRelease('1.2.0', '2026-04-19'),
        ]);

        self::assertSame(ChangelogDocument::UNRELEASED_VERSION, $document->getUnreleased()->getVersion());
        self::assertNull($document->getRelease('9.9.9'));
        self::assertSame('1.2.0', $document->getLatestPublishedRelease()?->getVersion());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLatestPublishedReleaseWillReturnNullWhenOnlyUnreleasedExists(): void
    {
        self::assertNull(ChangelogDocument::create()->getLatestPublishedRelease());
    }

    /**
     * @return void
     */
    #[Test]
    public function withReleaseWillReplaceExistingVersionAndInsertUnreleasedAtTheTop(): void
    {
        $existing = new ChangelogRelease('1.2.0', '2026-04-01');
        $replacement = (new ChangelogRelease('1.2.0', '2026-04-19'))
            ->withEntry(ChangelogEntryType::Added, 'Updated note');
        $document = (new ChangelogDocument([$existing]))
            ->withRelease(new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))
            ->withRelease($replacement);

        self::assertSame(
            [ChangelogDocument::UNRELEASED_VERSION, '1.2.0'],
            array_map(
                static fn(ChangelogRelease $release): string => $release->getVersion(),
                $document->getReleases(),
            ),
        );
        self::assertSame(['Updated note'], $document->getRelease('1.2.0')?->getEntriesFor(ChangelogEntryType::Added));
    }

    /**
     * @return void
     */
    #[Test]
    public function promoteUnreleasedWillCreatePublishedReleaseWhenDocumentIsEmpty(): void
    {
        $document = new ChangelogDocument([]);

        $promoted = $document->promoteUnreleased('1.0.0', '2026-04-20');

        self::assertSame(
            [ChangelogDocument::UNRELEASED_VERSION, '1.0.0'],
            array_map(
                static fn(ChangelogRelease $release): string => $release->getVersion(),
                $promoted->getReleases(),
            ),
        );
        self::assertSame('2026-04-20', $promoted->getRelease('1.0.0')?->getDate());
    }

    /**
     * @return void
     */
    #[Test]
    public function releaseHelpersWillNormalizeEntriesAndSupportImmutableMutators(): void
    {
        $release = new ChangelogRelease('1.3.0', null, [
            ChangelogEntryType::Added->value => ['Ship feature', 'Ship feature'],
        ]);

        self::assertSame('1.3.0', $release->getVersion());
        self::assertNull($release->getDate());
        self::assertFalse($release->isUnreleased());
        self::assertTrue($release->hasEntries());
        self::assertSame(['Ship feature'], $release->getEntriesFor(ChangelogEntryType::Added));

        $sameRelease = $release->withEntry(ChangelogEntryType::Fixed, '   ');
        self::assertSame($release, $sameRelease);

        $updated = $release
            ->withEntry(ChangelogEntryType::Fixed, 'Repair behavior')
            ->withEntries([ChangelogEntryType::Security->value => ['Security hardening']])
            ->withDate('2026-04-20');

        self::assertSame('2026-04-20', $updated->getDate());
        self::assertSame(['Security hardening'], $updated->getEntriesFor(ChangelogEntryType::Security));
        self::assertSame([], $updated->getEntriesFor(ChangelogEntryType::Added));
    }
}
