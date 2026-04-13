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

namespace FastForward\DevTools\Console\CommandLoader;

use ReflectionClass;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Finder\Finder;

final class DevToolsCommandLoader extends ContainerCommandLoader
{
    /**
     * Constructs the DevToolsCommandLoader.
     *
     * This constructor initializes the command loader by scanning the Command directory for classes that are
     * instantiable and have the AsCommand attribute.
     * It builds a command map associating command names with their respective classes.
     *
     * @param Finder $finder
     * @param ContainerInterface $container
     */
    public function __construct(Finder $finder, ContainerInterface $container)
    {
        parent::__construct($container, $this->getCommandMap($finder));
    }

    /**
     * Builds a command map by scanning the Command directory for classes that are instantiable and have the AsCommand attribute.
     *
     * @param Finder $finder
     *
     * @return array
     */
    private function getCommandMap(Finder $finder): array
    {
        $commandMap = [];

        $commandsDirectory = $finder
            ->files()
            ->in(__DIR__ . '/../Command')
            ->name('*.php');

        $namespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) . '\\Command\\';

        foreach ($commandsDirectory as $file) {
            $class = $namespace . $file->getBasename('.php');
            $reflection = new ReflectionClass($class);
            if (! $reflection->isInstantiable()) {
                continue;
            }

            if (! $reflection->isSubclassOf(Command::class)) {
                continue;
            }

            $attribute = $reflection->getAttributes(AsCommand::class)[0] ?? null;

            if (null === $attribute) {
                continue;
            }

            $arguments = $attribute->getArguments();
            $commandMap[$arguments['name']] = $class;
        }

        return $commandMap;
    }
}
