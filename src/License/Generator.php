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

use Symfony\Component\Filesystem\Filesystem;

final readonly class Generator implements GeneratorInterface
{
    /**
     * @param Reader $reader
     * @param Resolver $resolver
     * @param TemplateLoader $templateLoader
     * @param PlaceholderResolver $placeholderResolver
     * @param Filesystem $filesystem
     */
    public function __construct(
        private Reader $reader,
        private Resolver $resolver,
        private TemplateLoader $templateLoader,
        private PlaceholderResolver $placeholderResolver,
        private Filesystem $filesystem = new Filesystem()
    ) {}

    /**
     * @param string $targetPath
     *
     * @return string|null
     */
    public function generate(string $targetPath): ?string
    {
        $license = $this->reader->getLicense();

        if (null === $license) {
            return null;
        }

        if (! $this->resolver->isSupported($license)) {
            return null;
        }

        if ($this->filesystem->exists($targetPath)) {
            return null;
        }

        $templateFilename = $this->resolver->resolve($license);

        if (null === $templateFilename) {
            return null;
        }

        $template = $this->templateLoader->load($templateFilename);

        $authors = $this->reader->getAuthors();
        $firstAuthor = $authors[0] ?? null;

        $metadata = [
            'year' => $this->reader->getYear(),
            'organization' => $this->reader->getVendor(),
            'author' => null !== $firstAuthor ? ($firstAuthor['name'] ?: ($firstAuthor['email'] ?? '')) : '',
            'project' => $this->reader->getPackageName(),
        ];

        $content = $this->placeholderResolver->resolve($template, $metadata);

        $this->filesystem->dumpFile($targetPath, $content);

        return $content;
    }

    /**
     * @return bool
     */
    public function hasLicense(): bool
    {
        $license = $this->reader->getLicense();

        if (null === $license) {
            return false;
        }

        return $this->resolver->isSupported($license);
    }
}
