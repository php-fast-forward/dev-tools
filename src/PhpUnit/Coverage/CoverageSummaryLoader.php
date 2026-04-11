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

use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;

use function is_file;

/**
 * Loads line coverage metrics from the serialized PHPUnit `coverage-php` output.
 */
final readonly class CoverageSummaryLoader implements CoverageSummaryLoaderInterface
{
    /**
     * @param string $coverageReportPath Path to the PHPUnit `coverage-php` report file
     *
     * @return CoverageSummary Extracted line coverage summary
     *
     * @throws RuntimeException When the report file does not exist or contains invalid data
     */
    public function load(string $coverageReportPath): CoverageSummary
    {
        if (! is_file($coverageReportPath)) {
            throw new RuntimeException(\sprintf('PHPUnit coverage report not found: %s', $coverageReportPath));
        }

        /** @var mixed $coverage */
        $coverage = require $coverageReportPath;

        if (! $coverage instanceof CodeCoverage) {
            throw new RuntimeException(\sprintf('PHPUnit coverage report is invalid: %s', $coverageReportPath));
        }

        $report = $coverage->getReport();

        return new CoverageSummary($report->numberOfExecutedLines(), $report->numberOfExecutableLines());
    }
}
