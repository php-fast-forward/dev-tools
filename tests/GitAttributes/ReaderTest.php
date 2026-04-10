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
