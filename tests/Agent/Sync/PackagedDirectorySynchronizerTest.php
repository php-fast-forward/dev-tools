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

namespace FastForward\DevTools\Tests\Agent\Sync;

use ArrayIterator;
use FastForward\DevTools\Agent\Sync\PackagedDirectorySynchronizer;
use FastForward\DevTools\Agent\Sync\SynchronizeResult;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[CoversClass(PackagedDirectorySynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class PackagedDirectorySynchronizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FilesystemInterface>
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
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
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
        $this->filesystem->exists('/package/.agents/agents')
            ->willReturn(false);
        $this->logger->error('No packaged .agents/agents found at: /package/.agents/agents')
            ->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize('/consumer/.agents/agents', '/package/.agents/agents', '.agents/agents');

        self::assertTrue($result->failed());
        self::assertSame([], $result->getCreatedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWithMissingTargetDirWillCreateItAndCreateLinks(): void
    {
        $entryPath = '/package/.agents/agents/issue-editor';

        $this->mockFinder($this->createDirectory('issue-editor', $entryPath));

        $this->filesystem->exists('/package/.agents/agents')
            ->willReturn(true);
        $this->filesystem->exists('/consumer/.agents/agents')
            ->willReturn(false);
        $this->filesystem->mkdir('/consumer/.agents/agents')
            ->shouldBeCalledOnce();
        $this->logger->info('Created .agents/agents directory.')
            ->shouldBeCalledOnce();
        $this->filesystem->exists('/consumer/.agents/agents/issue-editor')
            ->willReturn(false);
        $this->filesystem->symlink($entryPath, '/consumer/.agents/agents/issue-editor')
            ->shouldBeCalledOnce();
        $this->logger->info('Created link: issue-editor -> ' . $entryPath)->shouldBeCalledOnce();

        $result = $this->createSynchronizer()
            ->synchronize('/consumer/.agents/agents', '/package/.agents/agents', '.agents/agents');

        self::assertFalse($result->failed());
        self::assertSame(['issue-editor'], $result->getCreatedLinks());
    }

    /**
     * @param SplFileInfo $directories
     *
     * @return void
     */
    private function mockFinder(SplFileInfo ...$directories): void
    {
        $finder = $this->finder->reveal();

        $this->finderFactory->create()
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->directories()
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->in('/package/.agents/agents')
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->depth('== 0')
            ->willReturn($finder)
            ->shouldBeCalledOnce();
        $this->finder->getIterator()
            ->willReturn(new ArrayIterator($directories));
    }

    /**
     * @param string $entryName
     * @param string $sourcePath
     *
     * @return SplFileInfo
     */
    private function createDirectory(string $entryName, string $sourcePath): SplFileInfo
    {
        $directory = $this->prophesize(SplFileInfo::class);
        $directory->getFilename()
            ->willReturn($entryName);
        $directory->getRealPath()
            ->willReturn($sourcePath);

        return $directory->reveal();
    }

    /**
     * @return PackagedDirectorySynchronizer
     */
    private function createSynchronizer(): PackagedDirectorySynchronizer
    {
        return new PackagedDirectorySynchronizer(
            $this->filesystem->reveal(),
            $this->finderFactory->reveal(),
            $this->logger->reveal(),
        );
    }
}
