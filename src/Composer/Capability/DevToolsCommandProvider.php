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

use Composer\Plugin\Capability\CommandProvider;
use FastForward\DevTools\Composer\Command\ProxyCommand;
use FastForward\DevTools\Composer\DevToolsPluginInterface;
use FastForward\DevTools\Console\DevTools;
use Symfony\Component\Console\Command\Command;

/**
 * Provides a registry of custom dev-tools commands mapped for Composer integration.
 * This capability struct MUST implement the defined `CommandProvider`.
 */
final readonly class DevToolsCommandProvider implements CommandProvider
{
    /**
     * @var string the namespace prefix for dev-tools console commands to be registered as Composer commands
     */
    private const string COMMAND_NAMESPACE = 'FastForward\DevTools\Console\Command';

    private ?DevToolsPluginInterface $plugin;

    /**
     * @param array<string, mixed> $constructorArguments the Composer capability constructor arguments
     */
    public function __construct(array $constructorArguments = [])
    {
        $plugin = $constructorArguments['plugin'] ?? null;
        $this->plugin = $plugin instanceof DevToolsPluginInterface ? $plugin : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        $commands = [];

        foreach (DevTools::create()->all() as $registeredName => $command) {
            /**
             * Composer plugin registrations must be canonicalized to one command per Symfony command.
             * The application exposes alias keys in `all()`, but Composer interprets each entry as
             * an independent command and emits override warnings.
             */
            if ($registeredName !== $command->getName()) {
                continue;
            }

            if (! str_starts_with($command::class, self::COMMAND_NAMESPACE)) {
                continue;
            }

            if ($this->isRegisteredCommand($command->getName())) {
                continue;
            }

            $commands[] = new ProxyCommand($command, $this->getComposerAliases($command));
        }

        return $commands;
    }

    /**
     * Returns command aliases that may be safely exposed to Composer.
     *
     * @param Command $command the Symfony command being proxied
     *
     * @return list<string>
     */
    private function getComposerAliases(Command $command): array
    {
        return array_values(array_filter(
            $command->getAliases(),
            fn(string $alias): bool => ! $this->isRegisteredCommand($alias),
        ));
    }

    /**
     * Detects names already owned by Composer's active command surface.
     *
     * @param string|null $name the command name or alias being evaluated
     */
    private function isRegisteredCommand(?string $name): bool
    {
        return $this->plugin?->isRegisteredCommand($name) ?? false;
    }
}
