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

namespace FastForward\DevTools\GitAttributes;

/**
 * Defines the contract for writing .gitattributes files to persistent storage.
 *
 * Implementations MUST write the provided content to the target path, SHOULD
 * normalize attribute-column alignment for deterministic output, and SHALL
 * ensure the resulting file ends with a trailing line feed.
 */
interface WriterInterface
{
    /**
     * Writes the .gitattributes content to the specified filesystem path.
     *
     * @param string $gitattributesPath The filesystem path to the .gitattributes file.
     * @param string $content The merged .gitattributes content to persist.
     *
     * @return void
     */
    public function write(string $gitattributesPath, string $content): void;
}
