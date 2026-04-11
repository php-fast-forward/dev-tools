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

namespace FastForward\DevTools\PhpUnit\Coverage;

/**
 * Represents the line coverage summary extracted from a PHPUnit `coverage-php` report.
 */
final readonly class CoverageSummary
{
    /**
     * @param int $executedLines the number of executable lines that were covered
     * @param int $executableLines the total number of executable lines
     */
    public function __construct(
        private int $executedLines,
        private int $executableLines,
    ) {}

    /**
     * @return int the number of covered executable lines
     */
    public function executedLines(): int
    {
        return $this->executedLines;
    }

    /**
     * @return int the total number of executable lines
     */
    public function executableLines(): int
    {
        return $this->executableLines;
    }

    /**
     * @return float the executed line coverage percentage
     */
    public function percentage(): float
    {
        if (0 === $this->executableLines) {
            return 100.0;
        }

        return ($this->executedLines / $this->executableLines) * 100;
    }

    /**
     * @return string the formatted executed line coverage percentage
     */
    public function percentageAsString(): string
    {
        return \sprintf('%01.2F%%', $this->percentage());
    }
}
