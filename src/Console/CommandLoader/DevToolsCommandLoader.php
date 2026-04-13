<?php

namespace FastForward\DevTools\Console\CommandLoader;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Finder\Finder;

final class DevToolsCommandLoader extends ContainerCommandLoader
{
    public function __construct(
        Finder $finder,
        ContainerInterface $container,
    ) {
        parent::__construct($container, $this->getCommandMap($finder, $container));
    }

    private function getCommandMap(
        Finder $finder,
        ContainerInterface $container
    ): array {
        $commandMap = [];

        $commandsDirectory = $finder
            ->files()
            ->in(__DIR__ . '/../Command')
            ->name('*.php');

        foreach ($commandsDirectory as $file) {
            $class = 'FastForward\\DevTools\\Console\\Command\\' . $file->getBasename('.php');
            $reflection = new \ReflectionClass($class);

             if (!$reflection->isInstantiable() || !$reflection->isSubclassOf(Command::class)) {
                continue;
            }

            $command = $container->get($class);
            $commandMap[$command->getName()] = $class;
        }

        return $commandMap;
    }
}
