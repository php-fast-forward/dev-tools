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

use function array_reverse;
use function implode;

/**
 * Renders Keep a Changelog markdown in a deterministic package-friendly format.
 */
final readonly class MarkdownRenderer
{
    /**
     * @var list<string>
     */
    private const array SECTION_ORDER = ['Added', 'Changed', 'Deprecated', 'Removed', 'Fixed', 'Security'];

    /**
     * Renders the changelog markdown content.
     *
     * @param list<array{version: string, date: string, entries: array<string, list<string>>}> $releases list of releases with their version, date, and entries
     *
     * @return string the generated changelog markdown content
     */
    public function render(array $releases): string
    {
        $lines = [
            '# Changelog',
            '',
            'All notable changes to this project will be documented in this file, in reverse chronological order by release.',
            '',
        ];

        $lines = [...$lines, ...$this->renderSection('Unreleased', 'TBD', [])];

        foreach (array_reverse($releases) as $release) {
            $lines = [
                ...$lines,
                ...$this->renderSection($release['version'], $release['date'], $release['entries']),
            ];
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Renders a section of the changelog for a specific release.
     *
     * @param string $version the version of the release
     * @param string $date the release date
     * @param array<string, list<string>> $entries the entries for the release, categorized by section
     *
     * @return list<string> the rendered lines for the release section
     */
    private function renderSection(string $version, string $date, array $entries): array
    {
        $lines = ['## ' . $version . ' - ' . $date, ''];
        $hasEntries = false;

        foreach (self::SECTION_ORDER as $section) {
            $sectionEntries = $entries[$section] ?? [];

            if ('Unreleased' !== $version && [] === $sectionEntries) {
                continue;
            }

            $hasEntries = true;
            $lines[] = '### ' . $section;
            $lines[] = '';

            if ([] === $sectionEntries) {
                $lines[] = '- Nothing.';
                $lines[] = '';
                continue;
            }

            foreach ($sectionEntries as $entry) {
                $lines[] = '- ' . $entry;
            }

            $lines[] = '';
        }

        if (! $hasEntries) {
            $lines[] = '### Changed';
            $lines[] = '';
            $lines[] = '- Nothing.';
            $lines[] = '';
        }

        return $lines;
    }
}
