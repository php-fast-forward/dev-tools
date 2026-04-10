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

use Composer\Composer;
use Composer\Package\RootPackageInterface;

final readonly class Reader
{
    /**
     * @param Composer $composer
     */
    public function __construct(
        private Composer $composer
    ) {}

    /**
     * @return string|null
     */
    public function getLicense(): ?string
    {
        $package = $this->composer->getPackage();

        return $this->extractLicense($package);
    }

    /**
     * @return string
     */
    public function getPackageName(): string
    {
        $package = $this->composer->getPackage();

        return $package->getName();
    }

    /**
     * @return array
     */
    public function getAuthors(): array
    {
        $package = $this->composer->getPackage();
        $authors = $package->getAuthors();

        if ([] === $authors) {
            return [];
        }

        return array_map(
            static fn(array $author): array => [
                'name' => $author['name'] ?? '',
                'email' => $author['email'] ?? '',
                'homepage' => $author['homepage'] ?? '',
                'role' => $author['role'] ?? '',
            ],
            $authors
        );
    }

    /**
     * @return string|null
     */
    public function getVendor(): ?string
    {
        $packageName = $this->getPackageName();

        if (null === $packageName) {
            return null;
        }

        $parts = explode('/', $packageName, 2);

        if (! isset($parts[1])) {
            return null;
        }

        return $parts[0];
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return (int) date('Y');
    }

    /**
     * @param RootPackageInterface $package
     *
     * @return string|null
     */
    private function extractLicense(RootPackageInterface $package): ?string
    {
        $license = $package->getLicense();

        if ([] === $license) {
            return null;
        }

        if (1 === \count($license)) {
            return $license[0];
        }

        return null;
    }
}
