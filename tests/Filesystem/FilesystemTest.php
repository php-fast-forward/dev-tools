<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Filesystem;

use FastForward\DevTools\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

use function Safe\getcwd;

#[CoversClass(Filesystem::class)]
final class FilesystemTest extends TestCase
{
    private Filesystem $filesystem;

    private string $tempDir;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('ff_dev_tools_', true);
        $this->filesystem->mkdir($this->tempDir);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    /**
     * @return void
     */
    #[Test]
    public function getAbsolutePathWillReturnAbsoluteForRelativePath(): void
    {
        $expected = Path::makeAbsolute('test/file.php', getcwd());

        self::assertSame($expected, $this->filesystem->getAbsolutePath('test/file.php'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAbsolutePathWillReturnAbsoluteForMultipleRelativePaths(): void
    {
        $expected = [Path::makeAbsolute('test1.php', getcwd()), Path::makeAbsolute('test2.php', getcwd())];

        // Ensure returning array has matching elements
        $result = $this->filesystem->getAbsolutePath(['test1.php', 'test2.php']);

        self::assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function getAbsolutePathWillUseProvidedBasePath(): void
    {
        $basePath = '/var/www';
        $expected = Path::makeAbsolute('test.php', $basePath);

        self::assertSame($expected, $this->filesystem->getAbsolutePath('test.php', $basePath));
    }

    /**
     * @return void
     */
    #[Test]
    public function basenameWillReturnCorrectBasename(): void
    {
        self::assertSame('file', $this->filesystem->basename('/path/to/file.txt', '.txt'));
        self::assertSame('file.txt', $this->filesystem->basename('/path/to/file.txt'));
    }

    /**
     * @return void
     */
    #[Test]
    public function dirnameWillReturnCorrectDirname(): void
    {
        self::assertSame('/path/to', $this->filesystem->dirname('/path/to/file.txt'));
        self::assertSame('/path', $this->filesystem->dirname('/path/to/file.txt', 2));
    }

    /**
     * @return void
     */
    #[Test]
    public function makePathRelativeWillReturnRelativePathAgainstBase(): void
    {
        $path = '/var/www/project/src/file.php';
        $basePath = '/var/www/project';

        $relative = $this->filesystem->makePathRelative($path, $basePath);

        // Symfony makePathRelative usually returns trailing slash for directories, but not required for files
        self::assertStringStartsWith('src/file.php', $relative);
    }

    /**
     * @return void
     */
    #[Test]
    public function dumpFileAndReadFileWillWorkWithRelativePaths(): void
    {
        $filename = 'test_file.txt';
        $content = 'hello world';

        $this->filesystem->dumpFile($filename, $content, $this->tempDir);

        self::assertTrue($this->filesystem->exists($filename, $this->tempDir));
        self::assertSame($content, $this->filesystem->readFile($filename, $this->tempDir));
    }

    /**
     * @return void
     */
    #[Test]
    public function mkdirWillCreateDirectoryWithRelativePath(): void
    {
        $dirName = 'nested/dir';

        $this->filesystem->mkdir($dirName, 0o777, $this->tempDir);

        self::assertTrue($this->filesystem->exists($dirName, $this->tempDir));
        self::assertDirectoryExists($this->tempDir . '/' . $dirName);
    }
}
