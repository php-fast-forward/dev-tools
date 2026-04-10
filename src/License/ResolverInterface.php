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
     * Checks whether the given license identifier is supported.
     *
     * @param string $license The license identifier to check (e.g., "MIT", "Apache-2.0")
     *
     * @return bool True if the license is supported, false otherwise
     */
    public function isSupported(string $license): bool;

    /**
     * Resolves a license identifier to its template filename.
     *
     * @param string $license The license identifier to resolve
     *
     * @return string|null The template filename if supported, or null if not
     */
    public function resolve(string $license): ?string;
}
