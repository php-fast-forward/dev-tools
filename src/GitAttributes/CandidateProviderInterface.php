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

namespace FastForward\DevTools\GitAttributes;

/**
 * Provides the canonical list of candidate paths for export-ignore rules.
 *
 * This interface defines the contract for classes that provide the baseline
 * set of files and directories that should typically be excluded from
 * Composer package archives.
 */
interface CandidateProviderInterface
{
    /**
     * Returns the list of folder paths that are candidates for export-ignore.
     *
     * @return list<string> Folder paths in canonical form (e.g., "/.github/")
     */
    public function folders(): array;

    /**
     * Returns the list of file paths that are candidates for export-ignore.
     *
     * @return list<string> File paths in canonical form (e.g., "/.editorconfig")
     */
    public function files(): array;

    /**
     * Returns all candidates as a combined list with folders first, then files.
     *
     * @return list<string> All candidates in deterministic order
     */
    public function all(): array;
}
