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

namespace FastForward\DevTools\GitIgnore;

use ArrayIterator;
use SplFileObject;

/**
 * Represents a .gitignore file with its path and entries.
 *
 * This class implements IteratorAggregate to allow iteration over entries
 * and provides a factory method to load .gitignore content from the file system.
 *
 * @implements GitIgnoreInterface
 */
final readonly class GitIgnore implements GitIgnoreInterface
{
    /**
     * Initializes a GitIgnore instance with the given path and entries.
     *
     * @param string $path the file system path to the .gitignore file
     * @param list<string> $entries the .gitignore entries
     */
    public function __construct(
        public string $path,
        public array $entries
    ) {}

    /**
     * Returns the file system path to the .gitignore file.
     *
     * @return string the absolute path to the .gitignore file
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Returns the list of entries from the .gitignore file.
     *
     * @return list<string> the non-empty .gitignore entries
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * Returns an iterator over the .gitignore entries.
     *
     * This method implements the IteratorAggregate interface.
     *
     * @return ArrayIterator<int, string> an iterator over the entries
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->entries);
    }

    /**
     * Creates a GitIgnore instance from a file path.
     *
     * If the file does not exist, returns an empty GitIgnore with the given path.
     * Empty lines and whitespace-only lines are filtered from the entries.
     *
     * @param string $gitignorePath the file system path to the .gitignore file
     *
     * @return static a new GitIgnore instance
     */
    public static function fromFile(string $gitignorePath): self
    {
        if (! file_exists($gitignorePath)) {
            return new self($gitignorePath, []);
        }

        $file = new SplFileObject($gitignorePath, 'r');
        $file->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $entries = [];
        foreach ($file as $line) {
            $trimmed = trim($line ?: '');

            if ('' !== $trimmed) {
                $entries[] = $trimmed;
            }
        }

        return new self($gitignorePath, array_values($entries));
    }
}
