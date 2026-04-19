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

use FastForward\DevTools\Filesystem\Filesystem;
use FastForward\DevTools\Resource\OverwriteDiffRenderer;
use FastForward\DevTools\Resource\OverwriteDiffResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;
use function sprintf;
use function sys_get_temp_dir;

#[CoversClass(OverwriteDiffRenderer::class)]
#[UsesClass(OverwriteDiffResult::class)]
#[UsesClass(Filesystem::class)]
final class OverwriteDiffRendererTest extends TestCase
{
    private string $tempDirectory;

    private OverwriteDiffRenderer $renderer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempDirectory = sys_get_temp_dir() . '/overwrite-diff-renderer-' . bin2hex(random_bytes(4));
        mkdir($this->tempDirectory);
        $this->renderer = new OverwriteDiffRenderer(new Filesystem());
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (['source.txt', 'target.txt', 'binary.bin'] as $filename) {
            $path = $this->tempDirectory . '/' . $filename;

            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->tempDirectory)) {
            rmdir($this->tempDirectory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnChangedResultWithUnifiedDiff(): void
    {
        $sourcePath = $this->tempDirectory . '/source.txt';
        $targetPath = $this->tempDirectory . '/target.txt';

        file_put_contents($sourcePath, "new\n");
        file_put_contents($targetPath, "old\n");

        $result = $this->renderer->render($sourcePath, $targetPath);

        self::assertSame(OverwriteDiffResult::STATUS_CHANGED, $result->status());
        self::assertSame(sprintf('Overwriting resource %s from %s.', $targetPath, $sourcePath), $result->summary());
        self::assertNotNull($result->diff());
        self::assertStringContainsString('--- Current: ' . $targetPath, $result->diff());
        self::assertStringContainsString('+++ Source: ' . $sourcePath, $result->diff());
        self::assertStringContainsString('-old', $result->diff());
        self::assertStringContainsString('+new', $result->diff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnUnchangedResultWhenContentsMatch(): void
    {
        $sourcePath = $this->tempDirectory . '/source.txt';
        $targetPath = $this->tempDirectory . '/target.txt';

        file_put_contents($sourcePath, "same\n");
        file_put_contents($targetPath, "same\n");

        $result = $this->renderer->render($sourcePath, $targetPath);

        self::assertSame(OverwriteDiffResult::STATUS_UNCHANGED, $result->status());
        self::assertSame(
            sprintf('Target %s already matches source %s; overwrite skipped.', $targetPath, $sourcePath),
            $result->summary(),
        );
        self::assertNull($result->diff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnBinaryResultWhenNullBytesAreDetected(): void
    {
        $sourcePath = $this->tempDirectory . '/binary.bin';
        $targetPath = $this->tempDirectory . '/target.txt';

        file_put_contents($sourcePath, "bin\0ary");
        file_put_contents($targetPath, "text\n");

        $result = $this->renderer->render($sourcePath, $targetPath);

        self::assertSame(OverwriteDiffResult::STATUS_BINARY, $result->status());
        self::assertSame(
            sprintf(
                'Target %s will be overwritten from %s, but a text diff is unavailable for binary content.',
                $targetPath,
                $sourcePath,
            ),
            $result->summary(),
        );
        self::assertNull($result->diff());
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillReturnUnreadableResultWhenContentsCannotBeRead(): void
    {
        $sourcePath = $this->tempDirectory . '/source.txt';
        $targetPath = $this->tempDirectory . '/missing.txt';

        file_put_contents($sourcePath, "new\n");

        $result = $this->renderer->render($sourcePath, $targetPath);

        self::assertSame(OverwriteDiffResult::STATUS_UNREADABLE, $result->status());
        self::assertSame(
            sprintf(
                'Target %s will be overwritten from %s, but the existing or source content could not be read.',
                $targetPath,
                $sourcePath,
            ),
            $result->summary(),
        );
        self::assertNull($result->diff());
    }
}
