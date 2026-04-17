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
 * Reads .gitignore files and returns domain representations for them.
 *
 * This reader SHALL provide a minimal abstraction for loading a .gitignore file
 * from a filesystem path. The implementation MUST delegate file parsing to the
 * GitIgnore value object and MUST return a GitIgnoreInterface-compatible result.
 */
final class Reader implements ReaderInterface
{
    /**
     * Reads a .gitignore file from the specified filesystem path.
     *
     * The provided path MUST reference a readable .gitignore file. This method
     * SHALL delegate object creation to GitIgnore::fromFile() and MUST return
     * the resulting GitIgnoreInterface implementation.
     *
     * @param string $gitignorePath The filesystem path to the .gitignore file.
     *
     * @return GitIgnoreInterface The loaded .gitignore representation.
     */
    public function read(string $gitignorePath): GitIgnoreInterface
    {
        return GitIgnore::fromFile($gitignorePath);
    }
}
