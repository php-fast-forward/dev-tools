<?php

namespace FastForward\DevTools\Filesystem;

use Override;
use function Safe\getcwd;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path;

final class Filesystem extends SymfonyFilesystem implements FilesystemInterface
{
    /**
     * @param string|iterable $files
     * @param string|null $basePath
     *
     * @return bool
     */
    #[Override]
    public function exists(string|iterable $files, ?string $basePath = null): bool
    {
        return parent::exists($this->getAbsolutePath($files, $basePath));
    }

    /**
     * @param string $filename
     * @param string|null $path
     *
     * @return string
     */
    #[Override]
    public function readFile(string $filename, ?string $path = null): string
    {
        return parent::readFile($this->getAbsolutePath($filename, $path));
    }

    /**
     * @param string $filename
     * @param mixed $content
     * @param string|null $path
     *
     * @return void
     */
    #[Override]
    public function dumpFile(string $filename, $content, ?string $path = null): void
    {
        parent::dumpFile($this->getAbsolutePath($filename, $path), $content);
    }

    /**
     * @param string|iterable $files
     * @param string|null $basePath
     *
     * @return string|iterable
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
     * @param string|iterable $dirs
     * @param int $mode
     * @param string|null $basePath
     *
     * @return void
     */
    #[Override]
    public function mkdir(string|iterable $dirs, int $mode = 0777, ?string $basePath = null): void
    {
        parent::mkdir($this->getAbsolutePath($dirs, $basePath), $mode);
    }

    /**
     * @param string $path
     * @param string|null $basePath
     *
     * @return string
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
     * @param string $path
     * @param string $suffix
     *
     * @return string
     */
    public function basename(string $path, string $suffix = ''): string
    {
        return \basename($path, $suffix);
    }

    /**
     * @param string $path
     * @param int $levels
     *
     * @return string
     */
    public function dirname(string $path, int $levels = 1): string
    {
        return \dirname($path, $levels);
    }
}
