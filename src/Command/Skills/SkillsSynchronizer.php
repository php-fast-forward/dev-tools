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

namespace FastForward\DevTools\Command\Skills;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;

/**
 * Synchronizes Fast Forward skills into consumer repositories.
 */
final class SkillsSynchronizer
{
    private readonly Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * Synchronizes skills from the package to the consumer repository.
     *
     * @param string $rootPath The consumer repository root path
     * @param string $skillsDir The target .agents/skills directory
     * @param string $packageSkillsPath The source skills directory in the package
     * @param callable(string): void $logger Callback for logging messages
     *
     * @return SynchronizeResult The result of the synchronization
     */
    public function synchronize(
        string $rootPath,
        string $skillsDir,
        string $packageSkillsPath,
        callable $logger,
    ): SynchronizeResult {
        $result = new SynchronizeResult();

        if (! $this->filesystem->exists($packageSkillsPath)) {
            $logger('<comment>No packaged skills found at: ' . $packageSkillsPath . '</comment>');
            $result->markFailed();

            return $result;
        }

        if (! $this->filesystem->exists($skillsDir)) {
            $this->filesystem->mkdir($skillsDir);
            $logger('<info>Created .agents/skills directory.</info>');
        }

        $this->syncPackageSkills($rootPath, $skillsDir, $packageSkillsPath, $logger, $result);
        $this->cleanupBrokenLinks($skillsDir, $logger, $result);

        return $result;
    }

    /**
     * Syncs skills from the package to the consumer repository.
     *
     * @param string $rootPath
     * @param string $skillsDir
     * @param string $packageSkillsPath
     * @param callable $logger
     * @param SynchronizeResult $result
     */
    private function syncPackageSkills(
        string $rootPath,
        string $skillsDir,
        string $packageSkillsPath,
        callable $logger,
        SynchronizeResult $result,
    ): void {
        $finder = Finder::create()
            ->directories()
            ->in($packageSkillsPath)
            ->depth('== 0');

        foreach ($finder as $skillDir) {
            $skillName = $skillDir->getFilename();
            $targetLink = Path::makeAbsolute($skillName, $skillsDir);
            $sourcePath = $skillDir->getRealPath();

            if ($this->filesystem->exists($targetLink)) {
                // Check if existing target is a valid symlink pointing to source
                if ($this->isSymlink($targetLink)) {
                    $existingTarget = readlink($targetLink);

                    if ($existingTarget === $sourcePath) {
                        $logger('<comment>Preserved existing link: ' . $skillName . '</comment>');
                        $result->addPreservedLink($skillName);

                        continue;
                    }

                    // Broken or wrong symlink - remove and recreate
                    $this->filesystem->remove($targetLink);
                } else {
                    // Non-symlink exists - check if it's the same content
                    // For development mode in dev-tools repo, we might have actual directories
                    // In that case, offer to convert to symlink
                    $logger('<comment>Found existing directory: ' . $skillName . ' (converting to symlink)</comment>');
                    $this->filesystem->remove($targetLink);
                }
            }

            $this->filesystem->symlink($sourcePath, $targetLink);
            $logger('<info>Created link: ' . $skillName . ' -> ' . $sourcePath . '</info>');
            $result->addCreatedLink($skillName);
        }
    }

    /**
     * Cleans up broken symlinks in the skills directory.
     *
     * @param string $skillsDir
     * @param callable $logger
     * @param SynchronizeResult $result
     */
    private function cleanupBrokenLinks(string $skillsDir, callable $logger, SynchronizeResult $result): void
    {
        if (! $this->filesystem->exists($skillsDir)) {
            return;
        }

        $items = scandir($skillsDir);

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $itemPath = Path::makeAbsolute($item, $skillsDir);

            if (! is_link($itemPath)) {
                continue;
            }

            $target = readlink($itemPath);

            if (false === $target) {
                continue;
            }

            if (! file_exists($target)) {
                $this->filesystem->remove($itemPath);
                $logger('<info>Removed broken link: ' . $item . '</info>');
                $result->addRemovedBrokenLink($item);
            }
        }
    }

    /**
     * Checks if a path is a symbolic link.
     *
     * @param string $path
     */
    private function isSymlink(string $path): bool
    {
        return is_link($path);
    }
}
