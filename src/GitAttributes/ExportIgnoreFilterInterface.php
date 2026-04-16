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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\GitAttributes;

/**
 * Filters canonical export-ignore candidates against consumer keep rules.
 *
 * Implementations MUST remove any candidate path explicitly configured to stay
 * in the exported package archive, while preserving the order of the remaining
 * candidates.
 */
interface ExportIgnoreFilterInterface
{
    /**
     * Filters export-ignore candidates using the configured keep-in-export paths.
     *
     * @param list<string> $candidates the canonical candidate paths
     * @param list<string> $keepInExportPaths the paths that MUST remain exportable
     *
     * @return list<string> the filtered export-ignore candidates
     */
    public function filter(array $candidates, array $keepInExportPaths): array;
}
