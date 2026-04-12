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

namespace FastForward\DevTools\Changelog;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function rtrim;
use function str_contains;
use function str_replace;
use function strpos;
use function substr;

/**
 * Creates missing keep-a-changelog configuration and bootstrap files.
 */
final readonly class Bootstrapper implements BootstrapperInterface
{
    /**
     * Initializes the `Bootstrapper` with optional dependencies.
     *
     * @param Filesystem $filesystem filesystem instance for file operations, allowing for easier testing and potential customization
     * @param HistoryGeneratorInterface $historyGenerator history generator instance for generating changelog history
     * @param KeepAChangelogConfigRenderer $configRenderer config renderer instance for rendering keep-a-changelog configuration
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
        private HistoryGeneratorInterface $historyGenerator = new HistoryGenerator(),
        private KeepAChangelogConfigRenderer $configRenderer = new KeepAChangelogConfigRenderer(),
    ) {}

    /**
     * Bootstraps changelog automation assets in the given working directory.
     *
     * @param string $workingDirectory
     *
     * @return BootstrapResult
     */
    public function bootstrap(string $workingDirectory): BootstrapResult
    {
        $configPath = Path::join($workingDirectory, '.keep-a-changelog.ini');
        $changelogPath = Path::join($workingDirectory, 'CHANGELOG.md');

        $configCreated = false;
        $changelogCreated = false;
        $unreleasedCreated = false;

        if (! $this->filesystem->exists($configPath)) {
            $this->filesystem->dumpFile($configPath, $this->configRenderer->render());
            $configCreated = true;
        }

        if (! $this->filesystem->exists($changelogPath)) {
            $this->filesystem->dumpFile($changelogPath, $this->historyGenerator->generate($workingDirectory));
            $changelogCreated = true;

            return new BootstrapResult($configCreated, $changelogCreated, $unreleasedCreated);
        }

        $contents = $this->filesystem->readFile($changelogPath);

        if (! str_contains($contents, '## Unreleased - ')) {
            $this->filesystem->dumpFile($changelogPath, $this->prependUnreleasedSection($contents));
            $unreleasedCreated = true;
        }

        return new BootstrapResult($configCreated, $changelogCreated, $unreleasedCreated);
    }

    /**
     * @param string $contents
     *
     * @return string
     */
    private function prependUnreleasedSection(string $contents): string
    {
        $heading = "# Changelog\n\nAll notable changes to this project will be documented in this file, in reverse chronological order by release.\n\n";
        $unreleasedSection = "## Unreleased - TBD\n\n### Added\n\n- Nothing.\n\n### Changed\n\n- Nothing.\n\n### Deprecated\n\n- Nothing.\n\n### Removed\n\n- Nothing.\n\n### Fixed\n\n- Nothing.\n\n### Security\n\n- Nothing.\n\n";

        $updatedContents = str_replace($heading, $heading . $unreleasedSection, $contents);

        if ($updatedContents !== $contents) {
            return $updatedContents;
        }

        $firstSecondaryHeadingOffset = strpos($contents, "\n## ");

        if (false === $firstSecondaryHeadingOffset) {
            return rtrim($contents) . "\n\n" . $unreleasedSection;
        }

        return substr($contents, 0, $firstSecondaryHeadingOffset + 1)
            . $unreleasedSection
            . substr($contents, $firstSecondaryHeadingOffset + 1);
    }
}
