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

/**
 * Merges export-ignore entries with existing .gitattributes content.
 *
 * This interface defines the contract for managing .gitattributes files,
 * specifically handling the merging of canonical export-ignore rules with
 * existing custom entries while preserving the managed block structure.
 */
interface MergerInterface
{
    /**
     * Reads the current .gitattributes content.
     *
     * @return string The raw file content
     */
    public function read(): string;

    /**
     * Merges the managed export-ignore entries with existing .gitattributes content.
     *
     * @param list<string> $exportIgnoreEntries The export-ignore entries to manage
     *
     * @return string The merged .gitattributes content
     */
    public function merge(array $exportIgnoreEntries): string;

    /**
     * Writes the merged content to the .gitattributes file.
     *
     * @param string $content The merged content to write
     *
     * @return void
     */
    public function write(string $content): void;
}
