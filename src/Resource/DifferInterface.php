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

namespace FastForward\DevTools\Resource;

/**
 * Defines the contract for generating unified diffs.
 */
interface DifferInterface
{
    /**
     * Generates a unified diff between current and updated content.
     *
     * @param string $currentContent the current content
     * @param string $updatedContent the updated content
     *
     * @return string the unified diff
     */
    public function diff(string $currentContent, string $updatedContent): string;
}
