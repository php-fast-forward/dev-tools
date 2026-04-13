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

final class ComposerJson extends JsonFile
{
    private array $data;

    /**
     * @param string|null $path
     */
    public function __construct(?string $path = null)
    {
        parent::__construct($path ?? Factory::getComposerFile());
        $this->data = $this->read();
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->data['name'] ?? '';
    }

    /**
     * @return string
     */
    public function getPackageDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    /**
     * @return string|null
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
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->data['authors'] ?? [];
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->data['extra'] ?? [];
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getAutoload(string $type = 'psr-4'): array
    {
        $autoload = $this->data['autoload'] ?? [];

        return $autoload[$type] ?? [];
    }
}
