<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\GitAttributes;

use function Safe\preg_split;
use function Safe\preg_replace;
use function Safe\preg_match;

/**
 * Merges .gitattributes content with generated export-ignore rules.
 *
 * This class preserves existing custom entries while adding missing
 * export-ignore defaults before consumer rules. Existing rules MUST retain their
 * order and repetitions so later unset and unspecified attributes keep precedence.
 */
final class Merger implements MergerInterface
{
    /**
     * Merges generated export-ignore entries with existing .gitattributes content.
     *
     * This method:
     * 1. Preserves custom user-defined entries in their original order
     * 2. Prepends missing generated defaults before consumer overrides
     * 3. Omits generated duplicates without deduplicating existing rules
     * 4. Removes simple positive rules for explicitly kept paths
     *
     * @param string $existingContent The raw .gitattributes content currently stored.
     * @param list<string> $exportIgnoreEntries the export-ignore entries to manage
     * @param list<string> $keepInExportPaths the paths that MUST remain exported
     *
     * @return string The merged .gitattributes content
     */
    public function merge(string $existingContent, array $exportIgnoreEntries, array $keepInExportPaths = []): string
    {
        $existingLines = [];
        $existingRules = [];
        $keptExportLookup = $this->keepInExportLookup($keepInExportPaths);

        foreach ($this->parseExistingLines($existingContent) as $line) {
            $pathSpec = $this->extractExportIgnorePathSpec($line);

            if (null !== $pathSpec) {
                $pathKey = $this->normalizePathKey($pathSpec);

                if ($this->isKeptPath($pathKey, $keptExportLookup)) {
                    continue;
                }

                $existingRules[$pathKey] = true;
            }

            $existingLines[] = $line;
        }

        $generatedLines = [];

        foreach ($exportIgnoreEntries as $entry) {
            $entry = trim($entry);
            $pathKey = $this->normalizePathKey($entry);

            if ('' === $entry || $this->isKeptPath($pathKey, $keptExportLookup) || isset($existingRules[$pathKey])) {
                continue;
            }

            $generatedLines[] = $entry . ' export-ignore';
            $existingRules[$pathKey] = true;
        }

        return implode("\n", [...$generatedLines, ...$existingLines]);
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

        foreach (preg_split('/\R/', $content) as $line) {
            $trimmedLine = trim((string) $line);

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
     * @param string $pathKey
     * @param array<string, true> $keptExportLookup
     */
    private function isKeptPath(string $pathKey, array $keptExportLookup): bool
    {
        foreach (array_keys($keptExportLookup) as $keptPath) {
            if ($pathKey === $keptPath || str_starts_with($pathKey . '/', $keptPath . '/')) {
                return true;
            }
        }

        return false;
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

        return trim($pathSpec);
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
