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

namespace FastForward\DevTools\Composer\Capability;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Console\Command\ProxyCommand;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Console\DevToolsComposer;
use Symfony\Component\Console\Command\Command;

/**
 * Provides a registry of custom dev-tools commands mapped for Composer integration.
 * This capability struct MUST implement the defined `CommandProvider`.
 */
final class DevToolsCommandProvider implements CommandProvider
{
    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        $legacyCommands = DevToolsComposer::create()->all();
        $reservedCommandNames = $this->collectCommandNames($legacyCommands);
        $migratedCommands = DevTools::create()->all();

        $commands = $legacyCommands;

        foreach ($migratedCommands as $command) {
            if (! $command instanceof Command || $command instanceof BaseCommand) {
                continue;
            }

            if ($this->hasReservedName($command, $reservedCommandNames)) {
                continue;
            }

            $commands[] = new ProxyCommand($command);
        }

        return array_values(array_filter(
            $commands,
            static fn(object $command): bool => $command instanceof BaseCommand,
        ));
    }

    /**
     * Collects command names and aliases that must remain mapped to legacy commands.
     *
     * @param array<int, BaseCommand> $commands
     *
     * @return array<string, true>
     */
    private function collectCommandNames(array $commands): array
    {
        $commandNames = [];

        foreach ($commands as $command) {
            if (! $command instanceof BaseCommand) {
                continue;
            }

            if (null !== $command->getName()) {
                $commandNames[$command->getName()] = true;
            }

            foreach ($command->getAliases() as $alias) {
                $commandNames[$alias] = true;
            }
        }

        return $commandNames;
    }

    /**
     * Verifies whether the command name or any aliases collide with legacy command names.
     *
     * @param Command $command
     * @param array<string, true> $reservedCommandNames
     */
    private function hasReservedName(Command $command, array $reservedCommandNames): bool
    {
        if (null !== $command->getName() && isset($reservedCommandNames[$command->getName()])) {
            return true;
        }

        foreach ($command->getAliases() as $alias) {
            if (isset($reservedCommandNames[$alias])) {
                return true;
            }
        }

        return false;
    }
}
