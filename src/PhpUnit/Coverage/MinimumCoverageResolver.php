<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\PhpUnit\Coverage;

use InvalidArgumentException;

use function is_numeric;

/**
 * Resolves the existing minimum-coverage percentage independently of CLI input.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */
final readonly class MinimumCoverageResolver
{
    /**
     * Validates a caller-selected threshold without reading environment or console state.
     *
     * @param string|null $value the configured threshold, or null when no threshold was selected
     *
     * @return float|null the percentage, or null when no threshold is configured
     *
     * @throws InvalidArgumentException when the value is not a percentage between zero and one hundred
     */
    public function __invoke(?string $value): ?float
    {
        $value = trim($value ?? '');

        if ('' === $value) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('The --min-coverage option MUST be a numeric percentage.');
        }

        $percentage = (float) $value;

        if (0.0 > $percentage || 100.0 < $percentage) {
            throw new InvalidArgumentException('The --min-coverage option MUST be between 0 and 100.');
        }

        return $percentage;
    }
}
