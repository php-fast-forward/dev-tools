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

namespace FastForward\DevTools\Console\Input;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Path;

/**
 * Provides the standard cache option used by cache-writing commands.
 */
trait HasCacheOption
{
    /**
     * Adds the standard cache control option to the current command.
     *
     * @param string $description the cache option description
     *
     * @return static
     */
    protected function addCacheOption(string $description): static
    {
        if ($this->getDefinition()->hasOption('cache')) {
            return $this;
        }

        return $this->addOption(name: 'cache', mode: InputOption::VALUE_NONE, description: $description);
    }

    /**
     * Adds the standard cache directory option to the current command.
     *
     * @param string $description the cache directory option description
     * @param string $default the command-specific default cache directory
     *
     * @return static
     */
    protected function addCacheDirOption(string $description, string $default): static
    {
        if ($this->getDefinition()->hasOption('cache-dir')) {
            return $this;
        }

        return $this->addOption(
            name: 'cache-dir',
            mode: InputOption::VALUE_OPTIONAL,
            description: $description,
            default: $default,
        );
    }

    /**
     * Resolves whether cache writes SHOULD be enabled for the current invocation.
     *
     * @param InputInterface $input the current command input
     * @param bool $default the command-specific default cache behavior when the option is omitted
     */
    protected function isCacheEnabled(InputInterface $input, bool $default = true): bool
    {
        if ((bool) $input->getOption('cache')) {
            return true;
        }

        if ($this->isNoCacheRequested($input)) {
            return false;
        }

        return $default;
    }

    /**
     * Returns the explicit cache flag that SHOULD be forwarded to nested commands.
     *
     * @param InputInterface $input the current command input
     */
    protected function resolveCacheArgument(InputInterface $input): ?string
    {
        if ((bool) $input->getOption('cache')) {
            return '--cache';
        }

        if ($this->isNoCacheRequested($input)) {
            return '--no-cache';
        }

        return null;
    }

    /**
     * Resolves a nested cache directory for a child command.
     *
     * @param InputInterface $input the current command input
     * @param string $path the child cache path relative to the current command cache root
     */
    protected function resolveCacheDirArgument(InputInterface $input, string $path = ''): ?string
    {
        if (! $this->hasExplicitCacheDirArgument($input)) {
            return null;
        }

        try {
            $cacheDir = $input->getOption('cache-dir');
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! \is_string($cacheDir) || '' === $cacheDir) {
            return null;
        }

        return '' === $path
            ? $cacheDir
            : Path::join($cacheDir, $path);
    }

    /**
     * Determines whether the current invocation explicitly passed `--cache-dir`.
     *
     * @param InputInterface $input the current command input
     */
    private function hasExplicitCacheDirArgument(InputInterface $input): bool
    {
        try {
            return $input->hasParameterOption('--cache-dir', true);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Determines whether cache writes were explicitly disabled for the current invocation.
     *
     * The Composer application already provides a global `--no-cache` flag, so commands
     * SHALL reuse that switch instead of redefining a local negated variant.
     *
     * @param InputInterface $input the current command input
     */
    private function isNoCacheRequested(InputInterface $input): bool
    {
        try {
            return true === $input->getOption('no-cache');
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
