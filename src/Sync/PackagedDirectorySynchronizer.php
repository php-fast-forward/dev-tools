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

namespace FastForward\DevTools\Sync;

use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Synchronizes one packaged directory of symlinked entries into a consumer repository.
 */
final readonly class PackagedDirectorySynchronizer
{
    /**
     * Initializes the synchronizer with a filesystem and finder factory.
     *
     * @param FilesystemInterface $filesystem Filesystem instance for file operations
     * @param FinderFactoryInterface $finderFactory Factory for locating packaged directories
     * @param LoggerInterface $logger Logger for recording synchronization actions and decisions
     */
    public function __construct(
        private FilesystemInterface $filesystem,
        private FinderFactoryInterface $finderFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * Synchronizes packaged directory entries into the consumer repository.
     *
     * @param string $targetDir Absolute path to the consumer directory to populate
     * @param string $packagePath Absolute path to the packaged directory to mirror
     * @param string $directoryLabel Human-readable directory label used in log messages
     *
     * @return SynchronizeResult Result containing counts of created, preserved, and removed links
     */
    public function synchronize(string $targetDir, string $packagePath, string $directoryLabel): SynchronizeResult
    {
        $result = new SynchronizeResult();

        if (! $this->filesystem->exists($packagePath)) {
            $this->logger->error('No packaged ' . $directoryLabel . ' found at: ' . $packagePath);
            $result->markFailed();

            return $result;
        }

        if (! $this->filesystem->exists($targetDir)) {
            $this->filesystem->mkdir($targetDir);
            $this->logger->info('Created ' . $directoryLabel . ' directory.');
        }

        $finder = $this->finderFactory
            ->create()
            ->in($packagePath)
            ->depth('== 0');

        foreach ($finder as $packagedEntry) {
            $entryName = $packagedEntry->getFilename();
            $targetLink = Path::makeAbsolute($entryName, $targetDir);
            $sourcePath = $packagedEntry->getRealPath();
            $isDirectory = $packagedEntry->isDir();

            $this->processLink($entryName, $targetLink, $sourcePath, $isDirectory, $result);
        }

        return $result;
    }

    /**
     * Routes an entry link to the appropriate handling method based on target state.
     *
     * @param string $entryName Name of the packaged entry being processed
     * @param string $targetLink Absolute path where the symlink should exist
     * @param string $sourcePath Absolute path to the packaged source directory
     * @param SynchronizeResult $result Result tracker for reporting outcomes
     * @param bool $isDirectory
     */
    private function processLink(
        string $entryName,
        string $targetLink,
        string $sourcePath,
        bool $isDirectory,
        SynchronizeResult $result,
    ): void {
        if (! $this->filesystem->exists($targetLink)) {
            $this->createNewLink($entryName, $targetLink, $sourcePath, $isDirectory, $result);

            return;
        }

        if (! $this->isSymlink($targetLink)) {
            $this->preserveExistingNonSymlink($entryName, $result);

            return;
        }

        $this->processExistingSymlink($entryName, $targetLink, $sourcePath, $isDirectory, $result);
    }

    /**
     * Creates a new symlink pointing to the packaged entry.
     *
     * @param string $entryName Name identifying the entry
     * @param string $targetLink Absolute path where the symlink will be created
     * @param string $sourcePath Absolute path to the packaged directory
     * @param SynchronizeResult $result Result object for tracking creation
     * @param bool $isDirectory
     */
    private function createNewLink(
        string $entryName,
        string $targetLink,
        string $sourcePath,
        bool $isDirectory,
        SynchronizeResult $result,
    ): void {
        $relativeSourcePath = $this->normalizeRelativeSourcePath(
            $this->filesystem->makePathRelative($sourcePath, $this->filesystem->getDirectory($targetLink)),
            $isDirectory,
        );

        $this->filesystem->symlink($relativeSourcePath, $targetLink);
        $this->logger->info('Created link: ' . $entryName . ' -> ' . $relativeSourcePath);
        $result->addCreatedLink($entryName);
    }

    /**
     * Handles an existing non-symlink item at the target path.
     *
     * @param string $entryName Name of the entry with the conflicting item
     * @param SynchronizeResult $result Result tracker for preserved items
     */
    private function preserveExistingNonSymlink(string $entryName, SynchronizeResult $result): void
    {
        $this->logger->notice(
            'Existing non-symlink found: ' . $entryName . ' (keeping as is, skipping link creation)'
        );
        $result->addPreservedLink($entryName);
    }

    /**
     * Evaluates an existing symlink and determines whether to preserve or repair it.
     *
     * @param string $entryName Name of the entry with the existing symlink
     * @param string $targetLink Absolute path to the existing symlink
     * @param string $sourcePath Absolute path to the expected source directory
     * @param SynchronizeResult $result Result tracker for preserved or removed links
     * @param bool $isDirectory
     */
    private function processExistingSymlink(
        string $entryName,
        string $targetLink,
        string $sourcePath,
        bool $isDirectory,
        SynchronizeResult $result,
    ): void {
        $linkPath = $this->filesystem->readlink($targetLink, true);

        if (! $linkPath || ! $this->filesystem->exists($linkPath)) {
            $this->repairBrokenLink($entryName, $targetLink, $sourcePath, $isDirectory, $result);

            return;
        }

        $this->logger->notice('Preserved existing link: ' . $entryName);
        $result->addPreservedLink($entryName);
    }

    /**
     * Removes a broken symlink and creates a fresh one pointing to the current source.
     *
     * @param string $entryName Name of the entry with the broken symlink
     * @param string $targetLink Absolute path to the broken symlink
     * @param string $sourcePath Absolute path to the current packaged directory
     * @param SynchronizeResult $result Result tracker for removed and created items
     * @param bool $isDirectory
     */
    private function repairBrokenLink(
        string $entryName,
        string $targetLink,
        string $sourcePath,
        bool $isDirectory,
        SynchronizeResult $result,
    ): void {
        $this->filesystem->remove($targetLink);
        $this->logger->notice('Existing link is broken: ' . $entryName . ' (removing and recreating)');
        $result->addRemovedBrokenLink($entryName);

        $this->createNewLink($entryName, $targetLink, $sourcePath, $isDirectory, $result);
    }

    /**
     * Normalizes a relative symlink target emitted by Symfony path helpers.
     *
     * Files MUST NOT keep the trailing slash that directory-oriented path helpers
     * may append, otherwise link creation treats them as non-existent directories.
     *
     * @param string $relativeSourcePath Relative path from the consumer target directory to the packaged source
     * @param bool $isDirectory Whether the packaged source is a directory
     *
     * @return string Normalized relative symlink target
     */
    private function normalizeRelativeSourcePath(string $relativeSourcePath, bool $isDirectory): string
    {
        if ($isDirectory) {
            return $relativeSourcePath;
        }

        return rtrim($relativeSourcePath, '/');
    }

    /**
     * Checks if a path is a symbolic link.
     *
     * @param string $path the target path
     *
     * @return bool whether the path is a symbolic link
     */
    private function isSymlink(string $path): bool
    {
        return null !== $this->filesystem->readlink($path);
    }
}
