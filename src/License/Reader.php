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

use Safe\Exceptions\JsonException;
use SplFileObject;

use function Safe\json_decode;

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
     * @param SplFileObject $source The source file to read from, typically composer.json
     *
     * @throws JsonException if the JSON content is invalid
     */
    public function __construct(SplFileObject $source)
    {
        $this->data = $this->readData($source);
    }

    /**
     * Reads and parses the JSON content from the source file.
     *
     * @param SplFileObject $source The source file to read from
     *
     * @return array The parsed JSON data as an associative array
     *
     * @throws JsonException if the JSON is invalid
     */
    private function readData(SplFileObject $source): array
    {
        $content = $source->fread($source->getSize());

        return json_decode($content, true);
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
        $license = $this->data['license'] ?? [];

        if (\is_string($license)) {
            return $license;
        }

        return $this->extractLicense($license);
    }

    /**
     * Retrieves the package name from composer.json.
     *
     * @return string the full package name (vendor/package), or empty string if not set
     */
    public function getPackageName(): string
    {
        return $this->data['name'] ?? '';
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
        $authors = $this->data['authors'] ?? [];

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
        return (int) date('Y');
    }

    /**
     * Extracts a single license from an array of licenses.
     *
     * Returns the first license if exactly one element exists.
     * Returns null if the array is empty or contains multiple licenses.
     *
     * @param array<string> $license The license array to extract from
     *
     * @return string|null a single license string, or null if extraction is not possible
     */
    private function extractLicense(array $license): ?string
    {
        if ([] === $license) {
            return null;
        }

        if (1 === \count($license)) {
            return $license[0];
        }

        return null;
    }
}
