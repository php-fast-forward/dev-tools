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
