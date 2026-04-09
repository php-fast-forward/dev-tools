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
final readonly class Merger implements MergerInterface
{
    /**
     * @param ClassifierInterface $classifier
     */
    public function __construct(
        private ClassifierInterface $classifier = new Classifier()
    ) {}

    /**
     * @param GitIgnoreInterface $canonical
     * @param GitIgnoreInterface $project
     *
     * @return GitIgnoreInterface
     */
    public function merge(GitIgnoreInterface $canonical, GitIgnoreInterface $project): GitIgnoreInterface
    {
        $entries = array_unique(array_merge($canonical->entries(), $project->entries()));

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

        $mergedEntries = array_merge($directories, $files);

        return new GitIgnore($project->path(), array_values($mergedEntries));
    }
}
