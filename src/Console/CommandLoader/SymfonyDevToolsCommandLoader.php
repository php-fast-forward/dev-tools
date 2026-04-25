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

namespace FastForward\DevTools\Console\CommandLoader;

use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Loads only migrated Symfony commands for the standalone DevTools application runtime.
 */
final class SymfonyDevToolsCommandLoader extends DevToolsCommandLoader
{
    /**
     * @param FinderFactoryInterface $finderFactory
     * @param ContainerInterface $container
     */
    public function __construct(FinderFactoryInterface $finderFactory, ContainerInterface $container)
    {
        parent::__construct($finderFactory, $container, true);
    }
}
