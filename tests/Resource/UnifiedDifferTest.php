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

use FastForward\DevTools\Resource\UnifiedDiffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

#[CoversClass(UnifiedDiffer::class)]
final class UnifiedDifferTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function diffWillReturnTrimmedUnifiedDiffOutput(): void
    {
        $differ = new UnifiedDiffer(new Differ(new UnifiedDiffOutputBuilder("--- Current\n+++ New\n")));

        $diff = $differ->diff("old\n", "new\n");

        self::assertStringStartsWith('--- Current', $diff);
        self::assertStringContainsString('+++ New', $diff);
        self::assertStringContainsString('-old', $diff);
        self::assertStringContainsString('+new', $diff);
        self::assertStringEndsNotWith("\n", $diff);
    }
}
