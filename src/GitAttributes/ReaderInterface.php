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
 * Defines the contract for reading .gitattributes files from persistent storage.
 *
 * Implementations MUST load the raw textual content from the provided path and
 * SHALL return an empty string when the target file does not exist.
 */
interface ReaderInterface
{
    /**
     * Reads the .gitattributes content from the specified filesystem path.
     *
     * @param string $gitattributesPath The filesystem path to the .gitattributes file.
     *
     * @return string The raw .gitattributes content, or an empty string when absent.
     */
    public function read(string $gitattributesPath): string;
}
