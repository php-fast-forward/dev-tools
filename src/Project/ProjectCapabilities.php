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
 * Describes the documentation, testing, and wiki surfaces exposed by the current repository.
 */
final readonly class ProjectCapabilities
{
    /**
     * @param list<string> $apiDirectories project-relative directories that contain autoloaded PHP API source
     * @param string|null $defaultPackageName the default package name derived from Composer namespaces when available
     * @param bool $hasGuideDirectory whether the configured guide directory exists
     * @param bool $hasTestsPath whether the configured tests path exists
     * @param bool $hasWikiTarget whether the configured wiki target exists
     * @param bool $hasPhpSourceFiles whether the repository contains PHP source files outside generated/vendor areas
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
     * @return list<string>
     */
    public function getApiDirectories(): array
    {
        return $this->apiDirectories;
    }

    /**
     * @return string|null
     */
    public function getDefaultPackageName(): ?string
    {
        return $this->defaultPackageName;
    }

    /**
     * @return bool
     */
    public function hasGuideDirectory(): bool
    {
        return $this->hasGuideDirectory;
    }

    /**
     * @return bool
     */
    public function hasTestsPath(): bool
    {
        return $this->hasTestsPath;
    }

    /**
     * @return bool
     */
    public function hasWikiTarget(): bool
    {
        return $this->hasWikiTarget;
    }

    /**
     * @return bool
     */
    public function hasPhpSourceFiles(): bool
    {
        return $this->hasPhpSourceFiles;
    }

    /**
     * @return bool
     */
    public function canGenerateApiDocumentation(): bool
    {
        return [] !== $this->apiDirectories;
    }

    /**
     * @return bool
     */
    public function canGenerateDocs(): bool
    {
        return $this->hasGuideDirectory || $this->canGenerateApiDocumentation();
    }

    /**
     * @return bool
     */
    public function canGenerateMetrics(): bool
    {
        return $this->hasTestsPath || $this->hasPhpSourceFiles;
    }

    /**
     * @return bool
     */
    public function canGenerateWiki(): bool
    {
        return $this->hasWikiTarget && $this->canGenerateApiDocumentation();
    }

    /**
     * @return bool
     */
    public function canRunTests(): bool
    {
        return $this->hasTestsPath || $this->hasPhpSourceFiles;
    }
}
