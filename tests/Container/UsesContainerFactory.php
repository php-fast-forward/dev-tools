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

namespace FastForward\DevTools\Tests\Container;

use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use FastForward\DevTools\Environment\Environment;
use FastForward\DevTools\Environment\RuntimeEnvironment;
use FastForward\DevTools\Path\DevToolsPathResolver;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Resets the shared DevTools container around a test class lifecycle.
 */
#[UsesClass(ContainerFactory::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(Environment::class)]
#[UsesClass(RuntimeEnvironment::class)]
trait UsesContainerFactory
{
    /**
     * @return void
     */
    #[BeforeClass]
    #[AfterClass]
    public static function resetSharedContainer(): void
    {
        ContainerFactory::reset();
    }

    /**
     * Overrides a shared container entry for the current test case.
     *
     * @param string $id
     * @param mixed $value
     *
     * @return void
     */
    protected function setContainerEntry(string $id, mixed $value): void
    {
        ContainerFactory::set($id, $value);
    }
}
