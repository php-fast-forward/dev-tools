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

/**
 * Carries the result of comparing source and target file contents.
 */
final readonly class FileDiff
{
    /**
     * @var string indicates that the source and target differ and a text diff is available
     */
    public const string STATUS_CHANGED = 'changed';

    /**
     * @var string indicates that the source and target already match
     */
    public const string STATUS_UNCHANGED = 'unchanged';

    /**
     * @var string indicates that a text diff should not be rendered for the compared files
     */
    public const string STATUS_BINARY = 'binary';

    /**
     * @var string indicates that the compared files could not be read safely
     */
    public const string STATUS_UNREADABLE = 'unreadable';

    /**
     * Creates a new file diff result.
     *
     * @param string $status the comparison status for the source and target files
     * @param string $summary the human-readable summary for console output
     * @param string|null $diff the optional unified diff payload
     */
    public function __construct(
        private string $status,
        private string $summary,
        private ?string $diff = null,
    ) {}

    /**
     * Returns the comparison status.
     *
     * @return string the comparison status value
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns the human-readable summary.
     *
     * @return string the summary for console output
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * Returns the optional unified diff payload.
     *
     * @return string|null the diff payload, or null when no text diff is available
     */
    public function getDiff(): ?string
    {
        return $this->diff;
    }

    /**
     * Reports whether the compared files already match.
     *
     * @return bool true when the source and target contents are identical
     */
    public function isUnchanged(): bool
    {
        return self::STATUS_UNCHANGED === $this->status;
    }

    /**
     * Reports whether the compared files produced a text diff.
     *
     * @return bool true when a text diff is available
     */
    public function isChanged(): bool
    {
        return self::STATUS_CHANGED === $this->status;
    }
}
