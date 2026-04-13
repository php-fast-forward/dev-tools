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
use function array_reverse;
use function array_values;
use function explode;
use function implode;
use function rtrim;
use function str_ends_with;
use function substr;
use function trim;

/**
 * Renders Keep a Changelog markdown in a deterministic package-friendly format.
 */
final readonly class MarkdownRenderer
{
    /**
     * @var list<string>
     */
    private const array SECTION_ORDER = ['Added', 'Changed', 'Deprecated', 'Removed', 'Fixed', 'Security'];

    private const string INTRODUCTION = "# Changelog\n\nAll notable changes to this project will be documented in this file.\n\nThe format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),\nand this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).";

    /**
     * Renders the changelog markdown content.
     *
     * @param list<array{version: string, date: string, entries: array<string, list<string>>, tag?: string}> $releases list of releases with their version, date, entries, and optional tag
     * @param string|null $repositoryUrl repository URL used to build footer references
     *
     * @return string the generated changelog markdown content
     */
    public function render(array $releases, ?string $repositoryUrl = null): string
    {
        $orderedReleases = array_values(array_reverse($releases));
        $lines = explode("\n", self::INTRODUCTION);

        $lines = [...$lines, '', '## [Unreleased]', ''];

        foreach ($orderedReleases as $release) {
            $lines = [
                ...$lines,
                ...$this->renderSection($release['version'], $release['date'], $release['entries']),
            ];
        }

        $references = $this->renderReferences($orderedReleases, $repositoryUrl);

        if ([] !== $references) {
            $lines = [...$lines, ...$references];
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
        $lines = ['## [' . $version . '] - ' . $date, ''];

        foreach (self::SECTION_ORDER as $section) {
            $sectionEntries = $entries[$section] ?? [];

            if ([] === $sectionEntries) {
                continue;
            }

            $lines[] = '### ' . $section;
            $lines[] = '';

            foreach ($sectionEntries as $entry) {
                $lines[] = '- ' . $entry;
            }

            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @param list<array{version: string, date: string, entries: array<string, list<string>>, tag?: string}> $orderedReleases
     * @param ?string $repositoryUrl
     *
     * @return list<string>
     */
    private function renderReferences(array $orderedReleases, ?string $repositoryUrl): array
    {
        $normalizedRepositoryUrl = $this->normalizeRepositoryUrl($repositoryUrl);

        if (null === $normalizedRepositoryUrl || [] === $orderedReleases) {
            return [];
        }

        $references = [
            \sprintf(
                '[unreleased]: %s/compare/%s...HEAD',
                $normalizedRepositoryUrl,
                $this->resolveTag($orderedReleases[0]),
            ),
        ];

        foreach ($orderedReleases as $index => $release) {
            $references[] = isset($orderedReleases[$index + 1])
                ? \sprintf(
                    '[%s]: %s/compare/%s...%s',
                    $release['version'],
                    $normalizedRepositoryUrl,
                    $this->resolveTag($orderedReleases[$index + 1]),
                    $this->resolveTag($release),
                )
                : \sprintf(
                    '[%s]: %s/releases/tag/%s',
                    $release['version'],
                    $normalizedRepositoryUrl,
                    $this->resolveTag($release),
                );
        }

        return ['', ...$references];
    }

    /**
     * @param array{version: string, date: string, entries: array<string, list<string>>, tag?: string} $release
     *
     * @return string
     */
    private function resolveTag(array $release): string
    {
        return $release['tag'] ?? 'v' . $release['version'];
    }

    /**
     * @param string|null $repositoryUrl
     *
     * @return string|null
     */
    private function normalizeRepositoryUrl(?string $repositoryUrl): ?string
    {
        if (null === $repositoryUrl) {
            return null;
        }

        $repositoryUrl = trim($repositoryUrl);

        if ('' === $repositoryUrl) {
            return null;
        }

        if (1 === preg_match('~^git@(?<host>[^:]+):(?<path>.+)$~', $repositoryUrl, $matches)) {
            $repositoryUrl = 'https://' . $matches['host'] . '/' . $matches['path'];
        }

        if (1 === preg_match('~^ssh://git@(?<host>[^/]+)/(?<path>.+)$~', $repositoryUrl, $matches)) {
            $repositoryUrl = 'https://' . $matches['host'] . '/' . $matches['path'];
        }

        if (str_ends_with($repositoryUrl, '.git')) {
            $repositoryUrl = substr($repositoryUrl, 0, -4);
        }

        return rtrim($repositoryUrl, '/');
    }
}
