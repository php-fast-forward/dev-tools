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

/**
 * Defines the contract for writing .gitignore representations to persistent storage.
 *
 * Implementations MUST persist the entries exposed by a GitIgnoreInterface
 * instance to its associated path. Implementations SHALL preserve the semantic
 * ordering of entries provided by the input object and SHOULD write content in a
 * format compatible with standard .gitignore files.
 */
interface WriterInterface
{
    /**
     * Writes the GitIgnore content to its associated filesystem path.
     *
     * The provided GitIgnoreInterface instance MUST contain the target path and
     * the entries to be written. Implementations SHALL persist that content to
     * the associated location represented by the given object.
     *
     * @param GitIgnoreInterface $gitignore The .gitignore representation to
     *                                      write to persistent storage.
     *
     * @return void
     */
    public function write(GitIgnoreInterface $gitignore): void;
}
