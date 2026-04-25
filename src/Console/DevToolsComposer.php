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

use Composer\Console\Application;
use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use Override;
use DI\Container;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

/**
 * Legacy composer-backed application that exposes non-migrated BaseCommand commands.
 */
final class DevToolsComposer extends Application
{
    /**
     * @var ContainerInterface holds the shared container instance for global access within the DevTools composer context
     */
    private static ?ContainerInterface $container = null;

    /**
     * @param CommandLoaderInterface $commandLoader
     */
    public function __construct(CommandLoaderInterface $commandLoader)
    {
        parent::__construct('Fast Forward Dev Tools');

        $this->setDefaultCommand('standards');
        $this->setCommandLoader($commandLoader);
    }

    /**
     * Create DevToolsComposer instance from container.
     *
     * @return DevToolsComposer
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

    /**
     * Retrieves the default set of commands provided by the Composer Application.
     *
     * @return array
     */
    #[Override]
    protected function getDefaultCommands(): array
    {
        $reflectionMethod = new ReflectionMethod(Application::class, __FUNCTION__);

        return $reflectionMethod->invoke($this);
    }
}
