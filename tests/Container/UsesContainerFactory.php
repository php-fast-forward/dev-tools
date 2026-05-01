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

/**
 * Resets the shared DevTools container around a test class lifecycle.
 */
trait UsesContainerFactory
{
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        ContainerFactory::reset();
    }

    /**
     * @return void
     */
    public static function tearDownAfterClass(): void
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
