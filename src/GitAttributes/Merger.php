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

namespace FastForward\DevTools\GitAttributes;

use function Safe\preg_split;
use function Safe\preg_replace;
use function Safe\preg_match;

/**
 * Merges .gitattributes content with generated export-ignore rules.
 *
 * This class preserves existing custom entries while adding missing
 * export-ignore rules for known candidate paths, deduplicates semantically
 * equivalent entries, and sorts export-ignore rules with directories before
 * files.
 */
final class Merger implements MergerInterface
{
    /**
     * Merges generated export-ignore entries with existing .gitattributes content.
     *
     * This method:
     * 1. Preserves custom user-defined entries in their original order
     * 2. Adds missing generated export-ignore entries for existing paths
     * 3. Deduplicates entries using normalized path comparison
     * 4. Sorts export-ignore entries with directories before files
     *
     * @param string $existingContent The raw .gitattributes content currently stored.
     * @param list<string> $exportIgnoreEntries the export-ignore entries to manage
     * @param list<string> $keepInExportPaths the paths that MUST remain exported
     *
     * @return string The merged .gitattributes content
     */
    public function merge(string $existingContent, array $exportIgnoreEntries, array $keepInExportPaths = []): string
    {
        $nonExportIgnoreLines = [];
        $seenNonExportIgnoreLines = [];
        $exportIgnoreLines = [];
        $keptExportLookup = $this->keepInExportLookup($keepInExportPaths);
        $generatedDirectoryLookup = $this->generatedDirectoryLookup($exportIgnoreEntries);

        foreach ($this->parseExistingLines($existingContent) as $line) {
            $normalizedLine = $this->normalizeLine($line);

            if ('' === $normalizedLine) {
                continue;
            }

            $pathSpec = $this->extractExportIgnorePathSpec($normalizedLine);

            if (null === $pathSpec) {
                if (isset($seenNonExportIgnoreLines[$normalizedLine])) {
                    continue;
                }

                $nonExportIgnoreLines[] = $normalizedLine;
                $seenNonExportIgnoreLines[$normalizedLine] = true;

                continue;
            }

            $pathKey = $this->normalizePathKey($pathSpec);
            if (isset($keptExportLookup[$pathKey])) {
                continue;
            }
            if (isset($exportIgnoreLines[$pathKey])) {
                continue;
            }

            $exportIgnoreLines[$pathKey] = [
                'line' => $normalizedLine,
                'sort_key' => $this->sortKey($pathSpec),
                'is_directory' => str_ends_with($pathSpec, '/') || isset($generatedDirectoryLookup[$pathKey]),
            ];
        }

        foreach ($exportIgnoreEntries as $entry) {
            $trimmedEntry = trim($entry);
            $pathKey = $this->normalizePathKey($trimmedEntry);

            if (isset($keptExportLookup[$pathKey])) {
                continue;
            }

            if (! isset($exportIgnoreLines[$pathKey])) {
                $exportIgnoreLines[$pathKey] = [
                    'line' => $trimmedEntry . ' export-ignore',
                    'sort_key' => $this->sortKey($trimmedEntry),
                    'is_directory' => str_ends_with($trimmedEntry, '/'),
                ];

                continue;
            }

            $exportIgnoreLines[$pathKey]['is_directory'] = $exportIgnoreLines[$pathKey]['is_directory']
                || str_ends_with($trimmedEntry, '/');
        }

        $sortedExportIgnoreLines = array_values($exportIgnoreLines);

        usort(
            $sortedExportIgnoreLines,
            static function (array $left, array $right): int {
                if ($left['is_directory'] !== $right['is_directory']) {
                    return $left['is_directory'] ? -1 : 1;
                }

                $naturalOrder = strnatcasecmp($left['sort_key'], $right['sort_key']);

                if (0 !== $naturalOrder) {
                    return $naturalOrder;
                }

                return strcmp($left['sort_key'], $right['sort_key']);
            }
        );

        return implode("\n", [...$nonExportIgnoreLines, ...array_column($sortedExportIgnoreLines, 'line')]);
    }

    /**
     * Parses the raw .gitattributes content into trimmed non-empty lines.
     *
     * @param string $content The full .gitattributes content.
     *
     * @return list<string> the non-empty lines from the file
     */
    private function parseExistingLines(string $content): array
    {
        if ('' === $content) {
            return [];
        }

        $lines = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $trimmedLine = trim($line);

            if ('' === $trimmedLine) {
                continue;
            }

            $lines[] = $trimmedLine;
        }

        return $lines;
    }

    /**
     * Builds a lookup table for paths that MUST stay in the exported archive.
     *
     * @param list<string> $keepInExportPaths the configured keep-in-export paths
     *
     * @return array<string, true> the normalized path lookup
     */
    private function keepInExportLookup(array $keepInExportPaths): array
    {
        $lookup = [];

        foreach ($keepInExportPaths as $path) {
            $normalizedPath = $this->normalizePathKey($path);

            if ('' === $normalizedPath) {
                continue;
            }

            $lookup[$normalizedPath] = true;
        }

        return $lookup;
    }

    /**
     * Builds a lookup table of generated directory candidates.
     *
     * @param list<string> $exportIgnoreEntries the generated export-ignore path list
     *
     * @return array<string, true> the normalized directory lookup
     */
    private function generatedDirectoryLookup(array $exportIgnoreEntries): array
    {
        $lookup = [];

        foreach ($exportIgnoreEntries as $entry) {
            $trimmedEntry = trim($entry);

            if (! str_ends_with($trimmedEntry, '/')) {
                continue;
            }

            $lookup[$this->normalizePathKey($trimmedEntry)] = true;
        }

        return $lookup;
    }

    /**
     * Normalizes a .gitattributes line for deterministic comparison and output.
     *
     * @param string $line the raw line to normalize
     *
     * @return string the normalized line
     */
    private function normalizeLine(string $line): string
    {
        $trimmedLine = trim($line);

        if ('' === $trimmedLine) {
            return '';
        }

        if (str_starts_with($trimmedLine, '#')) {
            return $trimmedLine;
        }

        return preg_replace('/(?<!\\\\)[ \t]+/', ' ', $trimmedLine) ?? $trimmedLine;
    }

    /**
     * Extracts the path spec from a simple export-ignore line.
     *
     * @param string $line the line to inspect
     *
     * @return string|null the extracted path spec when the line is a simple export-ignore rule
     */
    private function extractExportIgnorePathSpec(string $line): ?string
    {
        if (1 !== preg_match('/^(\S+)\s+export-ignore$/', $line, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Builds the natural sort key for a path spec.
     *
     * @param string $pathSpec the raw path spec to normalize for sorting
     *
     * @return string the natural sort key
     */
    private function sortKey(string $pathSpec): string
    {
        return ltrim($this->normalizePathSpec($pathSpec), '/');
    }

    /**
     * Normalizes a gitattributes path spec for sorting.
     *
     * @param string $pathSpec the raw path spec to normalize
     *
     * @return string the normalized path spec
     */
    private function normalizePathSpec(string $pathSpec): string
    {
        $trimmedPathSpec = trim($pathSpec);

        if ('' === $trimmedPathSpec) {
            return '';
        }

        $isDirectory = str_ends_with($trimmedPathSpec, '/');
        $normalizedPathSpec = preg_replace('#/+#', '/', '/' . ltrim($trimmedPathSpec, '/')) ?? $trimmedPathSpec;
        $normalizedPathSpec = '/' === $normalizedPathSpec ? $normalizedPathSpec : rtrim($normalizedPathSpec, '/');

        if ($isDirectory && '/' !== $normalizedPathSpec) {
            $normalizedPathSpec .= '/';
        }

        return $normalizedPathSpec;
    }

    /**
     * Normalizes a path spec for deduplication and keep-in-export matching.
     *
     * Literal root paths are compared without leading slash differences, while
     * pattern-based specs preserve their original anchoring semantics.
     *
     * @param string $pathSpec the raw path spec to normalize
     *
     * @return string the normalized deduplication key
     */
    private function normalizePathKey(string $pathSpec): string
    {
        $normalizedPathSpec = $this->normalizePathSpec($pathSpec);

        if ($this->isLiteralPathSpec($normalizedPathSpec)) {
            return ltrim(rtrim($normalizedPathSpec, '/'), '/');
        }

        return $normalizedPathSpec;
    }

    /**
     * Determines whether a path spec is a literal path and not a glob pattern.
     *
     * @param string $pathSpec the normalized path spec to inspect
     *
     * @return bool true when the path spec is a literal path
     */
    private function isLiteralPathSpec(string $pathSpec): bool
    {
        return ! str_contains($pathSpec, '*')
            && ! str_contains($pathSpec, '?')
            && ! str_contains($pathSpec, '[')
            && ! str_contains($pathSpec, '{');
    }
}
