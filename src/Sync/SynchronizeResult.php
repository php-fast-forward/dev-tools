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

namespace FastForward\DevTools\Sync;

/**
 * Result object for packaged directory synchronization operations.
 *
 * This value object tracks the outcome of a synchronization run by recording
 * which links were created, which existing links or directories were
 * preserved, and which broken links were removed before recreation. It also
 * carries a failure flag so callers can distinguish successful runs from
 * aborted or invalid synchronization attempts.
 */
final class SynchronizeResult
{
    /**
     * Stores the entry names for symlinks created during synchronization.
     *
     * @var list<string>
     */
    private array $createdLinks = [];

    /**
     * Stores the entry names that were already valid and therefore preserved.
     *
     * @var list<string>
     */
    private array $preservedLinks = [];

    /**
     * Stores the entry names for broken links removed during repair.
     *
     * @var list<string>
     */
    private array $removedBrokenLinks = [];

    /**
     * Indicates whether the synchronization process encountered a fatal failure.
     */
    private bool $failed = false;

    /**
     * Records the name of a link that was newly created.
     *
     * @param string $link Entry name identifying the created link
     *
     * @return void
     */
    public function addCreatedLink(string $link): void
    {
        $this->createdLinks[] = $link;
    }

    /**
     * Records the name of an entry that was preserved as-is.
     *
     * @param string $link Entry name identifying the preserved link or directory
     *
     * @return void
     */
    public function addPreservedLink(string $link): void
    {
        $this->preservedLinks[] = $link;
    }

    /**
     * Records the name of a broken link that was removed before recreation.
     *
     * @param string $link Entry name identifying the repaired broken link
     *
     * @return void
     */
    public function addRemovedBrokenLink(string $link): void
    {
        $this->removedBrokenLinks[] = $link;
    }

    /**
     * Marks the synchronization as failed.
     *
     * Callers SHOULD use this when a precondition or runtime error prevents
     * the synchronization result from being considered successful.
     *
     * @return void
     */
    public function markFailed(): void
    {
        $this->failed = true;
    }

    /**
     * Returns the names of links created during synchronization.
     *
     * @return list<string>
     */
    public function getCreatedLinks(): array
    {
        return $this->createdLinks;
    }

    /**
     * Returns the names of links or directories preserved during synchronization.
     *
     * @return list<string>
     */
    public function getPreservedLinks(): array
    {
        return $this->preservedLinks;
    }

    /**
     * Returns the names of broken links removed during synchronization repair.
     *
     * @return list<string>
     */
    public function getRemovedBrokenLinks(): array
    {
        return $this->removedBrokenLinks;
    }

    /**
     * Indicates whether the synchronization result represents a failed run.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->failed;
    }
}
