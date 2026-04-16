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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\GitIgnore;

/**
 * Defines the contract for classifying .gitignore entries.
 *
 * This classifier SHALL inspect a raw .gitignore entry and determine whether the
 * entry expresses directory semantics or file semantics. Implementations MUST
 * preserve deterministic classification for identical inputs. Blank entries and
 * comment entries MUST be treated as file-oriented values to avoid incorrectly
 * inferring directory intent where no effective pattern exists.
 */
interface ClassifierInterface
{
    /**
     * Classifies a .gitignore entry as directory or file pattern.
     *
     * @param string $entry the .gitignore entry to classify
     *
     * @return 'directory'|'file' the classification result
     */
    public function classify(string $entry): string;

    /**
     * Determines whether the entry represents a directory pattern.
     *
     * @param string $entry the .gitignore entry to check
     *
     * @return bool true if the entry is a directory pattern
     */
    public function isDirectory(string $entry): bool;

    /**
     * Determines whether the entry represents a file pattern.
     *
     * @param string $entry the .gitignore entry to check
     *
     * @return bool true if the entry is a file pattern
     */
    public function isFile(string $entry): bool;
}
