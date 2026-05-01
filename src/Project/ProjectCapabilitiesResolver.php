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

namespace FastForward\DevTools\Project;

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\WorkingProjectPathResolver;

use function array_key_first;
use function array_values;
use function is_dir;
use function rtrim;

/**
 * Resolves which repository surfaces are available to documentation, testing, and wiki tooling.
 */
final readonly class ProjectCapabilitiesResolver implements ProjectCapabilitiesResolverInterface
{
    /**
     * @var list<string> Composer autoload sections that MAY expose API source directories
     */
    private const array API_AUTOLOAD_TYPES = ['psr-4', 'psr-0', 'classmap'];

    /**
     * @param ComposerJsonInterface $composer
     * @param FilesystemInterface $filesystem
     */
    public function __construct(
        private ComposerJsonInterface $composer,
        private FilesystemInterface $filesystem,
    ) {}

    /**
     * @param string $testsPath
     * @param string $guideDirectory
     * @param string $wikiTarget
     *
     * @return ProjectCapabilities
     */
    public function resolve(
        string $testsPath = ProjectCapabilitiesResolverInterface::DEFAULT_TESTS_PATH,
        string $guideDirectory = ProjectCapabilitiesResolverInterface::DEFAULT_GUIDE_DIRECTORY,
        string $wikiTarget = ProjectCapabilitiesResolverInterface::DEFAULT_WIKI_TARGET,
    ): ProjectCapabilities {
        $psr4Autoload = $this->composer->getAutoload('psr-4');

        return new ProjectCapabilities(
            $this->resolveApiDirectories(),
            $this->resolveDefaultPackageName($psr4Autoload),
            $this->filesystem->exists($guideDirectory),
            $this->filesystem->exists($testsPath),
            $this->filesystem->exists($wikiTarget),
            [] !== WorkingProjectPathResolver::getToolingSourcePaths(),
        );
    }

    /**
     * @param array<string, mixed> $psr4Autoload
     */
    private function resolveDefaultPackageName(array $psr4Autoload): ?string
    {
        $defaultPackageName = array_key_first($psr4Autoload);

        if (! \is_string($defaultPackageName) || '' === $defaultPackageName) {
            return null;
        }

        return rtrim($defaultPackageName, '\\');
    }

    /**
     * @return list<string>
     */
    private function resolveApiDirectories(): array
    {
        $directories = [];

        foreach (self::API_AUTOLOAD_TYPES as $autoloadType) {
            foreach ($this->normalizeAutoloadPaths($this->composer->getAutoload($autoloadType)) as $path) {
                $relativePath = $this->resolveRelativeApiDirectory($path);

                if (null === $relativePath) {
                    continue;
                }

                $directories[$relativePath] = $relativePath;
            }
        }

        return array_values($directories);
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function resolveRelativeApiDirectory(string $path): ?string
    {
        $absolutePath = $this->filesystem->getAbsolutePath($path);

        if (! \is_string($absolutePath)) {
            return null;
        }

        if (! is_dir($absolutePath)) {
            return null;
        }

        return $this->filesystem->makePathRelative($absolutePath);
    }

    /**
     * @param array<string, mixed> $autoload
     *
     * @return list<string>
     */
    private function normalizeAutoloadPaths(array $autoload): array
    {
        $paths = [];

        foreach ($autoload as $path) {
            if (\is_string($path) && '' !== $path) {
                $paths[] = $path;

                continue;
            }

            if (! \is_array($path)) {
                continue;
            }

            foreach ($path as $nestedPath) {
                if (! \is_string($nestedPath)) {
                    continue;
                }

                if ('' === $nestedPath) {
                    continue;
                }

                $paths[] = $nestedPath;
            }
        }

        return $paths;
    }
}
