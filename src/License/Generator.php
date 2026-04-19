<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\License;

use Throwable;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Clock\ClockInterface;
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
     * @param FilesystemInterface $filesystem The filesystem component for file operations
     * @param ClockInterface $clock
     * @param Environment $renderer
     */
    public function __construct(
        private ResolverInterface $resolver,
        private ComposerJsonInterface $composer,
        private ClockInterface $clock,
        private Environment $renderer,
        private FilesystemInterface $filesystem,
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
        $content = $this->generateContent();

        if (null === $content) {
            return null;
        }

        $this->filesystem->dumpFile($targetPath, $content);

        return $content;
    }

    /**
     * Generates license content without writing it to disk.
     *
     * @return string|null the generated license content, or null if generation failed
     */
    public function generateContent(): ?string
    {
        $templateFilename = $this->resolver->resolve($this->composer->getLicense());

        if (null === $templateFilename) {
            return null;
        }

        try {
            $content = $this->renderer->render('licenses/' . $templateFilename, [
                'copyright_holder' => (string) $this->composer->getAuthors(true),
                'year' => $this->clock->now()
                    ->format('Y'),
            ]);
        } catch (Throwable) {
            return null;
        }

        return $content;
    }
}
