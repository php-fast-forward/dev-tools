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
 *
 * The GitReleaseCollectorInterface defines a contract for collecting release information from git tags in a specified working directory.
 * Implementations of this interface are responsible for executing git commands to read tags and their associated commit subjects, building
 * a structured list of releases that includes version, tag name, creation date, and commit
 */
interface GitReleaseCollectorInterface
{
    /**
     * Collects release information from git tags in the specified working directory.
     *
     * The method SHOULD read git tags and their associated commit subjects to build a structured list of releases.
     * Each release entry MUST include the version, tag name, creation date, and a list of commit subjects that are part of that release.
     *
     * @param string $workingDirectory Directory in which to execute git commands (e.g., repository root).
     *
     * @return list<array{version: string, tag: string, date: string, commits: list<string>}> list of releases with version, tag, date, and associated commit subjects
     */
    public function collect(string $workingDirectory): array;
}
