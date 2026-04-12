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

use Symfony\Component\Filesystem\Path;
use Throwable;

use function Safe\preg_match;
use function Safe\preg_split;
use function Safe\file_get_contents;
use function array_diff;
use function array_filter;
use function array_map;
use function array_values;
use function trim;

/**
 * Compares unreleased changelog entries against the current branch or a base ref.
 */
final readonly class UnreleasedEntryChecker implements UnreleasedEntryCheckerInterface
{
    /**
     * Constructs a new UnreleasedEntryChecker.
     *
     * @param GitProcessRunnerInterface $gitProcessRunner the Git process runner
     */
    public function __construct(
        private GitProcessRunnerInterface $gitProcessRunner = new GitProcessRunner()
    ) {}

    /**
     * Checks if there are pending unreleased entries in the changelog compared to a given reference.
     *
     * @param string $workingDirectory the working directory of the repository
     * @param string|null $againstReference The reference to compare against (e.g., a branch or commit hash).
     *
     * @return bool true if there are pending unreleased entries, false otherwise
     */
    public function hasPendingChanges(string $workingDirectory, ?string $againstReference = null): bool
    {
        $currentPath = Path::join($workingDirectory, 'CHANGELOG.md');

        if (! is_file($currentPath)) {
            return false;
        }

        $currentEntries = $this->extractEntries(file_get_contents($currentPath));

        if ([] === $currentEntries) {
            return false;
        }

        if (null === $againstReference) {
            return true;
        }

        try {
            $baseline = $this->gitProcessRunner->run([
                'git',
                'show',
                $againstReference . ':CHANGELOG.md',
            ], $workingDirectory);
        } catch (Throwable) {
            return true;
        }

        $baselineEntries = $this->extractEntries($baseline);

        return [] !== array_values(array_diff($currentEntries, $baselineEntries));
    }

    /**
     * Extracts unreleased entries from the given changelog content.
     *
     * @param string $contents the changelog content
     *
     * @return list<string> the list of unreleased entries
     */
    private function extractEntries(string $contents): array
    {
        if (0 === preg_match('/^## \[?Unreleased\]?\s+-\s+.+?(?=^##\s|\z)/ms', $contents, $matches)) {
            return [];
        }

        $lines = preg_split('/\R/', trim($matches[0]));

        return array_values(array_filter(array_map(static function (string $line): ?string {
            $line = trim($line);

            if (0 === preg_match('/^- (.+)$/', $line, $matches)) {
                return null;
            }

            $entry = trim($matches[1]);

            if ('Nothing.' === $entry) {
                return null;
            }

            return $entry;
        }, $lines)));
    }
}
