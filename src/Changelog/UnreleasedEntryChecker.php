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
     * @param GitProcessRunnerInterface $gitProcessRunner
     */
    public function __construct(
        private GitProcessRunnerInterface $gitProcessRunner = new GitProcessRunner()
    ) {}

    /**
     * @param string $workingDirectory
     * @param string|null $againstReference
     *
     * @return bool
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
     * @param string $contents
     *
     * @return list<string>
     */
    private function extractEntries(string $contents): array
    {
        if (0 === preg_match('/^## Unreleased\s+-\s+.+?(?=^##\s|\z)/ms', $contents, $matches)) {
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
