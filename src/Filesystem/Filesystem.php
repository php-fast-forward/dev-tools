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

namespace FastForward\DevTools\Filesystem;

use Override;
use function Safe\getcwd;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Concrete implementation of the standard filesystem interface.
 *
 * This class wraps over the Symfony Filesystem component, automatically
 * converting provided paths to absolute representations when a base path is supplied or
 * dynamically inferred from the generic working directory.
 */
final class Filesystem extends SymfonyFilesystem implements FilesystemInterface
{
    /**
     * Checks whether a file or directory exists.
     *
     * @param iterable<string>|string $files the file(s) or directory(ies) to check
     * @param string|null $basePath the base path used to resolve relative paths
     *
     * @return bool true if the path exists, false otherwise
     */
    #[Override]
    public function exists(string|iterable $files, ?string $basePath = null): bool
    {
        return parent::exists($this->getAbsolutePath($files, $basePath));
    }

    /**
     * Reads the entire content of a file.
     *
     * @param string $filename the target filename to read
     * @param string|null $path the optional base path to resolve the filename against
     *
     * @return string the content of the file
     */
    #[Override]
    public function readFile(string $filename, ?string $path = null): string
    {
        return parent::readFile($this->getAbsolutePath($filename, $path));
    }

    /**
     * Writes content to a file, overriding it if it already exists.
     *
     * @param string $filename the filename to write to
     * @param mixed $content the content to write
     * @param string|null $path the optional base path to resolve the filename against
     */
    #[Override]
    public function dumpFile(string $filename, mixed $content, ?string $path = null): void
    {
        parent::dumpFile($this->getAbsolutePath($filename, $path), $content);
    }

    /**
     * Resolves a path or iterable of paths into their absolute path representation.
     *
     * @param iterable<string>|string $files the path(s) to resolve
     * @param string|null $basePath the base path for relative path resolution
     *
     * @return iterable<string>|string the resolved absolute path(s)
     */
    public function getAbsolutePath(string|iterable $files, ?string $basePath = null): string|iterable
    {
        $basePath ??= getcwd();

        if (! $this->isAbsolutePath($basePath)) {
            $basePath = Path::makeAbsolute($basePath, getcwd());
        }

        if (is_string($files)) {
            return Path::makeAbsolute($files, $basePath);
        }

        return array_map(static fn (string $file): string => Path::makeAbsolute($file, $basePath), $files);
    }

    /**
     * Creates a directory recursively.
     *
     * @param iterable<string>|string $dirs the directory path(s) to create
     * @param int $mode the permissions mode (defaults to 0777)
     * @param string|null $basePath the base path for relative path resolution
     */
    #[Override]
    public function mkdir(string|iterable $dirs, int $mode = 0777, ?string $basePath = null): void
    {
        parent::mkdir($this->getAbsolutePath($dirs, $basePath), $mode);
    }

    /**
     * Computes the relative path from the base path to the target path.
     *
     * @param string $path the target absolute or relative path
     * @param string|null $basePath the origin point; defaults to the current working directory
     *
     * @return string the computed relative path
     */
    #[Override]
    public function makePathRelative(string $path, ?string $basePath = null): string
    {
        return parent::makePathRelative(
            $this->getAbsolutePath($path, $basePath),
            $basePath ?? getcwd(),
        );
    }

    /**
     * Returns the trailing name component of a path.
     *
     * @param string $path the path to process
     * @param string $suffix an optional suffix to strip from the returned basename
     *
     * @return string the base name of the given path
     */
    public function basename(string $path, string $suffix = ''): string
    {
        return \basename($path, $suffix);
    }

    /**
     * Returns a parent directory's path.
     *
     * @param string $path the path to evaluate
     * @param int $levels the number of parent directories to go up
     *
     * @return string the parent path name
     */
    public function dirname(string $path, int $levels = 1): string
    {
        return \dirname($path, $levels);
    }
}
