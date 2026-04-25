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

namespace FastForward\DevTools\Console;

use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use DI\Container;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

/**
 * Wraps the fast-forward console tooling suite conceptually as an isolated application instance.
 * Extending the base application, it MUST provide default command injections safely.
 */
final class DevTools extends Application
{
    /**
     * @var ContainerInterface holds the static container instance for global access within the DevTools context
     */
    private static ?ContainerInterface $container = null;

    /**
     * Initializes the DevTools global context and dependency graph.
     *
     * The method MUST define default configurations and MAY accept an explicit command provider.
     * It SHALL instruct the runner to treat the `standards` command generically as its default endpoint.
     *
     * @param CommandLoaderInterface $commandLoader the command loader responsible for providing command instances
     */
    public function __construct(CommandLoaderInterface $commandLoader)
    {
        parent::__construct('Fast Forward Dev Tools');

        $this->setDefaultCommand('standards');
        $this->setCommandLoader($commandLoader);
    }

    /**
     * Create DevTools instance from container.
     *
     * @return DevTools
     */
    public static function create(): self
    {
        return self::getContainer()->get(self::class);
    }

    /**
     * Retrieves the shared DevTools service container.
     */
    public static function getContainer(): ContainerInterface
    {
        if (! self::$container instanceof ContainerInterface) {
            $serviceProvider = new DevToolsServiceProvider();
            self::$container = new Container($serviceProvider->getFactories());
        }

        return self::$container;
    }
}
