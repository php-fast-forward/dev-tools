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

namespace FastForward\DevTools\Resource;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Throwable;

use function sprintf;
use function str_contains;
use function trim;

/**
 * Renders deterministic overwrite summaries and unified diffs for copied files.
 */
final readonly class OverwriteDiffRenderer
{
    /**
     * Creates a new overwrite diff renderer.
     *
     * @param FilesystemInterface $filesystem the filesystem used to read compared file contents
     */
    public function __construct(private FilesystemInterface $filesystem)
    {
    }

    /**
     * Compares a source file against the target file that would be overwritten.
     *
     * @param string $sourcePath the source file path that would replace the target
     * @param string $targetPath the existing target file path
     *
     * @return OverwriteDiffResult the rendered comparison result
     */
    public function render(string $sourcePath, string $targetPath): OverwriteDiffResult
    {
        try {
            $sourceContent = $this->filesystem->readFile($sourcePath);
            $targetContent = $this->filesystem->readFile($targetPath);
        } catch (Throwable) {
            return new OverwriteDiffResult(
                OverwriteDiffResult::STATUS_UNREADABLE,
                sprintf(
                    'Target %s will be overwritten from %s, but the existing or source content could not be read.',
                    $targetPath,
                    $sourcePath,
                ),
            );
        }

        if ($sourceContent === $targetContent) {
            return new OverwriteDiffResult(
                OverwriteDiffResult::STATUS_UNCHANGED,
                sprintf('Target %s already matches source %s; overwrite skipped.', $targetPath, $sourcePath),
            );
        }

        if ($this->isBinary($sourceContent) || $this->isBinary($targetContent)) {
            return new OverwriteDiffResult(
                OverwriteDiffResult::STATUS_BINARY,
                sprintf(
                    'Target %s will be overwritten from %s, but a text diff is unavailable for binary content.',
                    $targetPath,
                    $sourcePath,
                ),
            );
        }

        $header = sprintf("--- Current: %s\n+++ Source: %s\n", $targetPath, $sourcePath);
        $differ = new Differ(new UnifiedDiffOutputBuilder($header));

        return new OverwriteDiffResult(
            OverwriteDiffResult::STATUS_CHANGED,
            sprintf('Overwriting resource %s from %s.', $targetPath, $sourcePath),
            trim($differ->diff($targetContent, $sourceContent)),
        );
    }

    /**
     * Reports whether the given content should be treated as binary.
     *
     * @param string $content the content to inspect
     *
     * @return bool true when the content should not receive a text diff
     */
    private function isBinary(string $content): bool
    {
        return str_contains($content, "\0");
    }
}
