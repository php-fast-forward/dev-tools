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

namespace FastForward\DevTools\Tests\Resource;

use RuntimeException;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\DifferInterface;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Throwable;

#[CoversClass(FileDiffer::class)]
#[CoversClass(FileDiff::class)]
final class FileDifferTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<DifferInterface>
     */
    private ObjectProphecy $differ;

    private FileDiffer $renderer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->differ = $this->prophesize(DifferInterface::class);
        $this->renderer = new FileDiffer($this->filesystem->reveal(), $this->differ->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnChangedResultWithUnifiedDiff(): void
    {
        $sourcePath = '/package/.editorconfig';
        $targetPath = '/project/.editorconfig';

        $this->filesystem->readFile($sourcePath)
            ->willReturn("new\n")
            ->shouldBeCalledOnce();
        $this->filesystem->readFile($targetPath)
            ->willReturn("old\n")
            ->shouldBeCalledOnce();
        $this->differ->diff("old\n", "new\n")
            ->willReturn("@@ -1 +1 @@\n-old\n+new")
            ->shouldBeCalledOnce();

        $result = $this->renderer->diff($sourcePath, $targetPath);

        self::assertSame(FileDiff::STATUS_CHANGED, $result->getStatus());
        self::assertSame(\sprintf('Overwriting resource %s from %s.', $targetPath, $sourcePath), $result->getSummary());
        self::assertSame("@@ -1 +1 @@\n-old\n+new", $result->getDiff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnUnchangedResultWhenContentsMatch(): void
    {
        $sourcePath = '/package/.editorconfig';
        $targetPath = '/project/.editorconfig';

        $this->filesystem->readFile($sourcePath)
            ->willReturn("same\n")
            ->shouldBeCalledOnce();
        $this->filesystem->readFile($targetPath)
            ->willReturn("same\n")
            ->shouldBeCalledOnce();
        $this->differ->diff("same\n", "same\n")
            ->shouldNotBeCalled();

        $result = $this->renderer->diff($sourcePath, $targetPath);

        self::assertSame(FileDiff::STATUS_UNCHANGED, $result->getStatus());
        self::assertSame(
            \sprintf('Target %s already matches source %s; overwrite skipped.', $targetPath, $sourcePath),
            $result->getSummary(),
        );
        self::assertNull($result->getDiff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnBinaryResultWhenNullBytesAreDetected(): void
    {
        $sourcePath = '/package/file.bin';
        $targetPath = '/project/file.bin';

        $this->filesystem->readFile($sourcePath)
            ->willReturn("bin\0ary")
            ->shouldBeCalledOnce();
        $this->filesystem->readFile($targetPath)
            ->willReturn("text\n")
            ->shouldBeCalledOnce();
        $this->differ->diff("text\n", "bin\0ary")
            ->shouldNotBeCalled();

        $result = $this->renderer->diff($sourcePath, $targetPath);

        self::assertSame(FileDiff::STATUS_BINARY, $result->getStatus());
        self::assertSame(
            \sprintf(
                'Target %s will be overwritten from %s, but a text diff is unavailable for binary content.',
                $targetPath,
                $sourcePath,
            ),
            $result->getSummary(),
        );
        self::assertNull($result->getDiff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnUnreadableResultWhenContentsCannotBeRead(): void
    {
        $sourcePath = '/package/.editorconfig';
        $targetPath = '/project/.editorconfig';

        $this->filesystem->readFile($sourcePath)
            ->willThrow(new class extends RuntimeException implements Throwable {})
            ->shouldBeCalledOnce();
        $this->filesystem->readFile($targetPath)
            ->shouldNotBeCalled();
        $this->differ->diff('a', 'b')
            ->shouldNotBeCalled();

        $result = $this->renderer->diff($sourcePath, $targetPath);

        self::assertSame(FileDiff::STATUS_UNREADABLE, $result->getStatus());
        self::assertSame(
            \sprintf(
                'Target %s will be overwritten from %s, but the existing or source content could not be read.',
                $targetPath,
                $sourcePath,
            ),
            $result->getSummary(),
        );
        self::assertNull($result->getDiff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderContentsWillUseTheInjectedDifferWithoutCustomHeaders(): void
    {
        $this->differ->diff("old\n", "new\n")
            ->willReturn("@@ -1 +1 @@\n-old\n+new")
            ->shouldBeCalledOnce();

        $result = $this->renderer->diffContents(
            'generated content',
            '/project/file.txt',
            "new\n",
            "old\n",
            'Updating managed file /project/file.txt.',
        );

        self::assertSame(FileDiff::STATUS_CHANGED, $result->getStatus());
        self::assertSame('Updating managed file /project/file.txt.', $result->getSummary());
        self::assertSame("@@ -1 +1 @@\n-old\n+new", $result->getDiff());
    }

    /**
     * @return void
     */
    #[Test]
    public function colorizeWillWrapUnifiedDiffLinesWithConsoleColors(): void
    {
        $diff = "--- Current: /project/.editorconfig\n"
            . "+++ Source: /package/.editorconfig\n"
            . "@@ -1 +1 @@\n"
            . "-old\n"
            . "+new\n"
            . ' unchanged';

        $colorized = $this->renderer->colorize($diff);

        self::assertStringContainsString('<fg=cyan>--- Current: /project/.editorconfig</>', $colorized);
        self::assertStringContainsString('<fg=cyan>+++ Source: /package/.editorconfig</>', $colorized);
        self::assertStringContainsString('<fg=yellow>@@ -1 +1 @@</>', $colorized);
        self::assertStringContainsString('<fg=red>-old</>', $colorized);
        self::assertStringContainsString('<fg=green>+new</>', $colorized);
        self::assertStringContainsString(' unchanged', $colorized);
    }
}
