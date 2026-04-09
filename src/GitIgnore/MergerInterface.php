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

namespace FastForward\DevTools\GitIgnore;

/**
 * Defines the contract for merging .gitignore entries.
 *
 * This service SHALL combine canonical and project-specific .gitignore
 * definitions into a single normalized result. The resulting entry list MUST
 * exclude blank lines and comment lines from the merged output, MUST remove
 * duplicate entries, and MUST group directory entries before file entries.
 * Directory and file groups SHALL be sorted independently in ascending string
 * order to provide deterministic output.
 */
interface MergerInterface
{
    /**
     * Merges two GitIgnore instances, removing duplicates and sorting entries.
     *
     * Directories are placed before files in the resulting list.
     * The path from $project is used in the returned instance.
     *
     * @param GitIgnoreInterface $canonical the canonical .gitignore from dev-tools
     * @param GitIgnoreInterface $project the project-specific .gitignore
     *
     * @return GitIgnoreInterface a new GitIgnore instance with merged entries
     */
    public function merge(GitIgnoreInterface $canonical, GitIgnoreInterface $project): GitIgnoreInterface;
}
