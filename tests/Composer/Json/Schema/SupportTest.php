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

use FastForward\DevTools\Composer\Json\Schema\Support;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Support::class)]
final class SupportTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function accessorsWillReturnValuesProvidedToConstructor(): void
    {
        $support = new Support(
            'support@example.com',
            'https://github.com/example/issues',
            'https://forum.example.com',
            'https://wiki.example.com',
            'irc://irc.freenode.net/example',
            'https://github.com/example',
            'https://docs.example.com',
            'https://example.com/rss',
            'https://chat.example.com',
            'https://example.com/security'
        );

        self::assertSame('support@example.com', $support->getEmail());
        self::assertSame('https://github.com/example/issues', $support->getIssues());
        self::assertSame('https://forum.example.com', $support->getForum());
        self::assertSame('https://wiki.example.com', $support->getWiki());
        self::assertSame('irc://irc.freenode.net/example', $support->getIrc());
        self::assertSame('https://github.com/example', $support->getSource());
        self::assertSame('https://docs.example.com', $support->getDocs());
        self::assertSame('https://example.com/rss', $support->getRss());
        self::assertSame('https://chat.example.com', $support->getChat());
        self::assertSame('https://example.com/security', $support->getSecurity());
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillInitializeWithEmptyStringsByDefault(): void
    {
        $support = new Support();

        self::assertSame('', $support->getEmail());
        self::assertSame('', $support->getIssues());
        self::assertSame('', $support->getForum());
        self::assertSame('', $support->getWiki());
        self::assertSame('', $support->getIrc());
        self::assertSame('', $support->getSource());
        self::assertSame('', $support->getDocs());
        self::assertSame('', $support->getRss());
        self::assertSame('', $support->getChat());
        self::assertSame('', $support->getSecurity());
    }
}
