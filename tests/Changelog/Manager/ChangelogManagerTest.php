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

namespace FastForward\DevTools\Tests\Changelog\Manager;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Manager\ChangelogManager;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use FastForward\DevTools\Changelog\Renderer\MarkdownRendererInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Git\GitClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

#[CoversClass(ChangelogManager::class)]
#[UsesClass(ChangelogDocument::class)]
#[UsesClass(ChangelogRelease::class)]
#[UsesClass(ChangelogEntryType::class)]
final class ChangelogManagerTest extends TestCase
{
    use ProphecyTrait;

    private const string FILE = '/repo/CHANGELOG.md';

    private const string WORKING_DIRECTORY = '/repo';

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<ChangelogParserInterface>
     */
    private ObjectProphecy $parser;

    /**
     * @var ObjectProphecy<MarkdownRendererInterface>
     */
    private ObjectProphecy $renderer;

    /**
     * @var ObjectProphecy<GitClientInterface>
     */
    private ObjectProphecy $gitClient;

    private ChangelogManager $manager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->parser = $this->prophesize(ChangelogParserInterface::class);
        $this->renderer = $this->prophesize(MarkdownRendererInterface::class);
        $this->gitClient = $this->prophesize(GitClientInterface::class);
        $this->manager = new ChangelogManager(
            $this->filesystem->reveal(),
            $this->parser->reveal(),
            $this->renderer->reveal(),
            $this->gitClient->reveal(),
        );
        $this->filesystem->dirname(self::FILE)
            ->willReturn(self::WORKING_DIRECTORY);
    }

    /**
     * @return void
     */
    #[Test]
    public function addEntryWillCreateTheManagedChangelogWhenNeeded(): void
    {
        $this->filesystem->exists(self::FILE)
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $this->gitClient->getConfig('remote.origin.url', self::WORKING_DIRECTORY)
            ->willThrow(new RuntimeException('Missing remote'))
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static function (ChangelogDocument $document): bool {
                $release = $document->getUnreleased();

                return ['Ship changelog automation'] === $release->getEntriesFor(ChangelogEntryType::Added);
            }),
            null,
        )->willReturn('rendered changelog')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(self::FILE, 'rendered changelog')
            ->shouldBeCalledOnce();

        $this->manager->addEntry(self::FILE, ChangelogEntryType::Added, 'Ship changelog automation');
    }

    /**
     * @return void
     */
    #[Test]
    public function addEntryWillPreserveExistingReleaseDatesWhenNoNewDateIsProvided(): void
    {
        $document = new ChangelogDocument([
            new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION),
            (new ChangelogRelease('1.2.0', '2026-04-19'))->withEntry(
                ChangelogEntryType::Added,
                'Keep previous release note',
            ),
        ]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();
        $this->gitClient->getConfig('remote.origin.url', self::WORKING_DIRECTORY)
            ->willReturn('git@github.com:php-fast-forward/dev-tools.git')
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static function (ChangelogDocument $updated): bool {
                $release = $updated->getRelease('1.2.0');

                return $release instanceof ChangelogRelease
                    && '2026-04-19' === $release->getDate()
                    && [
                        'Keep previous release note',
                        'Add release automation',
                    ] === $release->getEntriesFor(ChangelogEntryType::Added);
            }),
            'git@github.com:php-fast-forward/dev-tools.git',
        )->willReturn('rendered changelog')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(self::FILE, 'rendered changelog')
            ->shouldBeCalledOnce();

        $this->manager->addEntry(self::FILE, ChangelogEntryType::Added, 'Add release automation', '1.2.0');
    }

    /**
     * @return void
     */
    #[Test]
    public function addEntryWillUpdateAnExistingReleaseDateWhenANewOneIsProvided(): void
    {
        $document = new ChangelogDocument([
            new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION),
            (new ChangelogRelease('1.2.0', '2026-04-01'))->withEntry(
                ChangelogEntryType::Changed,
                'Keep previous release note',
            ),
        ]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();
        $this->gitClient->getConfig('remote.origin.url', self::WORKING_DIRECTORY)
            ->willReturn('')
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static function (ChangelogDocument $updated): bool {
                $release = $updated->getRelease('1.2.0');

                return $release instanceof ChangelogRelease
                    && '2026-04-19' === $release->getDate()
                    && [
                        'Keep previous release note',
                        'Refresh release links',
                    ] === $release->getEntriesFor(ChangelogEntryType::Changed);
            }),
            null,
        )->willReturn('rendered changelog')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(self::FILE, 'rendered changelog')
            ->shouldBeCalledOnce();

        $this->manager->addEntry(
            self::FILE,
            ChangelogEntryType::Changed,
            'Refresh release links',
            '1.2.0',
            '2026-04-19',
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function promoteWillThrowWhenTheUnreleasedSectionIsEmpty(): void
    {
        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn(ChangelogDocument::create())
            ->shouldBeCalledOnce();
        $this->renderer->render(Argument::cetera())
            ->shouldNotBeCalled();
        $this->filesystem->dumpFile(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::FILE . ' does not contain unreleased entries to promote.');

        $this->manager->promote(self::FILE, '1.2.0', '2026-04-19');
    }

    /**
     * @return void
     */
    #[Test]
    public function promoteWillPersistThePublishedReleaseWhenUnreleasedEntriesExist(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))->withEntry(
                ChangelogEntryType::Added,
                'Prepare release automation',
            ),
            (new ChangelogRelease('1.1.0', '2026-04-01'))->withEntry(
                ChangelogEntryType::Fixed,
                'Keep previous release note',
            ),
        ]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();
        $this->gitClient->getConfig('remote.origin.url', self::WORKING_DIRECTORY)
            ->willReturn('')
            ->shouldBeCalledOnce();
        $this->renderer->render(
            Argument::that(static function (ChangelogDocument $updated): bool {
                $release = $updated->getRelease('1.2.0');

                return $release instanceof ChangelogRelease
                    && '2026-04-19' === $release->getDate()
                    && ['Prepare release automation'] === $release->getEntriesFor(ChangelogEntryType::Added)
                    && ! $updated->getUnreleased()
                        ->hasEntries();
            }),
            null,
        )->willReturn('rendered changelog')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(self::FILE, 'rendered changelog')
            ->shouldBeCalledOnce();

        $this->manager->promote(self::FILE, '1.2.0', '2026-04-19');
    }

    /**
     * @return void
     */
    #[Test]
    public function inferNextVersionWillThrowWhenThereAreNoUnreleasedEntries(): void
    {
        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn(ChangelogDocument::create())
            ->shouldBeCalledOnce();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::FILE . ' does not contain unreleased entries to infer a version from.');

        $this->manager->inferNextVersion(self::FILE);
    }

    /**
     * @return void
     */
    #[Test]
    public function inferNextVersionWillBumpMinorVersionsWhenAddedEntriesExist(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))->withEntry(
                ChangelogEntryType::Added,
                'Add release command',
            ),
            new ChangelogRelease('1.4.2', '2026-04-01'),
        ]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();

        self::assertSame('1.5.0', $this->manager->inferNextVersion(self::FILE));
    }

    /**
     * @return void
     */
    #[Test]
    public function inferNextVersionWillBumpMajorVersionsWhenRemovedEntriesExist(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))->withEntry(
                ChangelogEntryType::Removed,
                'Remove legacy command',
            ),
            new ChangelogRelease('1.4.2', '2026-04-01'),
        ]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();

        self::assertSame('2.0.0', $this->manager->inferNextVersion(self::FILE));
    }

    /**
     * @return void
     */
    #[Test]
    public function inferNextVersionWillBumpPatchVersionsWhenOnlyFixesExist(): void
    {
        $document = new ChangelogDocument([
            (new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION))->withEntry(
                ChangelogEntryType::Fixed,
                'Fix release note export',
            ),
            new ChangelogRelease('1.4.2', '2026-04-01'),
        ]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();

        self::assertSame('1.4.3', $this->manager->inferNextVersion(self::FILE));
    }

    /**
     * @return void
     */
    #[Test]
    public function renderReleaseNotesWillReturnTheRendererOutputForTheRequestedRelease(): void
    {
        $release = (new ChangelogRelease('1.2.0', '2026-04-19'))->withEntry(
            ChangelogEntryType::Added,
            'Ship release notes',
        );
        $document = new ChangelogDocument([new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION), $release]);

        $this->filesystem->exists(self::FILE)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->readFile(self::FILE)
            ->willReturn('existing changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('existing changelog')
            ->willReturn($document)
            ->shouldBeCalledOnce();
        $this->renderer->renderReleaseBody($release)
            ->willReturn("### Added\n\n- Ship release notes\n")
            ->shouldBeCalledOnce();

        self::assertSame(
            "### Added\n\n- Ship release notes\n",
            $this->manager->renderReleaseNotes(self::FILE, '1.2.0'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function renderReleaseNotesWillThrowWhenTheRequestedReleaseDoesNotExist(): void
    {
        $this->filesystem->exists(self::FILE)
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $this->renderer->renderReleaseBody(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::FILE . ' does not contain a [1.2.0] section.');

        $this->manager->renderReleaseNotes(self::FILE, '1.2.0');
    }
}
