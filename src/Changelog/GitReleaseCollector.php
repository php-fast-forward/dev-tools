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
     * @param GitProcessRunnerInterface $gitProcessRunner
     */
    public function __construct(
        private GitProcessRunnerInterface $gitProcessRunner = new GitProcessRunner()
    ) {}

    /**
     * @param string $workingDirectory
     *
     * @return list<array{version: string, tag: string, date: string, commits: list<string>}>
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
     * @param string $workingDirectory
     * @param string $range
     *
     * @return list<string>
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
     * @param string $subject
     *
     * @return bool
     */
    private function shouldIgnore(string $subject): bool
    {
        return '' === $subject
            || str_starts_with($subject, 'Merge ')
            || 'Update wiki submodule pointer' === $subject;
    }
}
