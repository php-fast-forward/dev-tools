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

use function array_key_first;
use function array_values;
use function is_dir;
use function is_file;
use function rtrim;
use function str_ends_with;
use function strtolower;

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
     * Creates a capability resolver backed by Composer autoload metadata and filesystem checks.
     *
     * @param ComposerJsonInterface $composer the composer.json accessor for autoload metadata
     * @param FilesystemInterface $filesystem the filesystem used to resolve project-relative paths
     */
    public function __construct(
        private ComposerJsonInterface $composer,
        private FilesystemInterface $filesystem,
    ) {}

    /**
     * Resolves which documentation, testing, and wiki surfaces are available for the current repository.
     *
     * @param string $testsPath the project-relative tests directory to inspect
     * @param string $guideDirectory the project-relative guide directory to inspect
     * @param string $wikiTarget the project-relative wiki output target to inspect
     */
    public function resolve(
        string $testsPath = ProjectCapabilitiesResolverInterface::DEFAULT_TESTS_PATH,
        string $guideDirectory = ProjectCapabilitiesResolverInterface::DEFAULT_GUIDE_DIRECTORY,
        string $wikiTarget = ProjectCapabilitiesResolverInterface::DEFAULT_WIKI_TARGET,
    ): ProjectCapabilities {
        $psr4Autoload = $this->composer->getAutoload('psr-4');
        $apiDirectories = $this->resolveApiDirectories();

        return new ProjectCapabilities(
            $apiDirectories,
            $this->resolveDefaultPackageName($psr4Autoload),
            $this->filesystem->exists($guideDirectory),
            $this->filesystem->exists($testsPath),
            $this->filesystem->exists($wikiTarget),
            $this->resolveHasPhpSourceFiles($apiDirectories),
        );
    }

    /**
     * Resolves the default API package name from the first PSR-4 namespace entry when available.
     *
     * @param array<string, mixed> $psr4Autoload the PSR-4 autoload map from composer.json
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
     * Resolves project-relative API directories exposed by Composer autoload configuration.
     *
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
     * Resolves a Composer autoload path into a project-relative API directory when it exists.
     *
     * @param string $path the Composer autoload path candidate
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
     * Resolves whether Composer autoload metadata exposes testable PHP source for the repository.
     *
     * @param list<string> $apiDirectories the resolved API directories exposed by Composer autoload metadata
     */
    private function resolveHasPhpSourceFiles(array $apiDirectories): bool
    {
        if ([] !== $apiDirectories) {
            return true;
        }

        foreach (self::API_AUTOLOAD_TYPES as $autoloadType) {
            foreach ($this->normalizeAutoloadPaths($this->composer->getAutoload($autoloadType)) as $path) {
                $absolutePath = $this->filesystem->getAbsolutePath($path);

                if (! \is_string($absolutePath)) {
                    continue;
                }

                if (is_file($absolutePath) && str_ends_with(strtolower($absolutePath), '.php')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Flattens Composer autoload path definitions into a normalized list of non-empty paths.
     *
     * @param array<string, mixed> $autoload the Composer autoload section to normalize
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
