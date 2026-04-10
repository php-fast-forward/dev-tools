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
 * Provides the canonical list of candidate paths for export-ignore rules.
 *
 * This class defines the baseline set of files and directories that should
 * typically be excluded from Composer package archives. The list is organized
 * into folders and files groups for deterministic ordering.
 */
final class CandidateProvider implements CandidateProviderInterface
{
    /**
     * @return list<string> Folders that are candidates for export-ignore
     */
    public function folders(): array
    {
        return [
            '/.github/',
            '/.idea/',
            '/.vscode/',
            '/benchmarks/',
            '/build/',
            '/coverage/',
            '/docs/',
            '/examples/',
            '/fixtures/',
            '/scripts/',
            '/tests/',
            '/tools/',
        ];
    }

    /**
     * @return list<string> Files that are candidates for export-ignore
     */
    public function files(): array
    {
        return [
            '/.editorconfig',
            '/.gitattributes',
            '/.gitignore',
            '/.gitmodules',
            '/CODE_OF_CONDUCT.md',
            '/CONTRIBUTING.md',
            '/Makefile',
            '/phpunit.xml.dist',
            '/README.md',
        ];
    }

    /**
     * Returns all candidates as a combined list with folders first, then files.
     *
     * @return list<string> All candidates in deterministic order
     */
    public function all(): array
    {
        return [...$this->folders(), ...$this->files()];
    }
}
