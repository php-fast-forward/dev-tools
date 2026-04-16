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
 * Defines the contract for reading .gitignore files from a storage location.
 *
 * Implementations MUST load a .gitignore resource from the provided filesystem
 * path and SHALL return a GitIgnoreInterface representation of its contents.
 * Implementations SHOULD delegate parsing and normalization responsibilities to
 * a dedicated domain object or parser when appropriate.
 */
interface ReaderInterface
{
    /**
     * Reads a .gitignore file from the specified filesystem path.
     *
     * The provided path MUST identify a readable .gitignore file. Implementations
     * SHALL return a GitIgnoreInterface instance representing the contents read
     * from that path.
     *
     * @param string $gitignorePath The filesystem path to the .gitignore file.
     *
     * @return GitIgnoreInterface The loaded .gitignore representation.
     */
    public function read(string $gitignorePath): GitIgnoreInterface;
}
