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

use FastForward\DevTools\Filesystem\FilesystemInterface;

/**
 * Renders and persists normalized .gitignore content.
 *
 * This writer SHALL transform a GitIgnoreInterface representation into the
 * textual format expected by a .gitignore file and MUST persist that content to
 * the target path exposed by the provided object. Implementations MUST write a
 * trailing line feed to ensure consistent file formatting.
 */
final readonly class Writer implements WriterInterface
{
    /**
     * Creates a writer with the filesystem dependency used for persistence.
     *
     * The provided filesystem implementation MUST support writing file contents
     * to the target path returned by a GitIgnoreInterface instance.
     *
     * @param FilesystemInterface $filesystem The filesystem service responsible for
     *                                        writing the rendered .gitignore content.
     */
    public function __construct(
        private FilesystemInterface $filesystem
    ) {}

    /**
     * Writes the normalized .gitignore entries to the target file path.
     *
     * The implementation SHALL join all entries using a Unix line feed and MUST
     * append a final trailing line feed to the generated content. The resulting
     * content MUST be written to the path returned by $gitignore->path().
     *
     * @param GitIgnoreInterface $gitignore The .gitignore representation whose
     *                                      path and entries SHALL be written.
     *
     * @return void
     */
    public function write(GitIgnoreInterface $gitignore): void
    {
        $content = implode("\n", $gitignore->entries()) . "\n";

        $this->filesystem->dumpFile($gitignore->path(), $content);
    }
}
