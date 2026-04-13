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

use Composer\Factory;
use Composer\Json\JsonFile;

/**
 * Represents a specialized reader for a Composer JSON file.
 *
 * This class SHALL provide convenient accessors for commonly used
 * `composer.json` metadata after reading and caching the file contents.
 * Consumers SHOULD use this class when they need normalized access to
 * package-level metadata. The internal data cache MUST reflect the
 * contents returned by the underlying JSON file reader at construction
 * time.
 */
final class ComposerJson extends JsonFile
{
    /**
     * Stores the decoded Composer JSON document contents.
     *
     * This property MUST contain the data read from the target Composer
     * file during construction. Consumers SHOULD treat the structure as
     * internal implementation detail and SHALL rely on accessor methods
     * instead of direct access.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Initializes the Composer JSON reader.
     *
     * When no path is provided, the default Composer file location
     * returned by Composer's factory SHALL be used. The constructor MUST
     * immediately read and cache the JSON document contents so that
     * subsequent accessor methods can operate on the in-memory data.
     *
     * @param string|null $path The absolute or relative path to a
     *                          Composer JSON file. When omitted, the
     *                          default Composer file path SHALL be used.
     */
    public function __construct(?string $path = null)
    {
        parent::__construct($path ?? Factory::getComposerFile());
        $this->data = $this->read();
    }

    /**
     * Returns the package name declared in the Composer file.
     *
     * This method SHALL return the value of the `name` key when present.
     * If the package name is not defined, the method MUST return an
     * empty string.
     *
     * @return string the package name, or an empty string when undefined
     */
    public function getPackageName(): string
    {
        return $this->data['name'] ?? '';
    }

    /**
     * Returns the package description declared in the Composer file.
     *
     * This method SHALL return the value of the `description` key when
     * present. If the description is not defined, the method MUST return
     * an empty string.
     *
     * @return string the package description, or an empty string when
     *                undefined
     */
    public function getPackageDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    /**
     * Returns the package license when it can be resolved to a single value.
     *
     * This method SHALL return the `license` value directly when it is a
     * string. When the license is an array containing exactly one item,
     * that single item SHALL be returned. When the license field is not
     * present, is empty, or cannot be resolved to exactly one string
     * value, the method MUST return null.
     *
     * @return string|null the resolved license identifier, or null when
     *                     no single license value can be determined
     */
    public function getPackageLicense(): ?string
    {
        $license = $this->data['license'] ?? [];

        if (\is_string($license)) {
            return $license;
        }

        if (\is_array($license) && 1 === \count($license)) {
            return $license[0];
        }

        return null;
    }

    /**
     * Returns the package authors declared in the Composer file.
     *
     * This method SHALL return the value of the `authors` key when
     * present. If the key is absent, the method MUST return an empty
     * array.
     *
     * @return array the authors list as declared in the Composer file,
     *               or an empty array when undefined
     */
    public function getAuthors(): array
    {
        return $this->data['authors'] ?? [];
    }

    /**
     * Returns the extra configuration section declared in the Composer file.
     *
     * This method SHALL return the value of the `extra` key when present.
     * If the key is absent, the method MUST return an empty array.
     *
     * @return array the extra configuration data, or an empty array when
     *               undefined
     */
    public function getExtra(): array
    {
        return $this->data['extra'] ?? [];
    }

    /**
     * Returns the autoload configuration for the requested autoload type.
     *
     * This method SHALL inspect the `autoload` section and return the
     * nested configuration for the requested type, such as `psr-4`.
     * When the `autoload` section or the requested type is not defined,
     * the method MUST return an empty array.
     *
     * @param string $type The autoload mapping type to retrieve. This
     *                     defaults to `psr-4`.
     *
     * @return array the autoload configuration for the requested type,
     *               or an empty array when unavailable
     */
    public function getAutoload(string $type = 'psr-4'): array
    {
        $autoload = $this->data['autoload'] ?? [];

        return $autoload[$type] ?? [];
    }
}
