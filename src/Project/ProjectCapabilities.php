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

/**
 * Captures which documentation, testing, and wiki surfaces are available for the current repository.
 */
final readonly class ProjectCapabilities
{
    /**
     * Creates a repository capability snapshot for reporting and documentation commands.
     *
     * @param list<string> $apiDirectories project-relative directories that contain autoloaded PHP API source
     * @param string|null $defaultPackageName the default package name derived from Composer namespaces when available
     * @param bool $hasGuideDirectory whether the configured guide directory exists
     * @param bool $hasTestsPath whether the configured tests path exists
     * @param bool $hasWikiTarget whether the configured wiki target exists
     * @param bool $hasPhpSourceFiles whether the repository exposes autoloaded PHP source that can be tested
     */
    public function __construct(
        private array $apiDirectories,
        private ?string $defaultPackageName,
        private bool $hasGuideDirectory,
        private bool $hasTestsPath,
        private bool $hasWikiTarget,
        private bool $hasPhpSourceFiles,
    ) {}

    /**
     * Returns the project-relative directories that expose autoloaded PHP API source.
     */
    public function getApiDirectories(): array
    {
        return $this->apiDirectories;
    }

    /**
     * Returns the default API package name when one can be derived from Composer namespaces.
     */
    public function getDefaultPackageName(): ?string
    {
        return $this->defaultPackageName;
    }

    /**
     * Detects whether the configured guide directory exists.
     */
    public function hasGuideDirectory(): bool
    {
        return $this->hasGuideDirectory;
    }

    /**
     * Detects whether the configured tests directory exists.
     */
    public function hasTestsPath(): bool
    {
        return $this->hasTestsPath;
    }

    /**
     * Detects whether the configured wiki target exists.
     */
    public function hasWikiTarget(): bool
    {
        return $this->hasWikiTarget;
    }

    /**
     * Detects whether the repository exposes autoloaded PHP source that can be tested.
     */
    public function hasPhpSourceFiles(): bool
    {
        return $this->hasPhpSourceFiles;
    }

    /**
     * Detects whether the repository exposes autoloaded PHP API source.
     */
    public function canGenerateApiDocumentation(): bool
    {
        return [] !== $this->apiDirectories;
    }

    /**
     * Detects whether the repository can generate guides, API documentation, or both.
     */
    public function canGenerateDocs(): bool
    {
        return $this->hasGuideDirectory || $this->canGenerateApiDocumentation();
    }

    /**
     * Detects whether metrics generation can analyse repository history and package metadata.
     */
    public function canGenerateMetrics(): bool
    {
        return true;
    }

    /**
     * Detects whether wiki generation can render API documentation into the configured wiki target.
     */
    public function canGenerateWiki(): bool
    {
        return $this->hasWikiTarget && $this->canGenerateApiDocumentation();
    }

    /**
     * Detects whether the repository has enough PHP surface to justify running tests.
     */
    public function canRunTests(): bool
    {
        return $this->hasPhpSourceFiles;
    }
}
