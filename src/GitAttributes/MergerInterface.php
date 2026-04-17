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

/**
 * Merges export-ignore entries with existing .gitattributes content.
 *
 * This interface defines the contract for managing .gitattributes files,
 * specifically handling the merging of canonical export-ignore rules with
 * existing custom entries while removing obsolete generated markers and
 * duplicate lines.
 */
interface MergerInterface
{
    /**
     * Merges generated export-ignore entries with existing .gitattributes content.
     *
     * @param string $existingContent The current .gitattributes content.
     * @param list<string> $exportIgnoreEntries The export-ignore entries to manage
     * @param list<string> $keepInExportPaths The paths that MUST remain exported
     *
     * @return string The merged .gitattributes content
     */
    public function merge(string $existingContent, array $exportIgnoreEntries, array $keepInExportPaths = []): string;
}
