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

use FastForward\DevTools\Composer\Json\ComposerJson;
use Psr\Clock\ClockInterface;
use Safe\Exceptions\JsonException;

/**
 * Reads composer.json and exposes metadata for license generation.
 *
 * This class parses a composer.json file via SplFileObject and provides
 * methods to extract license information, package name, authors, vendor,
 * and the current year for copyright notices.
 */
final readonly class Reader implements ReaderInterface
{
    private array $data;

    /**
     * Creates a new Reader instance.
     *
     * @param ComposerJson $source The source file to read from, typically composer.json
     *
     * @throws JsonException if the JSON content is invalid
     */
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly ComposerJson $composerJson,
    ) {
        $this->data = $composerJson->read();
    }

    /**
     * Retrieves the license identifier from composer.json.
     *
     * If the license is a single string, returns it directly.
     * If it's an array with one element, extracts that element.
     * Returns null if no license is set or if multiple licenses are specified.
     *
     * @return string|null the license string, or null if not set or unsupported
     */
    public function getLicense(): ?string
    {
        return $this->composerJson->getPackageLicense();
    }

    /**
     * Retrieves the package name from composer.json.
     *
     * @return string the full package name (vendor/package), or empty string if not set
     */
    public function getPackageName(): string
    {
        return $this->composerJson->getPackageName();
    }

    /**
     * Retrieves the list of authors from composer.json.
     *
     * Each author is normalized to include name, email, homepage, and role fields.
     * Returns an empty array if no authors are defined.
     *
     * @return array<int, array{name: string, email: string, homepage: string, role: string}>
     */
    public function getAuthors(): array
    {
        $authors = $this->composerJson->getAuthors();

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
     * Extracts the vendor name from the package name.
     *
     * The package name is expected in vendor/package format.
     * Returns null if no package name is set or if the package has no vendor prefix.
     *
     * @return string|null the vendor name, or null if package has no vendor prefix
     */
    public function getVendor(): ?string
    {
        $packageName = $this->getPackageName();

        if ('' === $packageName) {
            return null;
        }

        $parts = explode('/', $packageName, 2);

        if (! isset($parts[1])) {
            return null;
        }

        return $parts[0];
    }

    /**
     * Returns the current year for copyright notices.
     *
     * @return int the current year as an integer
     */
    public function getYear(): int
    {
        $now = $this->clock->now();

        return (int) $now->format('Y');
    }
}
