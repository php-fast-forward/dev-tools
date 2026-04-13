<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console;

use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use Override;
use Composer\Console\Application as ComposerApplication;
use DI\Container;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

/**
 * Wraps the fast-forward console tooling suite conceptually as an isolated application instance.
 * Extending the base application, it MUST provide default command injections safely.
 */
final class DevTools extends ComposerApplication
{
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
        if (! self::$container instanceof ContainerInterface) {
            $serviceProvider = new DevToolsServiceProvider();
            self::$container = new Container($serviceProvider->getFactories());
        }

        return self::$container->get(self::class);
    }

    /**
     * Retrieves the default set of commands provided by the Symfony Application.
     *
     * The method SHOULD NOT add composer-specific commands to the list,
     * as they are handled separately by composer when loaded as a plugin.
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
