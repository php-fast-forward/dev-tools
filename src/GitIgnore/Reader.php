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
 * Loads canonical .gitignore entries from the dev-tools package.
 */
final class Reader
{
    /**
     * Reads the canonical .gitignore entries from dev-tools package.
     *
     * @param string $packagePath the path to the dev-tools package
     *
     * @return array<int, string> the lines from .gitignore
     */
    public static function readFromPackage(string $packagePath): array
    {
        $gitignorePath = $packagePath . '/.gitignore';

        if (! file_exists($gitignorePath)) {
            return [];
        }

        $content = file_get_contents($gitignorePath);
        $lines = explode("\n", $content);

        return array_values(array_filter($lines, static fn(string $line): bool => '' !== trim($line)));
    }

    /**
     * Reads the target project's .gitignore entries.
     *
     * @param string $projectPath the path to the target project
     *
     * @return array<int, string> the lines from .gitignore
     */
    public static function readFromProject(string $projectPath): array
    {
        $gitignorePath = $projectPath . '/.gitignore';

        if (! file_exists($gitignorePath)) {
            return [];
        }

        $content = file_get_contents($gitignorePath);
        $lines = explode("\n", $content);

        return array_values(array_filter($lines, static fn(string $line): bool => '' !== trim($line)));
    }
}
