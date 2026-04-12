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

namespace FastForward\DevTools\Changelog;

/**
 * Discovers released tags and the commit subjects they contain.
 */
interface GitReleaseCollectorInterface
{
    /**
     * @param string $workingDirectory
     *
     * @return list<array{version: string, tag: string, date: string, commits: list<string>}>
     */
    public function collect(string $workingDirectory): array;
}
