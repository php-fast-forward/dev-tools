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

namespace FastForward\DevTools\Tests\Composer\Json\Schema;

use FastForward\DevTools\Composer\Json\Schema\Author;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Author::class)]
final class AuthorTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function accessorsWillReturnValuesProvidedToConstructor(): void
    {
        $author = new Author(
            'Felipe',
            'github@mentordosnerds.com',
            'https://github.com/mentordosnerds',
            'Developer'
        );

        self::assertSame('Felipe', $author->getName());
        self::assertSame('github@mentordosnerds.com', $author->getEmail());
        self::assertSame('https://github.com/mentordosnerds', $author->getHomepage());
        self::assertSame('Developer', $author->getRole());
    }

    /**
     * @return void
     */
    #[Test]
    public function toStringWillReturnFormattedStringWhenNameAndEmailAreProvided(): void
    {
        $author = new Author('Felipe', 'github@mentordosnerds.com');

        self::assertSame('Felipe <github@mentordosnerds.com>', (string) $author);
    }

    /**
     * @return void
     */
    #[Test]
    public function toStringWillReturnOnlyNameWhenEmailIsMissing(): void
    {
        $author = new Author('Felipe');

        self::assertSame('Felipe', (string) $author);
    }

    /**
     * @return void
     */
    #[Test]
    public function toStringWillReturnOnlyEmailWhenNameIsMissing(): void
    {
        $author = new Author(email: 'github@mentordosnerds.com');

        self::assertSame('github@mentordosnerds.com', (string) $author);
    }

    /**
     * @return void
     */
    #[Test]
    public function toStringWillReturnEmptyStringWhenBothNameAndEmailAreMissing(): void
    {
        $author = new Author();

        self::assertSame('', (string) $author);
    }
}
