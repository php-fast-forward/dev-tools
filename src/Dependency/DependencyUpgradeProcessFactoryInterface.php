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

namespace FastForward\DevTools\Dependency;

use Symfony\Component\Process\Process;

/**
 * Builds the upgrade workflow processes for dependency management.
 */
interface DependencyUpgradeProcessFactoryInterface
{
    /**
     * @param bool $fix whether the workflow SHOULD apply changes instead of using preview mode
     * @param bool $dev whether Jack SHOULD prioritize dev dependencies first
     *
     * @return list<Process> the upgrade workflow processes in execution order
     */
    public function create(bool $fix, bool $dev): array;
}
