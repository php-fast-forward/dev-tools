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

namespace FastForward\DevTools\Tests\GitIgnore;

use FastForward\DevTools\GitIgnore\GitIgnore;
use FastForward\DevTools\GitIgnore\GitIgnoreInterface;
use FastForward\DevTools\GitIgnore\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\unlink;

#[CoversClass(Reader::class)]
#[UsesClass(GitIgnore::class)]
final class ReaderTest extends TestCase
{
    private readonly Reader $reader;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->reader = new Reader();
    }

    /**
     * @return void
     */
    #[Test]
    public function readWithNonExistentFileReturnsEmptyGitIgnore(): void
    {
        $result = $this->reader->read('/non/existent/.gitignore');

        self::assertInstanceOf(GitIgnoreInterface::class, $result);
        self::assertSame('/non/existent/.gitignore', $result->path());
        self::assertSame([], $result->entries());
    }

    /**
     * @return void
     */
    #[Test]
    public function readWithExistingFileReturnsGitIgnoreWithEntries(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_reader_' . uniqid() . '.gitignore';
        file_put_contents($tempFile, "vendor/\nnode_modules/\n*.log\n");

        try {
            $result = $this->reader->read($tempFile);

            self::assertInstanceOf(GitIgnoreInterface::class, $result);
            self::assertSame($tempFile, $result->path());
            self::assertSame(['vendor/', 'node_modules/', '*.log'], $result->entries());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function readReturnsGitIgnoreInterfaceInstance(): void
    {
        $result = $this->reader->read('/test/.gitignore');

        self::assertInstanceOf(GitIgnoreInterface::class, $result);
    }
}
