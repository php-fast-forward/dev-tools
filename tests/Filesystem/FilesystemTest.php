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

namespace FastForward\DevTools\Tests\Filesystem;

use FastForward\DevTools\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

use function Safe\chdir;
use function Safe\fileperms;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;
use function Safe\realpath;
use function Safe\chmod;
use function decoct;

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
    public function getAbsolutePathWillResolveRelativeBasePathsAgainstCurrentWorkingDirectory(): void
    {
        $basePath = 'var/www';
        $expected = Path::makeAbsolute('test.php', Path::makeAbsolute($basePath, getcwd()));

        self::assertSame($expected, $this->filesystem->getAbsolutePath('test.php', $basePath));
    }

    /**
     * @return void
     */
    #[Test]
    public function basenameWillReturnCorrectBasename(): void
    {
        self::assertSame('file', $this->filesystem->getBasename('/path/to/file.txt', '.txt'));
        self::assertSame('file.txt', $this->filesystem->getBasename('/path/to/file.txt'));
    }

    /**
     * @return void
     */
    #[Test]
    public function dirnameWillReturnCorrectDirname(): void
    {
        self::assertSame('/path/to', $this->filesystem->getDirectory('/path/to/file.txt'));
        self::assertSame('/path', $this->filesystem->getDirectory('/path/to/file.txt', 2));
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
        $currentWorkingDirectory = getcwd();

        chdir($this->tempDir);

        try {
            $dirName = 'nested/dir';

            $this->filesystem->mkdir($dirName);

            self::assertTrue($this->filesystem->exists($dirName, $this->tempDir));
            self::assertDirectoryExists($this->tempDir . '/' . $dirName);
        } finally {
            chdir($currentWorkingDirectory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function removeWillDeleteRelativePathAgainstCurrentWorkingDirectory(): void
    {
        $filename = $this->tempDir . '/remove-me.txt';
        file_put_contents($filename, 'temporary');

        $this->filesystem->remove($filename);

        self::assertFileDoesNotExist($filename);
    }

    /**
     * @return void
     */
    #[Test]
    public function symlinkAndReadlinkWillUseAbsolutePaths(): void
    {
        $origin = $this->tempDir . '/origin';
        $target = $this->tempDir . '/target';

        $this->filesystem->mkdir($origin);
        $this->filesystem->symlink($origin, $target);

        self::assertSame(realpath($origin), $this->filesystem->readlink($target, true));
    }

    /**
     * @return void
     */
    #[Test]
    public function symlinkWillPreserveRelativeOrigins(): void
    {
        $currentWorkingDirectory = getcwd();
        $origin = $this->tempDir . '/origin';
        $target = $this->tempDir . '/target';
        $relativeOrigin = 'origin';

        $this->filesystem->mkdir($origin);
        chdir($this->tempDir);

        try {
            $this->filesystem->symlink($relativeOrigin, $target);
        } finally {
            chdir($currentWorkingDirectory);
        }

        self::assertSame($relativeOrigin, $this->filesystem->readlink($target));
        self::assertSame(realpath($origin), $this->filesystem->readlink($target, true));
    }

    /**
     * @return void
     */
    #[Test]
    public function copyWillDuplicateFilesUsingAbsolutePaths(): void
    {
        $origin = $this->tempDir . '/origin.txt';
        $target = $this->tempDir . '/target.txt';
        file_put_contents($origin, 'copied content');

        $this->filesystem->copy($origin, $target);

        self::assertSame('copied content', $this->filesystem->readFile($target));
    }

    /**
     * @return void
     */
    #[Test]
    public function chmodWillApplyPermissionsToResolvedPaths(): void
    {
        $filename = $this->tempDir . '/permissions.txt';
        file_put_contents($filename, 'permission check');
        chmod($filename, 0o644);

        $this->filesystem->chmod($filename, 0o600);

        self::assertStringEndsWith('600', decoct(fileperms($filename) & 0o777));
    }

    /**
     * @return void
     */
    #[Test]
    public function existsWillAcceptIterablesOfRelativePaths(): void
    {
        mkdir($this->tempDir . '/iterable');
        file_put_contents($this->tempDir . '/iterable/a.txt', 'A');
        file_put_contents($this->tempDir . '/iterable/b.txt', 'B');

        self::assertTrue($this->filesystem->exists(['iterable/a.txt', 'iterable/b.txt'], $this->tempDir));
    }
}
