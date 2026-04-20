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
 */
final class SynchronizeResult
{
    /**
     * @var list<string>
     */
    private array $createdLinks = [];

    /**
     * @var list<string>
     */
    private array $preservedLinks = [];

    /**
     * @var list<string>
     */
    private array $removedBrokenLinks = [];

    private bool $failed = false;

    /**
     * @param string $link
     *
     * @return void
     */
    public function addCreatedLink(string $link): void
    {
        $this->createdLinks[] = $link;
    }

    /**
     * @param string $link
     *
     * @return void
     */
    public function addPreservedLink(string $link): void
    {
        $this->preservedLinks[] = $link;
    }

    /**
     * @param string $link
     *
     * @return void
     */
    public function addRemovedBrokenLink(string $link): void
    {
        $this->removedBrokenLinks[] = $link;
    }

    /**
     * @return void
     */
    public function markFailed(): void
    {
        $this->failed = true;
    }

    /**
     * @return list<string>
     */
    public function getCreatedLinks(): array
    {
        return $this->createdLinks;
    }

    /**
     * @return list<string>
     */
    public function getPreservedLinks(): array
    {
        return $this->preservedLinks;
    }

    /**
     * @return list<string>
     */
    public function getRemovedBrokenLinks(): array
    {
        return $this->removedBrokenLinks;
    }

    /**
     * @return bool
     */
    public function failed(): bool
    {
        return $this->failed;
    }
}
