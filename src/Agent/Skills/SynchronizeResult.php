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

namespace FastForward\DevTools\Agent\Skills;

/**
 * Result object for skill synchronization operations.
 *
 * Tracks the outcomes of a synchronization run, including newly created links,
 * existing items that were preserved, and broken links that were removed.
 * The failed flag indicates whether an error occurred during synchronization.
 */
final class SynchronizeResult
{
    /**
     * List of skill names for which new symlinks were created.
     *
     * @var list<string>
     */
    private array $createdLinks = [];

    /**
     * List of skill names for which existing items were left unchanged.
     *
     * @var list<string>
     */
    private array $preservedLinks = [];

    /**
     * List of skill names whose broken symlinks were removed during sync.
     *
     * @var list<string>
     */
    private array $removedBrokenLinks = [];

    private bool $failed = false;

    /**
     * Records a skill for which a new symlink was created.
     *
     * @param string $link Name of the skill that received a new symlink
     */
    public function addCreatedLink(string $link): void
    {
        $this->createdLinks[] = $link;
    }

    /**
     * Records a skill whose existing item was preserved unchanged.
     *
     * @param string $link Name of the skill that was left in place
     */
    public function addPreservedLink(string $link): void
    {
        $this->preservedLinks[] = $link;
    }

    /**
     * Records a skill whose broken symlink was removed during sync.
     *
     * @param string $link Name of the skill whose broken link was removed
     */
    public function addRemovedBrokenLink(string $link): void
    {
        $this->removedBrokenLinks[] = $link;
    }

    /**
     * Marks the synchronization as failed due to an error condition.
     */
    public function markFailed(): void
    {
        $this->failed = true;
    }

    /**
     * Returns the list of skills for which new symlinks were created.
     *
     * @return list<string> Skill names of newly created links
     */
    public function getCreatedLinks(): array
    {
        return $this->createdLinks;
    }

    /**
     * Returns the list of skills whose existing items were preserved.
     *
     * @return list<string> Skill names of preserved items
     */
    public function getPreservedLinks(): array
    {
        return $this->preservedLinks;
    }

    /**
     * Returns the list of skills whose broken symlinks were removed.
     *
     * @return list<string> Skill names of removed broken links
     */
    public function getRemovedBrokenLinks(): array
    {
        return $this->removedBrokenLinks;
    }

    /**
     * Indicates whether the synchronization encountered a failure.
     *
     * @return bool True if an error occurred, false otherwise
     */
    public function failed(): bool
    {
        return $this->failed;
    }
}
