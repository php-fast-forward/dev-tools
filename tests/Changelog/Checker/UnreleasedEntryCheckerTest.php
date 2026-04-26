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

namespace FastForward\DevTools\Tests\Changelog\Checker;

use FastForward\DevTools\Changelog\Checker\UnreleasedEntryChecker;
use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;
use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use FastForward\DevTools\Changelog\Parser\ChangelogParserInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Git\GitClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

#[CoversClass(UnreleasedEntryChecker::class)]
#[UsesClass(ChangelogDocument::class)]
#[UsesClass(ChangelogRelease::class)]
#[UsesClass(ChangelogEntryType::class)]
final class UnreleasedEntryCheckerTest extends TestCase
{
    use ProphecyTrait;

    private const string FILE = '/repo/CHANGELOG.md';

    private const string WORKING_DIRECTORY = '/repo';

    /**
     * @var ObjectProphecy<GitClientInterface>
     */
    private ObjectProphecy $gitClient;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<ChangelogParserInterface>
     */
    private ObjectProphecy $parser;

    private UnreleasedEntryChecker $checker;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->gitClient = $this->prophesize(GitClientInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->parser = $this->prophesize(ChangelogParserInterface::class);
        $this->filesystem->getDirectory(self::FILE)
            ->willReturn(self::WORKING_DIRECTORY);
        $this->checker = new UnreleasedEntryChecker(
            $this->filesystem->reveal(),
            $this->gitClient->reveal(),
            $this->parser->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnFalseWhenTheFileCannotBeRead(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willThrow(new RuntimeException('Missing file'))
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->shouldNotBeCalled();

        self::assertFalse($this->checker->hasPendingChanges(self::FILE));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnFalseWhenTheUnreleasedSectionIsEmpty(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn(ChangelogDocument::create())
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->shouldNotBeCalled();

        self::assertFalse($this->checker->hasPendingChanges(self::FILE));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnTrueWhenUnreleasedSectionContainsEntries(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn($this->createDocument(['Added changelog automation']))
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->shouldNotBeCalled();

        self::assertTrue($this->checker->hasPendingChanges(self::FILE));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillCompareAgainstBaselineReference(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn($this->createDocument(['Added changelog automation']))
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->willReturn('baseline changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('baseline changelog')
            ->willReturn($this->createDocument(['Added changelog automation']))
            ->shouldBeCalledOnce();

        self::assertFalse($this->checker->hasPendingChanges(self::FILE, 'origin/main'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnTrueWhenBaselineDoesNotContainNewEntries(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn($this->createDocument(['Added changelog automation']))
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->willReturn('baseline changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('baseline changelog')
            ->willReturn($this->createDocument(['Initial release']))
            ->shouldBeCalledOnce();

        self::assertTrue($this->checker->hasPendingChanges(self::FILE, 'origin/main'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillIgnoreEntriesOnlyInheritedFromTheBaseBranch(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn($this->createDocument([
                'Auto-create and push minimal changelog entries for same-repository Dependabot pull requests before changelog validation reruns (#186)',
            ]))
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->willReturn('baseline changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('baseline changelog')
            ->willReturn($this->createDocument([
                'Auto-create and push minimal changelog entries for same-repository Dependabot pull requests before changelog validation reruns (#186)',
            ]))
            ->shouldBeCalledOnce();

        self::assertFalse($this->checker->hasPendingChanges(self::FILE, 'origin/main'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillStillDetectBranchSpecificEntriesAlongsideInheritedOnes(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn($this->createDocument([
                'Auto-create and push minimal changelog entries for same-repository Dependabot pull requests before changelog validation reruns (#186)',
                'GitHub Actions(deps): Bump actions/github-script from 8 to 9 (#183)',
            ]))
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->willReturn('baseline changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('baseline changelog')
            ->willReturn($this->createDocument([
                'Auto-create and push minimal changelog entries for same-repository Dependabot pull requests before changelog validation reruns (#186)',
            ]))
            ->shouldBeCalledOnce();

        self::assertTrue($this->checker->hasPendingChanges(self::FILE, 'origin/main'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasPendingChangesWillReturnTrueWhenTheBaselineCannotBeLoaded(): void
    {
        $this->filesystem->readFile(self::FILE)
            ->willReturn('current changelog')
            ->shouldBeCalledOnce();
        $this->parser->parse('current changelog')
            ->willReturn($this->createDocument(['Added changelog automation']))
            ->shouldBeCalledOnce();
        $this->gitClient->show('origin/main', self::FILE, self::WORKING_DIRECTORY)
            ->willThrow(new RuntimeException('Missing baseline'))
            ->shouldBeCalledOnce();
        $this->parser->parse('baseline changelog')
            ->shouldNotBeCalled();

        self::assertTrue($this->checker->hasPendingChanges(self::FILE, 'origin/main'));
    }

    /**
     * @param list<string> $entries
     *
     * @return ChangelogDocument
     */
    private function createDocument(array $entries): ChangelogDocument
    {
        $release = new ChangelogRelease(ChangelogDocument::UNRELEASED_VERSION);

        foreach ($entries as $entry) {
            $release = $release->withEntry(ChangelogEntryType::Added, $entry);
        }

        return new ChangelogDocument([
            $release,
            (new ChangelogRelease('1.0.0', '2026-04-08'))->withEntry(ChangelogEntryType::Added, 'Initial release'),
        ]);
    }
}
