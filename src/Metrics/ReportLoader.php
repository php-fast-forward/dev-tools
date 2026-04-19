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

namespace FastForward\DevTools\Metrics;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use JsonException;
use RuntimeException;

use function Safe\json_decode;
use function is_array;
use function is_numeric;
use function round;

/**
 * Derives a reduced command summary from the raw PhpMetrics JSON payload.
 */
final readonly class ReportLoader implements ReportLoaderInterface
{
    /**
     * @param FilesystemInterface $filesystem the filesystem used to read the generated report
     */
    public function __construct(
        private FilesystemInterface $filesystem,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function load(string $path): Report
    {
        try {
            $report = json_decode($this->filesystem->readFile($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new RuntimeException(
                'The PhpMetrics JSON report could not be decoded.',
                previous: $jsonException,
            );
        }

        if (! is_array($report)) {
            throw new RuntimeException('The PhpMetrics JSON report MUST decode to an array.');
        }

        $classesAnalyzed = 0;
        $functionsAnalyzed = 0;
        $cyclomaticComplexityTotal = 0.0;
        $maintainabilityIndexTotal = 0.0;

        foreach ($report as $metric) {
            if (! is_array($metric)) {
                continue;
            }

            $type = $metric['_type'] ?? null;

            if (\Hal\Metric\ClassMetric::class === $type) {
                ++$classesAnalyzed;
                $cyclomaticComplexityTotal += $this->toFloat($metric['ccn'] ?? 0);
                $maintainabilityIndexTotal += $this->toFloat($metric['mi'] ?? 0);

                continue;
            }

            if (\Hal\Metric\FunctionMetric::class === $type) {
                ++$functionsAnalyzed;
            }
        }

        return new Report(
            averageCyclomaticComplexityByClass: 0 === $classesAnalyzed
                ? 0.0
                : round($cyclomaticComplexityTotal / $classesAnalyzed, 2),
            averageMaintainabilityIndexByClass: 0 === $classesAnalyzed
                ? 0.0
                : round($maintainabilityIndexTotal / $classesAnalyzed, 2),
            classesAnalyzed: $classesAnalyzed,
            functionsAnalyzed: $functionsAnalyzed,
        );
    }

    /**
     * @param mixed $value the raw metric value to normalize
     *
     * @return float the normalized floating-point metric value
     */
    private function toFloat(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }
}
