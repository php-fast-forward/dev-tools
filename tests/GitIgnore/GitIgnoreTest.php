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

namespace FastForward\DevTools\Tests\GitIgnore;

use ArrayIterator;
use FastForward\DevTools\GitIgnore\GitIgnore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\unlink;

/**
 * Test suite for the GitIgnore value object.
 *
 * This test class verifies the correct behavior of the GitIgnore class,
 * including construction, path and entries access, iteration, and the
 * factory method for loading from files.
 */
#[CoversClass(GitIgnore::class)]
final class GitIgnoreTest extends TestCase
{
    /**
     * Tests that the constructor correctly stores path and entries.
     */
    #[Test]
    public function constructWithPathAndEntries(): void
    {
        $gitignore = new GitIgnore('/path/to/.gitignore', ['vendor/', 'node_modules/', '*.log']);

        self::assertSame('/path/to/.gitignore', $gitignore->path());
        self::assertSame(['vendor/', 'node_modules/', '*.log'], $gitignore->entries());
    }

    /**
     * Tests that the path() method returns the correct file path.
     */
    #[Test]
    public function pathReturnsFilePath(): void
    {
        $gitignore = new GitIgnore('/test/.gitignore', ['*.log']);

        self::assertSame('/test/.gitignore', $gitignore->path());
    }

    /**
     * Tests that the entries() method returns the list of entries.
     */
    #[Test]
    public function entriesReturnsListOfEntries(): void
    {
        $entries = ['vendor/', 'node_modules/', '*.log', '#comment', '  ', ''];
        $gitignore = new GitIgnore('/test/.gitignore', $entries);

        self::assertSame($entries, $gitignore->entries());
    }

    /**
     * Tests that getIterator() returns an ArrayIterator with the correct entries.
     */
    #[Test]
    public function getIteratorReturnsArrayIterator(): void
    {
        $gitignore = new GitIgnore('/test/.gitignore', ['a', 'b', 'c']);
        $iterator = $gitignore->getIterator();

        self::assertInstanceOf(ArrayIterator::class, $iterator);
        self::assertCount(3, $iterator);
    }

    /**
     * Tests that fromFile() returns an empty GitIgnore when the file does not exist.
     */
    #[Test]
    public function fromFileWithNonExistentFileReturnsEmptyGitIgnore(): void
    {
        $gitignore = GitIgnore::fromFile('/non/existent/.gitignore');

        self::assertSame('/non/existent/.gitignore', $gitignore->path());
        self::assertSame([], $gitignore->entries());
    }

    /**
     * Tests that fromFile() correctly reads entries from an existing file.
     */
    #[Test]
    public function fromFileWithExistingFileReadsEntries(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_gitignore_' . uniqid() . '.gitignore';
        file_put_contents($tempFile, "vendor/\nnode_modules/\n*.log\n#comment\n  \n");

        try {
            $gitignore = GitIgnore::fromFile($tempFile);

            self::assertSame($tempFile, $gitignore->path());
            self::assertSame(['vendor/', 'node_modules/', '*.log', '#comment'], $gitignore->entries());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Tests that fromFile() filters out empty lines and whitespace-only lines.
     */
    #[Test]
    public function fromFileFiltersEmptyLines(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_gitignore_' . uniqid() . '.gitignore';
        file_put_contents($tempFile, "vendor/\n\n\nnode_modules/\n   \n\n*.log\n");

        try {
            $gitignore = GitIgnore::fromFile($tempFile);

            self::assertSame(['vendor/', 'node_modules/', '*.log'], $gitignore->entries());
        } finally {
            unlink($tempFile);
        }
    }
}
