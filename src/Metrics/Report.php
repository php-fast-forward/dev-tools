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

/**
 * Represents the reduced metrics summary shown by the dev-tools metrics command.
 */
final readonly class Report
{
    /**
     * @param float $averageCyclomaticComplexityByClass the average class cyclomatic complexity reported by PhpMetrics
     * @param float $averageMaintainabilityIndexByClass the average class maintainability index reported by PhpMetrics
     * @param int $classesAnalyzed the number of analyzed classes
     * @param int $functionsAnalyzed the number of analyzed functions
     */
    public function __construct(
        public float $averageCyclomaticComplexityByClass,
        public float $averageMaintainabilityIndexByClass,
        public int $classesAnalyzed,
        public int $functionsAnalyzed,
    ) {}
}
