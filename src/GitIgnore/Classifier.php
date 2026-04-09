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
 * Classifies .gitignore entries as directories or files.
 */
final class Classifier
{
    private const string DIRECTORY = 'directory';

    private const string FILE = 'file';

    /**
     * Classifies a .gitignore entry as directory or file pattern.
     *
     * @param string $entry the .gitignore entry
     *
     * @return 'directory'|'file' the classification
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

        if (1 === preg_match('/^[^.*]+[\/*]+$/', $entry)) {
            return self::DIRECTORY;
        }

        if (str_contains($entry, '*/')) {
            return self::DIRECTORY;
        }

        if (str_starts_with($entry, '**/')) {
            return self::DIRECTORY;
        }

        return self::FILE;
    }

    /**
     * Checks if an entry is a directory pattern.
     *
     * @param string $entry
     */
    public function isDirectory(string $entry): bool
    {
        return self::DIRECTORY === $this->classify($entry);
    }

    /**
     * Checks if an entry is a file pattern.
     *
     * @param string $entry
     */
    public function isFile(string $entry): bool
    {
        return self::FILE === $this->classify($entry);
    }
}
