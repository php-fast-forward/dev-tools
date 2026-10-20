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

use FastForward\DevTools\GitAttributes\Reader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\file_put_contents;
use function Safe\unlink;

#[CoversClass(Reader::class)]
final class ReaderTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function readWithNonExistentFileWillReturnEmptyString(): void
    {
        $reader = new Reader();

        self::assertSame('', $reader->read('/non/existent/.gitattributes'));
    }

    /**
     * @return void
     */
    #[Test]
    public function readWithExistingFileWillReturnFileContents(): void
    {
        $reader = new Reader();
        $tempFile = sys_get_temp_dir() . '/test_gitattributes_reader_' . uniqid() . '.gitattributes';

        file_put_contents($tempFile, "*.zip -diff\n");

        try {
            self::assertSame("*.zip -diff\n", $reader->read($tempFile));
        } finally {
            @unlink($tempFile);
        }
    }
}
