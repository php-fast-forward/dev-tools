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

namespace FastForward\DevTools\Tests\GitAttributes;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\GitAttributes\ExistenceChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ExistenceChecker::class)]
final class ExistenceCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @property ObjectProphecy<FilesystemInterface> $filesystem
     */
    private readonly ObjectProphecy $filesystem;

    private readonly ExistenceChecker $checker;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->checker = new ExistenceChecker($this->filesystem->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function existsWillReturnTrueWhenPathExists(): void
    {
        $this->filesystem->exists('/project/.github/')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        self::assertTrue($this->checker->exists('/project', '/.github/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function existsWillReturnFalseWhenPathDoesNotExist(): void
    {
        $this->filesystem->exists('/project/.nonexistent/')
            ->willReturn(false)
            ->shouldBeCalledOnce();

        self::assertFalse($this->checker->exists('/project', '/.nonexistent/'));
    }

    /**
     * @return void
     */
    #[Test]
    public function filterExistingWillKeepOnlyExistingPaths(): void
    {
        $this->filesystem->exists('/project/.github/')
            ->willReturn(true);
        $this->filesystem->exists('/project/README.md')
            ->willReturn(false);
        $this->filesystem->exists('/project/docs/')
            ->willReturn(true);

        $result = $this->checker->filterExisting('/project', ['/.github/', '/README.md', '/docs/']);

        self::assertSame(['/.github/', '/docs/'], $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function filterExistingWillReturnEmptyArrayWhenNoneExist(): void
    {
        $this->filesystem->exists('/project/fake1')
            ->willReturn(false);
        $this->filesystem->exists('/project/fake2')
            ->willReturn(false);

        $result = $this->checker->filterExisting('/project', ['/fake1', '/fake2']);

        self::assertSame([], $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function isDirectoryWillReturnTrueForDirectory(): void
    {
        self::assertTrue($this->checker->isDirectory(__DIR__, ''));
    }

    /**
     * @return void
     */
    #[Test]
    public function isDirectoryWillReturnFalseForFile(): void
    {
        self::assertFalse($this->checker->isDirectory(__DIR__, '/ExistenceCheckerTest.php'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isFileWillReturnTrueForFile(): void
    {
        self::assertTrue($this->checker->isFile(__DIR__, '/ExistenceCheckerTest.php'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isFileWillReturnFalseForDirectory(): void
    {
        self::assertFalse($this->checker->isFile(__DIR__, ''));
    }

    /**
     * @return void
     */
    #[Test]
    public function isDirectoryWillReturnFalseForNonExistent(): void
    {
        self::assertFalse($this->checker->isDirectory('/project', '/nonexistent'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isFileWillReturnFalseForNonExistent(): void
    {
        self::assertFalse($this->checker->isFile('/project', '/nonexistent.php'));
    }
}
