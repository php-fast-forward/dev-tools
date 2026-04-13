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

namespace FastForward\DevTools\Agent\Skills;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;

/**
 * Synchronizes Fast Forward skills into consumer repositories.
 *
 * This class manages the creation and maintenance of symlinks from a consumer
 * repository to the skills packaged within the fast-forward/dev-tools dependency.
 * It handles initial sync, idempotent re-runs, and cleanup of broken links.
 */
final class SkillsSynchronizer implements LoggerAwareInterface
{
    /**
     * Initializes the synchronizer with a filesystem and finder instance.
     *
     * @param Filesystem $filesystem Filesystem instance for file operations
     * @param Finder $finder Finder instance for locating skill directories in the package
     * @param LoggerInterface $logger Logger for recording synchronization actions and decisions
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Finder $finder,
        private LoggerInterface $logger,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Synchronizes skills from the package to the consumer repository.
     *
     * Ensures the consumer repository contains linked Fast Forward skills by
     * creating symlinks to the packaged skills directory. Creates the target
     * directory if missing, skips existing valid links, and repairs broken ones.
     *
     * @param string $skillsDir Absolute path to the consumer's .agents/skills directory
     * @param string $packageSkillsPath Absolute path to the packaged skills in the dependency
     *
     * @return SynchronizeResult Result containing counts of created, preserved, and removed links
     */
    public function synchronize(string $skillsDir, string $packageSkillsPath): SynchronizeResult
    {
        $result = new SynchronizeResult();

        if (! $this->filesystem->exists($packageSkillsPath)) {
            $this->logger->error('No packaged skills found at: ' . $packageSkillsPath);
            $result->markFailed();

            return $result;
        }

        if (! $this->filesystem->exists($skillsDir)) {
            $this->filesystem->mkdir($skillsDir);
            $this->logger->info('Created .agents/skills directory.');
        }

        $this->syncPackageSkills($skillsDir, $packageSkillsPath, $result);

        return $result;
    }

    /**
     * Iterates through all packaged skills and processes each one.
     *
     * Uses Finder to locate skill directories in the package, then processes
     * each as a potential symlink in the consumer repository.
     *
     * @param string $skillsDir Target directory for symlinks
     * @param string $packageSkillsPath Source directory containing packaged skills
     * @param SynchronizeResult $result Result object to track outcomes
     */
    private function syncPackageSkills(
        string $skillsDir,
        string $packageSkillsPath,
        SynchronizeResult $result,
    ): void {
        $finder = $this->finder
            ->directories()
            ->in($packageSkillsPath)
            ->depth('== 0');

        foreach ($finder as $skillDir) {
            $skillName = $skillDir->getFilename();
            $targetLink = Path::makeAbsolute($skillName, $skillsDir);
            $sourcePath = $skillDir->getRealPath();

            $this->processSkillLink($skillName, $targetLink, $sourcePath, $result);
        }
    }

    /**
     * Routes a skill link to the appropriate handling method based on target state.
     *
     * Determines whether the target path needs creation, preservation, or repair
     * based on filesystem checks, then delegates to the corresponding method.
     *
     * @param string $skillName Name of the skill being processed
     * @param string $targetLink Absolute path where the symlink should exist
     * @param string $sourcePath Absolute path to the packaged skill directory
     * @param SynchronizeResult $result Result tracker for reporting outcomes
     */
    private function processSkillLink(
        string $skillName,
        string $targetLink,
        string $sourcePath,
        SynchronizeResult $result,
    ): void {
        if (! $this->filesystem->exists($targetLink)) {
            $this->createNewLink($skillName, $targetLink, $sourcePath, $result);

            return;
        }

        if (! $this->isSymlink($targetLink)) {
            $this->preserveExistingNonSymlink($skillName, $result);

            return;
        }

        $this->processExistingSymlink($skillName, $targetLink, $sourcePath, $result);
    }

    /**
     * Creates a new symlink pointing to the packaged skill.
     *
     * This method is called when no existing item exists at the target path.
     * Creates the symlink, logs the creation, and records it in the result.
     *
     * @param string $skillName Name identifying the skill
     * @param string $targetLink Absolute path where the symlink will be created
     * @param string $sourcePath Absolute path to the packaged skill directory
     * @param SynchronizeResult $result Result object for tracking creation
     */
    private function createNewLink(
        string $skillName,
        string $targetLink,
        string $sourcePath,
        SynchronizeResult $result,
    ): void {
        $this->filesystem->symlink($sourcePath, $targetLink);
        $this->logger->info('Created link: ' . $skillName . ' -> ' . $sourcePath);
        $result->addCreatedLink($skillName);
    }

    /**
     * Handles an existing non-symlink item at the target path.
     *
     * When the target exists but is a real directory (not a symlink), this method
     * preserves it unchanged and logs the decision. Real directories are not
     * replaced to avoid accidental data loss.
     *
     * @param string $skillName Name of the skill with the conflicting item
     * @param SynchronizeResult $result Result tracker for preserved items
     */
    private function preserveExistingNonSymlink(string $skillName, SynchronizeResult $result): void
    {
        $this->logger->notice('Existing non-symlink found: ' . $skillName . ' (keeping as is, skipping link creation)');
        $result->addPreservedLink($skillName);
    }

    /**
     * Evaluates an existing symlink and determines whether to preserve or repair it.
     *
     * Reads the symlink target and checks if it points to a valid, existing path.
     * Delegates to repair if broken, otherwise preserves the valid link in place.
     *
     * @param string $skillName Name of the skill with the existing symlink
     * @param string $targetLink Absolute path to the existing symlink
     * @param string $sourcePath Absolute path to the expected source directory
     * @param SynchronizeResult $result Result tracker for preserved or removed links
     */
    private function processExistingSymlink(
        string $skillName,
        string $targetLink,
        string $sourcePath,
        SynchronizeResult $result,
    ): void {
        $linkPath = $this->filesystem->readlink($targetLink, true);

        if (! $linkPath || ! $this->filesystem->exists($linkPath)) {
            $this->repairBrokenLink($skillName, $targetLink, $sourcePath, $result);

            return;
        }

        $this->logger->notice('Preserved existing link: ' . $skillName);
        $result->addPreservedLink($skillName);
    }

    /**
     * Removes a broken symlink and creates a fresh one pointing to the current source.
     *
     * Called when the existing symlink target either does not exist or points to
     * an invalid path. Removes the broken link, logs the repair, records the removal,
     * then delegates to createNewLink for the fresh symlink.
     *
     * @param string $skillName Name of the skill with the broken symlink
     * @param string $targetLink Absolute path to the broken symlink
     * @param string $sourcePath Absolute path to the current packaged skill
     * @param SynchronizeResult $result Result tracker for removed and created items
     */
    private function repairBrokenLink(
        string $skillName,
        string $targetLink,
        string $sourcePath,
        SynchronizeResult $result,
    ): void {
        $this->filesystem->remove($targetLink);
        $this->logger->notice('Existing link is broken: ' . $skillName . ' (removing and recreating)');
        $result->addRemovedBrokenLink($skillName);

        $this->createNewLink($skillName, $targetLink, $sourcePath, $result);
    }

    /**
     * Checks if a path is a symbolic link.
     *
     * @param string $path
     */
    private function isSymlink(string $path): bool
    {
        return null !== $this->filesystem->readlink($path);
    }
}
