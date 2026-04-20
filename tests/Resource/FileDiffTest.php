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

namespace FastForward\DevTools\Tests\Resource;

use FastForward\DevTools\Resource\FileDiff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileDiff::class)]
final class FileDiffTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function accessorsAndStatusHelpersWillReflectTheDiffState(): void
    {
        $changed = new FileDiff(FileDiff::STATUS_CHANGED, 'Changed summary', 'diff body');
        $unchanged = new FileDiff(FileDiff::STATUS_UNCHANGED, 'Unchanged summary');

        self::assertSame(FileDiff::STATUS_CHANGED, $changed->getStatus());
        self::assertSame('Changed summary', $changed->getSummary());
        self::assertSame('diff body', $changed->getDiff());
        self::assertTrue($changed->isChanged());
        self::assertFalse($changed->isUnchanged());

        self::assertSame(FileDiff::STATUS_UNCHANGED, $unchanged->getStatus());
        self::assertSame('Unchanged summary', $unchanged->getSummary());
        self::assertNull($unchanged->getDiff());
        self::assertFalse($unchanged->isChanged());
        self::assertTrue($unchanged->isUnchanged());
    }
}
