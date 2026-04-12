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

use function Safe\preg_match;
use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function str_starts_with;
use function trim;

/**
 * Reads local git tags and commit subjects to build historical changelog data.
 */
final readonly class GitReleaseCollector implements GitReleaseCollectorInterface
{
    /**
     * Initializes the GitReleaseCollector with a GitProcessRunner for executing git commands.
     *
     * @param GitProcessRunnerInterface $gitProcessRunner git process runner for executing git commands
     */
    public function __construct(
        private GitProcessRunnerInterface $gitProcessRunner = new GitProcessRunner()
    ) {}

    /**
     * Collects release information from git tags in the specified working directory.
     *
     * @param string $workingDirectory Directory in which to execute git commands (e.g., repository root).
     *
     * @return list<array{version: string, tag: string, date: string, commits: list<string>}> list of releases with version, tag, date, and associated commit subjects
     */
    public function collect(string $workingDirectory): array
    {
        $output = $this->gitProcessRunner->run([
            'git',
            'for-each-ref',
            '--sort=creatordate',
            '--format=%(refname:short)%09%(creatordate:short)',
            'refs/tags',
        ], $workingDirectory);

        if ('' === $output) {
            return [];
        }

        $releases = [];
        $previousTag = null;

        foreach (explode("\n", $output) as $line) {
            [$tag, $date] = array_pad(explode("\t", trim($line), 2), 2, null);

            if (null === $tag) {
                continue;
            }

            if (null === $date) {
                continue;
            }

            if (0 === preg_match('/^v?(?<version>\d+\.\d+\.\d+(?:[-.][A-Za-z0-9.-]+)?)$/', $tag, $matches)) {
                continue;
            }

            $range = null === $previousTag ? $tag : $previousTag . '..' . $tag;
            $releases[] = [
                'version' => $matches['version'],
                'tag' => $tag,
                'date' => $date,
                'commits' => $this->collectCommitSubjects($workingDirectory, $range),
            ];

            $previousTag = $tag;
        }

        return $releases;
    }

    /**
     * Collects commit subjects for a given git range in the specified working directory.
     *
     * @param string $workingDirectory Directory in which to execute git commands (e.g., repository root).
     * @param string $range Git range to collect commits from (e.g., 'v1.0.0..v1.1.0' or 'v1.0.0').
     *
     * @return list<string> list of commit subjects for the specified range, excluding merges and ignored subjects
     */
    private function collectCommitSubjects(string $workingDirectory, string $range): array
    {
        $output = $this->gitProcessRunner->run([
            'git',
            'log',
            '--format=%s',
            '--no-merges',
            $range,
        ], $workingDirectory);

        if ('' === $output) {
            return [];
        }

        return array_values(array_filter(array_map(
            trim(...),
            explode("\n", $output),
        ), fn(string $subject): bool => ! $this->shouldIgnore($subject)));
    }

    /**
     * Determines whether a commit subject should be ignored based on common patterns (e.g., merge commits, wiki updates).
     *
     * @param string $subject commit subject to evaluate for ignoring
     *
     * @return bool True if the subject should be ignored (e.g., empty, merge commits, wiki updates); false otherwise.
     */
    private function shouldIgnore(string $subject): bool
    {
        return '' === $subject
            || str_starts_with($subject, 'Merge ')
            || 'Update wiki submodule pointer' === $subject;
    }
}
