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

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Generates LICENSE files from composer.json metadata.
 *
 * This class orchestrates the license generation workflow:
 * 1. Reads metadata from composer.json via Reader
 * 2. Resolves the license identifier to a template filename
 * 3. Uses the central Template Engine and VariablesFactory to map out the substitutions
 * 4. Writes the resulting LICENSE file to the target path
 *
 * Generation is skipped if a LICENSE file already exists or if the
 * license is not supported.
 */
final readonly class Generator implements GeneratorInterface
{
    /**
     * Creates a new Generator instance.
     *
     * @param ResolverInterface $resolver The resolver for mapping license identifiers to templates
     * @param ComposerJsonInterface $composer 
     * @param Filesystem $filesystem The filesystem component for file operations
     */
    public function __construct(
        private ResolverInterface $resolver,
        private ComposerJsonInterface $composer,
        private ClockInterface $clock,
        private Environment $renderer,
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
        $templateFilename = $this->resolver->resolve($this->composer->getPackageLicense());

        if (null === $templateFilename) {
            return null;
        }

        try {
            $content = $this->renderer->render('licenses/' . $templateFilename, [
                'copyright_holder' => $this->getCopyrightHolder(),
                'year' => $this->clock->now()->format('Y'),
            ]);
        } catch (\Throwable $throwable) {
            return null;
        }

        $this->filesystem->dumpFile($targetPath, $content);

        return $content;
    }

    /**
     * Gets the copyright holder name from composer.json.
     *
     * @return string The copyright holder name
     */
    private function getCopyrightHolder(): string
    {
        $authors = $this->composer->getAuthors();

        if ([] === $authors) {
            return '';
        }

        $firstAuthor = $authors[0];

        return $firstAuthor['name'] ?? $firstAuthor['email'] ?? '';
    }
}
