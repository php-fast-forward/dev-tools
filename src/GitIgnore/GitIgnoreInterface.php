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

namespace FastForward\DevTools\GitIgnore;

use IteratorAggregate;

/**
 * Defines the contract for a .gitignore file with its path and entries.
 *
 * This interface MUST be implemented by any class that represents a .gitignore file.
 * It SHALL allow iteration over entries and provide access to the file path.
 *
 * @extends IteratorAggregate<int, string>
 */
interface GitIgnoreInterface extends IteratorAggregate
{
    /**
     * Returns the file system path to the .gitignore file.
     *
     * @return string the absolute path to the .gitignore file
     */
    public function path(): string;

    /**
     * Returns the list of entries from the .gitignore file.
     *
     * @return list<string> the non-empty .gitignore entries
     */
    public function entries(): array;
}
