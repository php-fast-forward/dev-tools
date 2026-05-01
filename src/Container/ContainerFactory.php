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

namespace FastForward\DevTools\Container;

use DI\Container;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Builds and caches the shared DevTools dependency injection container.
 *
 * The factory centralizes container bootstrapping so command traits and other
 * internal helpers can resolve services without duplicating bootstrap logic or
 * depending on the console application entrypoint.
 */
final class ContainerFactory
{
    private static ?Container $container = null;

    /**
     * Creates or returns the shared DevTools container instance.
     *
     * @return ContainerInterface the shared container instance
     */
    public static function create(): ContainerInterface
    {
        if (! self::$container instanceof Container) {
            $serviceProvider = new DevToolsServiceProvider();
            self::$container = new Container($serviceProvider->getFactories());
        }

        return self::$container;
    }

    /**
     * Resolves a service from the shared DevTools container.
     *
     * @template T
     *
     * @param string|class-string<T> $id the service identifier
     *
     * @return mixed|T the resolved service
     */
    public static function get(string $id): mixed
    {
        return self::create()->get($id);
    }

    /**
     * Returns whether the shared DevTools container can resolve a service.
     *
     * @param string $id the service identifier
     */
    public static function has(string $id): bool
    {
        return self::create()->has($id);
    }

    /**
     * Overrides a shared service entry for the current process.
     *
     * @internal this method exists so tests can replace container entries with doubles
     *
     * @param string $id the service identifier
     * @param mixed $value the replacement service entry
     */
    public static function set(string $id, mixed $value): void
    {
        self::create();
        self::$container?->set($id, $value);
    }

    /**
     * Resets the cached shared container instance.
     *
     * @internal this method exists so tests can isolate container state between test cases
     */
    public static function reset(): void
    {
        self::$container = null;
    }
}
