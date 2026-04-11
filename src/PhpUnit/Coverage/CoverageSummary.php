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
     * Initializes a new instance of the CoverageSummary class.
     *
     * @param int $executedLines Number of executable lines covered
     * @param int $executableLines Total executable lines in the analyzed code
     */
    public function __construct(
        private int $executedLines,
        private int $executableLines,
    ) {}

    /**
     * Returns the number of executable lines that were executed.
     *
     * @return int
     */
    public function executedLines(): int
    {
        return $this->executedLines;
    }

    /**
     * Returns the total number of executable lines.
     *
     * @return int
     */
    public function executableLines(): int
    {
        return $this->executableLines;
    }

    /**
     * Returns the executed line coverage as a percentage.
     *
     * @return float
     */
    public function percentage(): float
    {
        if (0 === $this->executableLines) {
            return 100.0;
        }

        return ($this->executedLines / $this->executableLines) * 100;
    }

    /**
     * Returns the executed line coverage as a formatted percentage string.
     *
     * @return string
     */
    public function percentageAsString(): string
    {
        return \sprintf('%01.2F%%', $this->percentage());
    }
}
