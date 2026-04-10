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

use function Safe\file_get_contents;
use function Safe\file_put_contents;

/**
 * Reads, merges, and writes .gitattributes files with export-ignore rules.
 *
 * This class manages the .gitattributes file by preserving existing custom
 * entries while adding or updating export-ignore rules for known candidate
 * paths. It separates managed export-ignore entries from custom entries.
 */
final readonly class Merger implements MergerInterface
{
    private const string MANAGED_START = '# << dev-tools:managed export-ignore';

    private const string MANAGED_END = '# >> dev-tools:managed export-ignore';

    /**
     * @param string $path The path to the .gitattributes file
     */
    public function __construct(
        private string $path = '.gitattributes'
    ) {}

    /**
     * Reads the current .gitattributes content.
     *
     * @return string The raw file content
     */
    public function read(): string
    {
        if (! file_exists($this->path)) {
            return '';
        }

        return file_get_contents($this->path);
    }

    /**
     * Merges the managed export-ignore entries with existing .gitattributes content.
     *
     * This method:
     * 1. Extracts existing custom entries (outside the managed block)
     * 2. Adds the new export-ignore entries for existing paths
     * 3. Orders them: folders first, then files, alphabetically sorted
     * 4. Reconstructs the file with the managed block
     *
     * @param list<string> $exportIgnoreEntries The export-ignore entries to manage
     *
     * @return string The merged .gitattributes content
     */
    public function merge(array $exportIgnoreEntries): string
    {
        $existingContent = $this->read();
        $customEntries = $this->extractCustomEntries($existingContent);

        $managedContent = $this->renderManagedBlock($exportIgnoreEntries);

        $lines = [];

        if ('' !== $customEntries) {
            $lines[] = $customEntries;
            $lines[] = '';
        }

        $lines[] = self::MANAGED_START;
        $lines[] = $managedContent;
        $lines[] = self::MANAGED_END;

        return implode("\n", array_filter($lines, static fn(string $line): bool => '' !== $line));
    }

    /**
     * Writes the merged content to the .gitattributes file.
     *
     * @param string $content The merged content to write
     *
     * @return void
     */
    public function write(string $content): void
    {
        file_put_contents($this->path, $content . "\n");
    }

    /**
     * Extracts custom entries that are outside the managed block.
     *
     * @param string $content The full .gitattributes content
     *
     * @return string The custom entries (outside managed block)
     */
    private function extractCustomEntries(string $content): string
    {
        if ('' === $content) {
            return '';
        }

        $startPos = strpos($content, self::MANAGED_START);
        $endPos = strpos($content, self::MANAGED_END);

        if (false === $startPos && false === $endPos) {
            return trim($content);
        }

        if (false !== $startPos && false !== $endPos) {
            $before = substr($content, 0, $startPos);
            $after = substr($content, $endPos + \strlen(self::MANAGED_END));

            return trim($before . "\n" . $after);
        }

        if (false !== $startPos) {
            return trim(substr($content, 0, $startPos));
        }

        return trim(substr($content, $endPos + \strlen(self::MANAGED_END)));
    }

    /**
     * Renders the managed export-ignore block content.
     *
     * @param list<string> $entries The export-ignore entries to render
     *
     * @return string The rendered block content
     */
    private function renderManagedBlock(array $entries): string
    {
        if ([] === $entries) {
            return '';
        }

        $lines = [];

        foreach ($entries as $entry) {
            $lines[] = $entry . ' export-ignore';
        }

        return implode("\n", $lines);
    }
}
