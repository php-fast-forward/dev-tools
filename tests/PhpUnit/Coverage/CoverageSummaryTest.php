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

namespace FastForward\DevTools\PhpUnit\Coverage;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(CoverageSummary::class)]
final class CoverageSummaryTest extends TestCase
{
    /**
     * @return array
     */
    public static function provideCoverageData(): array
    {
        return [[0, 0, 100.0], [50, 200, 25.0], [1, 3, 33.33333333333333]];
    }

    /**
     * @param int $executed
     * @param int $executable
     * @param float $expectedPercentage
     *
     * @return void
     */
    #[Test]
    #[DataProvider('provideCoverageData')]
    public function executedLinesReturnsValue(int $executed, int $executable, float $expectedPercentage): void
    {
        $summary = new CoverageSummary($executed, $executable);
        self::assertSame($executed, $summary->executedLines());
        self::assertSame($executable, $summary->executableLines());
        self::assertSame($expectedPercentage, $summary->percentage());
    }
}
