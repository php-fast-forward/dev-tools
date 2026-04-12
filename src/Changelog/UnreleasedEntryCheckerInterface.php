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

namespace FastForward\DevTools\Changelog;

/**
 * Verifies that the changelog contains meaningful unreleased changes.
 *
 * This is used to prevent merging changes that have not been documented in the changelog.
 * It compares the unreleased entries in the changelog against the current branch or a specified reference (e.g., a base branch or commit hash).
 */
interface UnreleasedEntryCheckerInterface
{
    /**
     * Checks if there are pending unreleased entries in the changelog compared to a given reference.
     *
     * This method MUST read the unreleased section of the changelog and compare it against the changes in the current branch or a specified reference.
     * If there are entries in the unreleased section that are not present in the reference, it indicates that there are pending changes that have not been released yet.
     * The method MUST return true if there are pending unreleased entries, and false otherwise.
     *
     * @param string $workingDirectory the working directory of the repository
     * @param string|null $againstReference The reference to compare against (e.g., a branch or commit hash).
     *
     * @return bool true if there are pending unreleased entries, false otherwise
     */
    public function hasPendingChanges(string $workingDirectory, ?string $againstReference = null): bool;
}
