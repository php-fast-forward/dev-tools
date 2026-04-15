<?php

declare(strict_types=1);

namespace FastForward\DevTools\Filesystem;

interface FilesystemInterface
{
    /**
     * @param string|iterable $files
     * @param string|null $basePath
     *
     * @return bool
     */
    public function exists(string|iterable $files, ?string $basePath = null): bool;

    /**
     * @param string $filename
     * @param string|null $path
     *
     * @return string
     */
    public function readFile(string $filename, ?string $path = null): string;

    /**
     * @param string $filename
     * @param mixed $content
     * @param string|null $path
     *
     * @return void
     */
    public function dumpFile(string $filename, $content, ?string $path = null): void;

    /**
     * @param string|iterable $files
     * @param string|null $basePath
     *
     * @return string|iterable
     */
    public function getAbsolutePath(string|iterable $files, ?string $basePath = null): string|iterable;

    /**
     * @param string|iterable $dirs
     * @param int $mode
     * @param string|null $basePath
     *
     * @return void
     */
    public function mkdir(string|iterable $dirs, int $mode = 0777, ?string $basePath = null): void;

    /**
     * @param string $path
     * @param string|null $basePath
     *
     * @return string
     */
    public function makePathRelative(string $path, ?string $basePath = null): string;

    /**
     * @param string $path
     * @param string $suffix
     *
     * @return string
     */
    public function basename(string $path, string $suffix = ''): string;

    /**
     * @param string $path
     * @param int $levels
     *
     * @return string
     */
    public function dirname(string $path, int $levels = 1): string;
}
