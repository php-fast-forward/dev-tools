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
 *
 * This service SHALL combine canonical and project-specific .gitignore
 * definitions into a single normalized result. The resulting entry list MUST
 * exclude blank lines and comment lines from the merged output, MUST remove
 * duplicate entries, and MUST group directory entries before file entries.
 * Directory and file groups SHALL be sorted independently in ascending string
 * order to provide deterministic output.
 */
final readonly class Merger implements MergerInterface
{
    /**
     * Initializes the merger with a classifier implementation.
     *
     * The classifier MUST be capable of determining whether a normalized
     * .gitignore entry represents a directory or a file pattern. When no
     * classifier is provided, a default Classifier instance SHALL be used.
     *
     * @param ClassifierInterface $classifier the classifier responsible for
     *                                        distinguishing directory entries
     *                                        from file entries during merging
     */
    public function __construct(
        private ClassifierInterface $classifier = new Classifier()
    ) {}

    /**
     * Merges canonical and project .gitignore entries into a normalized result.
     *
     * The implementation MUST combine entries from both sources, MUST remove
     * duplicates, and MUST ignore blank or commented entries after trimming.
     * Entries identified as directories SHALL be collected separately from file
     * entries. Each group MUST be sorted using string comparison, and directory
     * entries MUST appear before file entries in the final result.
     *
     * The returned GitIgnore instance SHALL preserve the project path provided by
     * the $project argument.
     *
     * @param GitIgnoreInterface $canonical The canonical .gitignore source whose
     *                                      entries provide the shared baseline.
     * @param GitIgnoreInterface $project The project-specific .gitignore source
     *                                    whose path MUST be preserved in the
     *                                    merged result.
     *
     * @return GitIgnoreInterface A new merged .gitignore representation containing
     *                            normalized, deduplicated, and ordered entries.
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
