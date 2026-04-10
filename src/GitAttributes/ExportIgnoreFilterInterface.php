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
