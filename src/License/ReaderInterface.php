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

namespace FastForward\DevTools\License;

interface ReaderInterface
{
    /**
     * @return string|null
     */
    public function getLicense(): ?string;

    /**
     * @return string
     */
    public function getPackageName(): string;

    /**
     * @return array<int, array{name: string, email: string, homepage: string, role: string}>
     */
    public function getAuthors(): array;

    /**
     * @return string|null
     */
    public function getVendor(): ?string;

    /**
     * @return int
     */
    public function getYear(): int;
}
