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

namespace FastForward\DevTools\Command\Skills;

/**
 * Result of skill synchronization operation.
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

    public function addCreatedLink(string $link): void
    {
        $this->createdLinks[] = $link;
    }

    public function addPreservedLink(string $link): void
    {
        $this->preservedLinks[] = $link;
    }

    public function addRemovedBrokenLink(string $link): void
    {
        $this->removedBrokenLinks[] = $link;
    }

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

    public function failed(): bool
    {
        return $this->failed;
    }
}
