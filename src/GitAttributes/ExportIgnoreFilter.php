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

namespace FastForward\DevTools\GitAttributes;

use function Safe\preg_replace;

/**
 * Filters export-ignore candidates using normalized path comparisons.
 *
 * This filter SHALL compare configured keep-in-export paths against canonical
 * candidates while ignoring leading and trailing slash differences. It MUST
 * preserve the original candidate ordering in the filtered result.
 */
final class ExportIgnoreFilter implements ExportIgnoreFilterInterface
{
    /**
     * Filters export-ignore candidates using the configured keep-in-export paths.
     *
     * @param list<string> $candidates the canonical candidate paths
     * @param list<string> $keepInExportPaths the paths that MUST remain exportable
     *
     * @return list<string> the filtered export-ignore candidates
     */
    public function filter(array $candidates, array $keepInExportPaths): array
    {
        $keptPathLookup = [];

        foreach ($keepInExportPaths as $path) {
            $normalizedPath = $this->normalizePath($path);

            if ('' === $normalizedPath) {
                continue;
            }

            $keptPathLookup[$normalizedPath] = true;
        }

        return array_values(array_filter(
            $candidates,
            fn(string $candidate): bool => ! isset($keptPathLookup[$this->normalizePath($candidate)])
        ));
    }

    /**
     * Normalizes a configured path for stable matching.
     *
     * @param string $path the raw path from candidates or Composer extra config
     *
     * @return string the normalized path used for comparisons
     */
    private function normalizePath(string $path): string
    {
        $trimmedPath = trim($path);

        if ('' === $trimmedPath) {
            return '';
        }

        $normalizedPath = preg_replace('#/+#', '/', '/' . ltrim($trimmedPath, '/')) ?? $trimmedPath;

        return '/' === $normalizedPath ? $normalizedPath : rtrim($normalizedPath, '/');
    }
}
