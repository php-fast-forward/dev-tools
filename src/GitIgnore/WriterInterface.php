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
     * Renders the GitIgnore content without persisting it.
     *
     * @param GitIgnoreInterface $gitignore the .gitignore representation to render
     *
     * @return string the normalized .gitignore file content
     */
    public function render(GitIgnoreInterface $gitignore): string;

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
