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

namespace FastForward\DevTools\Tests\Agent\Skills;

use ArrayIterator;
use FastForward\DevTools\Agent\Skills\SkillsSynchronizer;
use FastForward\DevTools\Agent\Skills\SynchronizeResult;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[CoversClass(SkillsSynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class SkillsSynchronizerTest extends TestCase
{
    use ProphecyTrait;

    private const string PACKAGE_SKILLS_PATH = '/package/.agents/skills';

    private const string CONSUMER_SKILLS_PATH = '/consumer/.agents/skills';

    /**
     * @var ObjectProphecy<Filesystem>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<FinderFactoryInterface>
     */
    private ObjectProphecy $finderFactory;

    /**
     * @var ObjectProphecy<Finder>
     */
    private ObjectProphecy $finder;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private ObjectProphecy $logger;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->finderFactory = $this->prophesize(FinderFactoryInterface::class);
        $this->finder = $this->prophesize(Finder::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWithMissingPackagePathWillReturnFailedResult(): void
    {
        $this->filesystem->exists(self::PACKAGE_SKILLS_PATH)->willReturn(false);
        $this->logger->error('No packaged skills found at: ' . self::PACKAGE_SKILLS_PATH)->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize(self::CONSUMER_SKILLS_PATH, self::PACKAGE_SKILLS_PATH);

        self::assertTrue($result->failed());
        self::assertSame([], $result->getCreatedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWithMissingSkillsDirWillCreateItAndCreateLinks(): void
    {
        $skillOnePath = self::PACKAGE_SKILLS_PATH . '/skill-one';
        $skillTwoPath = self::PACKAGE_SKILLS_PATH . '/skill-two';

        $this->mockFinder(
            $this->createSkillDirectory('skill-one', $skillOnePath),
            $this->createSkillDirectory('skill-two', $skillTwoPath),
        );

        $this->filesystem->exists(self::PACKAGE_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists(self::CONSUMER_SKILLS_PATH)->willReturn(false);
        $this->filesystem->mkdir(self::CONSUMER_SKILLS_PATH)->shouldBeCalledOnce();
        $this->logger->info('Created .agents/skills directory.')
            ->shouldBeCalledOnce();

        $this->filesystem->exists(self::CONSUMER_SKILLS_PATH . '/skill-one')->willReturn(false);
        $this->filesystem->exists(self::CONSUMER_SKILLS_PATH . '/skill-two')->willReturn(false);
        $this->filesystem->symlink($skillOnePath, self::CONSUMER_SKILLS_PATH . '/skill-one')->shouldBeCalledOnce();
        $this->filesystem->symlink($skillTwoPath, self::CONSUMER_SKILLS_PATH . '/skill-two')->shouldBeCalledOnce();
        $this->logger->info('Created link: skill-one -> ' . $skillOnePath)->shouldBeCalledOnce();
        $this->logger->info('Created link: skill-two -> ' . $skillTwoPath)->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize(self::CONSUMER_SKILLS_PATH, self::PACKAGE_SKILLS_PATH);

        self::assertFalse($result->failed());
        self::assertSame(['skill-one', 'skill-two'], $result->getCreatedLinks());
        self::assertSame([], $result->getPreservedLinks());
        self::assertSame([], $result->getRemovedBrokenLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillPreserveExistingValidSymlink(): void
    {
        $skillOnePath = self::PACKAGE_SKILLS_PATH . '/skill-one';
        $targetLink = self::CONSUMER_SKILLS_PATH . '/skill-one';

        $this->mockFinder($this->createSkillDirectory('skill-one', $skillOnePath));

        $this->filesystem->exists(self::PACKAGE_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists(self::CONSUMER_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists($targetLink)
            ->willReturn(true);
        $this->filesystem->readlink($targetLink)
            ->willReturn($skillOnePath);
        $this->filesystem->readlink($targetLink, true)
            ->willReturn($skillOnePath);
        $this->filesystem->exists($skillOnePath)
            ->willReturn(true);
        $this->logger->notice('Preserved existing link: skill-one')
            ->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize(self::CONSUMER_SKILLS_PATH, self::PACKAGE_SKILLS_PATH);

        self::assertFalse($result->failed());
        self::assertSame([], $result->getCreatedLinks());
        self::assertSame(['skill-one'], $result->getPreservedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillHandleExistingBrokenSymlink(): void
    {
        $skillOnePath = self::PACKAGE_SKILLS_PATH . '/skill-one';
        $targetLink = self::CONSUMER_SKILLS_PATH . '/skill-one';
        $brokenLinkPath = '/obsolete/.agents/skills/skill-one';

        $this->mockFinder($this->createSkillDirectory('skill-one', $skillOnePath));

        $this->filesystem->exists(self::PACKAGE_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists(self::CONSUMER_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists($targetLink)
            ->willReturn(true);
        $this->filesystem->readlink($targetLink)
            ->willReturn($brokenLinkPath);
        $this->filesystem->readlink($targetLink, true)
            ->willReturn($brokenLinkPath);
        $this->filesystem->exists($brokenLinkPath)
            ->willReturn(false);
        $this->filesystem->remove($targetLink)
            ->shouldBeCalledOnce();
        $this->filesystem->symlink($skillOnePath, $targetLink)
            ->shouldBeCalledOnce();
        $this->logger->notice('Existing link is broken: skill-one (removing and recreating)')
            ->shouldBeCalledOnce();
        $this->logger->info('Created link: skill-one -> ' . $skillOnePath)->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize(self::CONSUMER_SKILLS_PATH, self::PACKAGE_SKILLS_PATH);

        self::assertFalse($result->failed());
        self::assertSame(['skill-one'], $result->getCreatedLinks());
        self::assertSame([], $result->getPreservedLinks());
        self::assertSame(['skill-one'], $result->getRemovedBrokenLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillPreserveNonSymlinkDirectoryForSameSkill(): void
    {
        $skillOnePath = self::PACKAGE_SKILLS_PATH . '/skill-one';
        $targetLink = self::CONSUMER_SKILLS_PATH . '/skill-one';

        $this->mockFinder($this->createSkillDirectory('skill-one', $skillOnePath));

        $this->filesystem->exists(self::PACKAGE_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists(self::CONSUMER_SKILLS_PATH)->willReturn(true);
        $this->filesystem->exists($targetLink)
            ->willReturn(true);
        $this->filesystem->readlink($targetLink)
            ->willReturn(null);
        $this->logger->notice(
            'Existing non-symlink found: skill-one (keeping as is, skipping link creation)'
        )->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize(self::CONSUMER_SKILLS_PATH, self::PACKAGE_SKILLS_PATH);

        self::assertFalse($result->failed());
        self::assertSame([], $result->getCreatedLinks());
        self::assertSame(['skill-one'], $result->getPreservedLinks());
        self::assertSame([], $result->getRemovedBrokenLinks());
    }

    /**
     * @param SplFileInfo ...$skills
     *
     * @return void
     */
    private function mockFinder(SplFileInfo ...$skills): void
    {
        $finder = $this->finder->reveal();

        $this->finderFactory->create()
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->directories()
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->in(self::PACKAGE_SKILLS_PATH)->willReturn($finder)->shouldBeCalledOnce();
        $this->finder->depth('== 0')
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->getIterator()
            ->willReturn(new ArrayIterator($skills));
    }

    /**
     * @param string $skillName
     * @param string $sourcePath
     *
     * @return SplFileInfo
     */
    private function createSkillDirectory(string $skillName, string $sourcePath): SplFileInfo
    {
        $skillDirectory = $this->prophesize(SplFileInfo::class);
        $skillDirectory->getFilename()
            ->willReturn($skillName);
        $skillDirectory->getRealPath()
            ->willReturn($sourcePath);

        return $skillDirectory->reveal();
    }

    /**
     * @return SkillsSynchronizer
     */
    private function createSynchronizer(): SkillsSynchronizer
    {
        return new SkillsSynchronizer(
            $this->filesystem->reveal(),
            $this->finderFactory->reveal(),
            $this->logger->reveal(),
        );
    }
}
