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

namespace FastForward\DevTools\Tests\Docblock;

use FastForward\DevTools\Docblock\OrderedDocblock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderedDocblock::class)]
final class OrderedDocblockTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function getSortedTagsWillOrderByPriority(): void
    {
        $docblock = "/**\n * @throws Exception\n * @param string \$a\n * @return int\n * @param int \$b\n */";
        $ordered = OrderedDocblock::create($docblock);
        $tags = $ordered->getSortedTags()
            ->toArray();
        $tagNames = array_map(fn($tag) => $tag->getTagName(), $tags);
        self::assertSame(['param', 'param', 'return', 'throws'], $tagNames);
    }

    /**
     * @return void
     */
    #[Test]
    public function getSortedTagsWillPreserveOrderWithinGroups(): void
    {
        $docblock = "/**\n * @param string \$a\n * @param int \$b\n * @return int\n * @throws Exception\n */";
        $ordered = OrderedDocblock::create($docblock);
        $tags = $ordered->getSortedTags()
            ->toArray();
        self::assertSame('param', $tags[0]->getTagName());
        self::assertSame('param', $tags[1]->getTagName());
        self::assertSame('return', $tags[2]->getTagName());
        self::assertSame('throws', $tags[3]->getTagName());
    }
}
