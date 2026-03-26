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

namespace FastForward\DevTools\Docblock;

use Override;
use phootwork\collection\ArrayList;
use phpowermove\docblock\Docblock;
use phpowermove\docblock\tags\AbstractTag;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionProperty;

/**
 * Represents a Docblock that preserves and sorts tags in a defined order for PHPDoc normalization.
 */
final class OrderedDocblock extends Docblock
{
    /**
     * Creates an OrderedDocblock instance from a docblock string or reflection.
     *
     * @param ReflectionFunctionAbstract|ReflectionClass|ReflectionProperty|string $docblock the docblock source
     *
     * @return self the created OrderedDocblock instance
     */
    #[Override]
    public static function create(
        ReflectionFunctionAbstract|ReflectionClass|ReflectionProperty|string $docblock = ''
    ): self {
        return new self($docblock);
    }

    /**
     * Returns the tags sorted by the defined priority order: param, return, throws, then others.
     *
     * @return ArrayList<AbstractTag> the sorted tags
     */
    #[Override]
    public function getSortedTags(): ArrayList
    {
        $tagOrder = [
            'param' => 10,
            'return' => 20,
            'throws' => 30,
        ];

        $indexedTags = [];

        /** @var AbstractTag $tag */
        foreach ($this->getTags()->toArray() as $index => $tag) {
            $indexedTags[] = [
                'index' => $index,
                'tag' => $tag,
            ];
        }

        usort($indexedTags, static function (array $left, array $right) use ($tagOrder): int {
            /** @var AbstractTag $leftTag */
            $leftTag = $left['tag'];

            /** @var AbstractTag $rightTag */
            $rightTag = $right['tag'];

            $leftPriority = $tagOrder[$leftTag->getTagName()] ?? 1000;
            $rightPriority = $tagOrder[$rightTag->getTagName()] ?? 1000;

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            $tagNameComparison = $leftTag->getTagName() <=> $rightTag->getTagName();
            if (0 !== $tagNameComparison) {
                return $tagNameComparison;
            }

            return $left['index'] <=> $right['index'];
        });

        $sorted = new ArrayList();

        foreach ($indexedTags as $item) {
            $sorted->add($item['tag']);
        }

        return $sorted;
    }
}
