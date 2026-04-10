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

/**
 * Reads and exposes metadata from composer.json for license generation.
 *
 * This interface provides access to license information, package name,
 * authors, vendor, and year data extracted from a project's composer.json.
 */
interface ReaderInterface
{
    /**
     * Retrieves the license identifier from composer.json.
     *
     * @return string|null The license string, or null if not set or unsupported.
     */
    public function getLicense(): ?string;

    /**
     * Retrieves the package name from composer.json.
     *
     * @return string The full package name (vendor/package).
     */
    public function getPackageName(): string;

    /**
     * Retrieves the list of authors from composer.json.
     *
     * @return array<int, array{name: string, email: string, homepage: string, role: string}>
     */
    public function getAuthors(): array;

    /**
     * Extracts the vendor name from the package name.
     *
     * @return string|null The vendor name, or null if package has no vendor prefix.
     */
    public function getVendor(): ?string;

    /**
     * Returns the current year for copyright notices.
     *
     * @return int The current year as an integer.
     */
    public function getYear(): int;
}
