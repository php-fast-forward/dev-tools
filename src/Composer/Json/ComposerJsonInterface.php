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

namespace FastForward\DevTools\Composer\Json;

/**
 * Represents a specialized reader for a Composer JSON file.
 *
 * This interface provides convenient accessors for commonly used
 * `composer.json` metadata.
 */
interface ComposerJsonInterface
{
    /**
     * Returns the package name declared in the Composer file.
     *
     * @return string the package name, or an empty string when undefined
     */
    public function getPackageName(): string;

    /**
     * Returns the package description declared in the Composer file.
     *
     * @return string the package description, or an empty string when
     *                undefined
     */
    public function getPackageDescription(): string;

    /**
     * Returns the package license when it can be resolved to a single value.
     *
     * @return string|null the resolved license identifier, or null when
     *                     no single license value can be determined
     */
    public function getPackageLicense(): ?string;

    /**
     * Returns the package authors declared in the Composer file.
     *
     * @return array the authors list as declared in the Composer file,
     *               or an empty array when undefined
     */
    public function getAuthors(): array;

    /**
     * Returns the extra configuration section declared in the Composer file.
     *
     * @return array the extra configuration data, or an empty array when
     *               undefined
     */
    public function getExtra(): array;

    /**
     * Returns the autoload configuration for the requested autoload type.
     *
     * @param string $type The autoload mapping type to retrieve. This
     *                     defaults to `psr-4`.
     *
     * @return array the autoload configuration for the requested type,
     *               or an empty array when unavailable
     */
    public function getAutoload(string $type = 'psr-4'): array;
}
