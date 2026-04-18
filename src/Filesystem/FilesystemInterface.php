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

namespace FastForward\DevTools\Filesystem;

/**
 * Defines a standard file system interface with enhanced path resolution.
 *
 * This interface extends common filesystem operations by ensuring paths
 * can be deterministically resolved to absolute paths before interaction.
 */
interface FilesystemInterface
{
    /**
     * Checks whether a file or directory exists.
     *
     * @param iterable<string>|string $files the file(s) or directory(ies) to check
     * @param string|null $basePath the base path used to resolve relative paths
     *
     * @return bool true if the path exists, false otherwise
     */
    public function exists(string|iterable $files, ?string $basePath = null): bool;

    /**
     * Reads the entire content of a file.
     *
     * @param string $filename the target filename to read
     * @param string|null $path the optional base path to resolve the filename against
     *
     * @return string the content of the file
     */
    public function readFile(string $filename, ?string $path = null): string;

    /**
     * Writes content to a file, overriding it if it already exists.
     *
     * @param string $filename the filename to write to
     * @param mixed $content the content to write
     * @param string|null $path the optional base path to resolve the filename against
     */
    public function dumpFile(string $filename, mixed $content, ?string $path = null): void;

    /**
     * Copies a file to a target path.
     *
     * @param string $originFile the source file path to copy
     * @param string $targetFile the target file path to create
     * @param bool $overwriteNewerFiles whether newer target files MAY be overwritten
     */
    public function copy(string $originFile, string $targetFile, bool $overwriteNewerFiles = false): void;

    /**
     * Changes the permission mode for one or more files.
     *
     * @param iterable<string>|string $files the target file paths
     * @param int $mode the permission mode to apply
     * @param int $umask the umask to apply
     * @param bool $recursive whether permissions SHOULD be applied recursively
     */
    public function chmod(string|iterable $files, int $mode, int $umask = 0o000, bool $recursive = false): void;

    /**
     * Removes files, symbolic links, or directories.
     *
     * @param iterable<string>|string $files the file(s), link(s), or directory(ies) to remove
     */
    public function remove(string|iterable $files): void;

    /**
     * Creates a symbolic link.
     *
     * @param string $originDir the origin path the link MUST point to
     * @param string $targetDir the link path to create
     * @param bool $copyOnWindows whether directories SHOULD be copied on Windows instead of linked
     */
    public function symlink(string $originDir, string $targetDir, bool $copyOnWindows = false): void;

    /**
     * Reads a symbolic link target.
     *
     * @param string $path the symbolic link path
     * @param bool $canonicalize whether the returned path SHOULD be canonicalized
     *
     * @return string|null the link target, or null when the path is not a symbolic link
     */
    public function readlink(string $path, bool $canonicalize = false): ?string;

    /**
     * Resolves a path or iterable of paths into their absolute path representation.
     *
     * If a relative path is provided, it SHALL be evaluated against the current
     * working directory or the provided $basePath if one is supplied.
     *
     * @param iterable<string>|string $files the path(s) to resolve
     * @param string|null $basePath the base path for relative path resolution
     *
     * @return iterable<string>|string the resolved absolute path(s)
     */
    public function getAbsolutePath(string|iterable $files, ?string $basePath = null): string|iterable;

    /**
     * Creates a directory recursively.
     *
     * @param iterable<string>|string $dirs the directory path(s) to create
     * @param int $mode the permissions mode (defaults to 0777)
     * @param string|null $basePath the base path for relative path resolution
     */
    public function mkdir(string|iterable $dirs, int $mode = 0o777, ?string $basePath = null): void;

    /**
     * Computes the relative path from the base path to the target path.
     *
     * @param string $path the target absolute or relative path
     * @param string|null $basePath the origin point; defaults to the current working directory
     *
     * @return string the computed relative path
     */
    public function makePathRelative(string $path, ?string $basePath = null): string;

    /**
     * Returns the trailing name component of a path.
     *
     * @param string $path the path to process
     * @param string $suffix an optional suffix to strip from the returned basename
     *
     * @return string the base name of the given path
     */
    public function basename(string $path, string $suffix = ''): string;

    /**
     * Returns a parent directory's path.
     *
     * @param string $path the path to evaluate
     * @param int $levels the number of parent directories to go up
     *
     * @return string the parent path name
     */
    public function dirname(string $path, int $levels = 1): string;
}
