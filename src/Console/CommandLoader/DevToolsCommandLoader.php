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
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

/**
 * Responsible for dynamically discovering and loading Symfony Console commands
 * within the DevTools context. This class extends the ContainerCommandLoader
 * and integrates with a PSR-11 compatible container to lazily instantiate commands.
 *
 * The implementation MUST scan a predefined directory for PHP classes representing
 * console commands and SHALL only register classes that:
 * - Are instantiable
 * - Extend the Symfony\Component\Console\Command\Command base class
 * - Declare the Symfony\Component\Console\Attribute\AsCommand attribute
 *
 * The command name MUST be extracted from the AsCommand attribute metadata and
 * used as the key in the command map. Classes that do not meet these criteria
 * MUST NOT be included in the command map.
 */
final class DevToolsCommandLoader extends ContainerCommandLoader
{
    /**
     * Constructs the DevToolsCommandLoader.
     *
     * This constructor initializes the command loader by scanning the Command directory for classes that are
     * instantiable and have the AsCommand attribute.
     * It builds a command map associating command names with their respective classes.
     *
     * @param FinderFactoryInterface $finderFactory
     * @param ContainerInterface $container
     */
    public function __construct(FinderFactoryInterface $finderFactory, ContainerInterface $container)
    {
        parent::__construct($container, $this->getCommandMap($finderFactory));
    }

    /**
     * Builds a command map by scanning the Command directory for classes that are instantiable and have the AsCommand attribute.
     *
     * @param FinderFactoryInterface $finderFactory
     *
     * @return array
     */
    private function getCommandMap(FinderFactoryInterface $finderFactory): array
    {
        $commandMap = [];

        $commandsDirectory = $finderFactory
            ->create()
            ->files()
            ->in(__DIR__ . '/../Command')
            ->notPath('Traits')
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
            $commandNames = [$arguments['name'], ...($arguments['aliases'] ?? [])];

            foreach ($commandNames as $commandName) {
                if ('' === $commandName) {
                    continue;
                }

                $commandMap[$commandName] = $class;
            }
        }

        return $commandMap;
    }
}
