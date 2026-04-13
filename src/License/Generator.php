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

/**
 * Generates LICENSE files from composer.json metadata.
 *
 * This class orchestrates the license generation workflow:
 * 1. Reads metadata from composer.json via Reader
 * 2. Resolves the license identifier to a template filename
 * 3. Loads the license template content
 * 4. Resolves placeholders with metadata (year, author, project, organization)
 * 5. Writes the resulting LICENSE file to the target path
 *
 * Generation is skipped if a LICENSE file already exists or if the
 * license is not supported.
 */
final readonly class Generator implements GeneratorInterface
{
    /**
     * Creates a new Generator instance.
     *
     * @param ReaderInterface $reader The reader for extracting metadata from composer.json
     * @param ResolverInterface $resolver The resolver for mapping license identifiers to templates
     * @param TemplateLoaderInterface $templateLoader The loader for reading template files
     * @param PlaceholderResolverInterface $placeholderResolver The resolver for template placeholders
     * @param Filesystem $filesystem The filesystem component for file operations
     */
    public function __construct(
        private ReaderInterface $reader,
        private ResolverInterface $resolver,
        private TemplateLoaderInterface $templateLoader,
        private PlaceholderResolverInterface $placeholderResolver,
        private Filesystem $filesystem,
    ) {}

    /**
     * Generates a LICENSE file at the specified path.
     *
     * @param string $targetPath The full path where the LICENSE file should be written
     *
     * @return string|null The generated license content, or null if generation failed
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
     * Checks whether a supported license is present in composer.json.
     *
     * @return bool True if a supported license is defined, false otherwise
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
