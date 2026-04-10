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

final readonly class Reader implements ReaderInterface
{
    private array $data;

    /**
     * @param SplFileObject $source The source file to read from, typically composer.json
     */
    public function __construct(SplFileObject $source)
    {
        $this->data = $this->readData($source);
    }

    /**
     * @param SplFileObject $source The source file to read from, typically composer.json
     *
     * @return array
     *
     * @throws JsonException if the JSON is invalid
     */
    private function readData(SplFileObject $source): array
    {
        $content = $source->fread($source->getSize());

        return json_decode($content, true);
    }

    /**
     * @return string|null
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
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->data['name'] ?? '';
    }

    /**
     * @return array
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
     * @return string|null
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
     * @return int
     */
    public function getYear(): int
    {
        return (int) date('Y');
    }

    /**
     * @param array $license
     *
     * @return string|null
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
