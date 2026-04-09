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

use Symfony\Component\Filesystem\Filesystem;

/**
 * Renders and writes the normalized .gitignore file.
 */
final readonly class Writer
{
    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        private Filesystem $filesystem
    ) {}

    /**
     * Writes the normalized .gitignore entries to the target file.
     *
     * @param array<int, string> $entries the sorted .gitignore entries
     * @param string $targetPath the target .gitignore file path
     */
    public function write(array $entries, string $targetPath): void
    {
        $content = implode("\n", $entries) . "\n";

        $this->filesystem->dumpFile($targetPath, $content);
    }
}
