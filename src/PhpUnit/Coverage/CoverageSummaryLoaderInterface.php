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

/**
 * Loads PHPUnit `coverage-php` reports and exposes their line coverage summary.
 */
interface CoverageSummaryLoaderInterface
{
    /**
     * Loads the coverage summary from a PHPUnit `coverage-php` report file.
     *
     * @param string $coverageReportPath Path to the PHPUnit `coverage-php` report file
     *
     * @return CoverageSummary Extracted coverage summary
     *
     * @throws RuntimeException When the report file does not exist or contains invalid data
     */
    public function load(string $coverageReportPath): CoverageSummary;
}
