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
use Throwable;

use function explode;
use function implode;
use function str_contains;
use function str_starts_with;

/**
 * Renders deterministic summaries and unified diffs for file comparisons.
 */
final readonly class FileDiffer
{
    /**
     * Creates a new file differ.
     *
     * @param FilesystemInterface $filesystem the filesystem used to read compared file contents
     * @param DifferInterface $differ the differ used to generate unified diffs
     */
    public function __construct(
        private FilesystemInterface $filesystem,
        private DifferInterface $differ,
    ) {}

    /**
     * Compares a source file against the target file that would be overwritten.
     *
     * @param string $sourcePath the source file path that would replace the target
     * @param string $targetPath the existing target file path
     *
     * @return FileDiff the rendered comparison result
     */
    public function diff(string $sourcePath, string $targetPath): FileDiff
    {
        try {
            $sourceContent = $this->filesystem->readFile($sourcePath);
            $targetContent = $this->filesystem->readFile($targetPath);
        } catch (Throwable) {
            return new FileDiff(
                FileDiff::STATUS_UNREADABLE,
                \sprintf(
                    'Target %s will be overwritten from %s, but the existing or source content could not be read.',
                    $targetPath,
                    $sourcePath,
                ),
            );
        }

        return $this->diffContents(
            $sourcePath,
            $targetPath,
            $sourceContent,
            $targetContent,
            \sprintf('Overwriting resource %s from %s.', $targetPath, $sourcePath),
        );
    }

    /**
     * Compares managed content against the current target contents.
     *
     * @param string $sourceLabel the human-readable source label shown in summaries
     * @param string $targetPath the target file path
     * @param string $sourceContent the generated or source content
     * @param string|null $targetContent the current target content, or null when the target does not exist
     * @param string|null $changedSummary an optional changed-state summary override
     *
     * @return FileDiff the rendered comparison result
     */
    public function diffContents(
        string $sourceLabel,
        string $targetPath,
        string $sourceContent,
        ?string $targetContent,
        ?string $changedSummary = null,
    ): FileDiff {
        if (null !== $targetContent && $sourceContent === $targetContent) {
            return new FileDiff(
                FileDiff::STATUS_UNCHANGED,
                \sprintf('Target %s already matches source %s; overwrite skipped.', $targetPath, $sourceLabel),
            );
        }

        if ($this->isBinary($sourceContent) || (null !== $targetContent && $this->isBinary($targetContent))) {
            return new FileDiff(
                FileDiff::STATUS_BINARY,
                \sprintf(
                    'Target %s will be overwritten from %s, but a text diff is unavailable for binary content.',
                    $targetPath,
                    $sourceLabel,
                ),
            );
        }

        $targetContent ??= '';
        $changedSummary ??= \sprintf('Overwriting resource %s from %s.', $targetPath, $sourceLabel);

        return new FileDiff(
            FileDiff::STATUS_CHANGED,
            $changedSummary,
            $this->differ->diff($targetContent, $sourceContent),
        );
    }

    /**
     * Colorizes a unified diff for decorated console output.
     *
     * @param string $diff the plain unified diff
     *
     * @return string the colorized diff using Symfony Console tags
     */
    public function colorize(string $diff): string
    {
        $lines = explode("\n", $diff);

        foreach ($lines as &$line) {
            if (str_starts_with($line, '+++') || str_starts_with($line, '---')) {
                $line = \sprintf('<fg=cyan>%s</>', $line);

                continue;
            }

            if (str_starts_with($line, '@@')) {
                $line = \sprintf('<fg=yellow>%s</>', $line);

                continue;
            }

            if (str_starts_with($line, '+')) {
                $line = \sprintf('<fg=green>%s</>', $line);

                continue;
            }

            if (str_starts_with($line, '-')) {
                $line = \sprintf('<fg=red>%s</>', $line);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Formats a diff payload for console output.
     *
     * @param string|null $diff the plain unified diff, if available
     * @param bool $decorated whether console decoration is enabled
     *
     * @return string|null the diff payload ready for console output
     */
    public function formatForConsole(?string $diff, bool $decorated): ?string
    {
        if (null === $diff) {
            return null;
        }

        if (! $decorated) {
            return $diff;
        }

        return $this->colorize($diff);
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
