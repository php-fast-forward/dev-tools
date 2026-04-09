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

namespace FastForward\DevTools\GitIgnore;

use function Safe\preg_match;

/**
 * Classifies .gitignore entries as directory-oriented or file-oriented patterns.
 *
 * This classifier SHALL inspect a raw .gitignore entry and determine whether the
 * entry expresses directory semantics or file semantics. Implementations MUST
 * preserve deterministic classification for identical inputs. Blank entries and
 * comment entries MUST be treated as file-oriented values to avoid incorrectly
 * inferring directory intent where no effective pattern exists.
 */
final class Classifier implements ClassifierInterface
{
    /**
     * Represents a classification result indicating directory semantics.
     *
     * This constant MUST be returned when an entry clearly targets a directory,
     * such as entries ending with a slash or patterns that imply directory
     * traversal.
     */
    private const string DIRECTORY = 'directory';

    /**
     * Represents a classification result indicating file semantics.
     *
     * This constant MUST be returned when an entry does not clearly express
     * directory semantics, including blank values and comment lines.
     */
    private const string FILE = 'file';

    /**
     * Classifies a .gitignore entry as either a directory or a file pattern.
     *
     * The provided entry SHALL be normalized with trim() before any rule is
     * evaluated. Empty entries and comment entries MUST be classified as files.
     * Entries ending with "/" MUST be classified as directories. Patterns that
     * indicate directory traversal or wildcard directory matching SHOULD also be
     * classified as directories.
     *
     * @param string $entry The raw .gitignore entry to classify.
     *
     * @return string The classification result. The value MUST be either
     *                self::DIRECTORY or self::FILE.
     */
    public function classify(string $entry): string
    {
        $entry = trim($entry);

        if ('' === $entry) {
            return self::FILE;
        }

        if (str_starts_with($entry, '#')) {
            return self::FILE;
        }

        if (str_ends_with($entry, '/')) {
            return self::DIRECTORY;
        }

        if (1 === preg_match('/^[^.*]+[\/*]+/', $entry)) {
            return self::DIRECTORY;
        }

        if (str_starts_with($entry, '**/')) {
            return self::DIRECTORY;
        }

        if (str_contains($entry, '*/')) {
            return self::DIRECTORY;
        }

        return self::FILE;
    }

    /**
     * Determines whether the given .gitignore entry represents a directory pattern.
     *
     * This method MUST delegate the effective classification to classify() and
     * SHALL return true only when the resulting classification is
     * self::DIRECTORY.
     *
     * @param string $entry The raw .gitignore entry to evaluate.
     *
     * @return bool true when the entry is classified as a directory pattern;
     *              otherwise, false
     */
    public function isDirectory(string $entry): bool
    {
        return self::DIRECTORY === $this->classify($entry);
    }

    /**
     * Determines whether the given .gitignore entry represents a file pattern.
     *
     * This method MUST delegate the effective classification to classify() and
     * SHALL return true only when the resulting classification is self::FILE.
     *
     * @param string $entry The raw .gitignore entry to evaluate.
     *
     * @return bool true when the entry is classified as a file pattern;
     *              otherwise, false
     */
    public function isFile(string $entry): bool
    {
        return self::FILE === $this->classify($entry);
    }
}
