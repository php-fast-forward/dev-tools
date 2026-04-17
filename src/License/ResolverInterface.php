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

namespace FastForward\DevTools\License;

/**
 * Resolves license identifiers to their corresponding template filenames.
 *
 * This interface checks whether a given license is supported and maps it
 * to the appropriate license template file for content generation.
 */
interface ResolverInterface
{
    /**
     * Resolves a license identifier to its template filename.
     *
     * @param string $license The license identifier to resolve
     *
     * @return string|null The template filename if supported, or null if not
     */
    public function resolve(string $license): ?string;
}
