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
 * Merges, deduplicates, and sorts .gitignore entries.
 */
final readonly class Merger
{
    /**
     * @param Classifier $classifier
     */
    public function __construct(
        private Classifier $classifier
    ) {}

    /**
     * Merges canonical and project entries, removes duplicates, and sorts.
     *
     * @param array<int, string> $canonical the canonical .gitignore entries from dev-tools
     * @param array<int, string> $project the project-specific .gitignore entries
     *
     * @return array<int, string> the merged and sorted entries
     */
    public function merge(array $canonical, array $project): array
    {
        $entries = array_unique(array_merge($canonical, $project));

        $directories = [];
        $files = [];

        foreach ($entries as $entry) {
            $trimmed = trim($entry);
            if ('' === $trimmed) {
                continue;
            }
            if (str_starts_with($trimmed, '#')) {
                continue;
            }

            if ($this->classifier->isDirectory($trimmed)) {
                $directories[] = $trimmed;
            } else {
                $files[] = $trimmed;
            }
        }

        sort($directories, \SORT_STRING);
        sort($files, \SORT_STRING);

        return array_merge($directories, $files);
    }
}
