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

namespace FastForward\DevTools\Console\Command;

use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Reflection\ClassReflection;
use FastForward\DevTools\SelfUpdate\SelfUpdateRunnerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates the installed DevTools package through Composer.
 */
#[AsCommand(
    name: 'dev-tools:self-update',
    description: 'Updates the installed fast-forward/dev-tools package.',
    aliases: ['self-update', 'selfupdate'],
)]
final class SelfUpdateCommand extends Command
{
    use LogsCommandResults;

    /**
     * @param SelfUpdateRunnerInterface $selfUpdateRunner the runner that executes Composer's update command
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly SelfUpdateRunnerInterface $selfUpdateRunner,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Returns the command name and aliases declared through AsCommand.
     *
     * @return list<string>
     */
    public static function getCommandNames(): array
    {
        static $commandNames = null;

        if (null !== $commandNames) {
            return $commandNames;
        }

        $arguments = ClassReflection::getAttributeArguments(self::class, AsCommand::class);
        $commandNames = [$arguments['name'], ...$arguments['aliases']];

        return $commandNames = array_values(array_filter(
            $commandNames,
            static fn(string $commandName): bool => '' !== $commandName,
        ));
    }

    /**
     * Configures the self-update command.
     */
    protected function configure(): void
    {
        $this->setHelp(
            'This command updates fast-forward/dev-tools through Composer. By default it updates the current'
            . ' project installation; use --global for Composer global installations.'
        );

        $this->addOption(
            name: 'global',
            mode: InputOption::VALUE_NONE,
            description: 'Update the Composer global fast-forward/dev-tools installation.',
        );
    }

    /**
     * Executes the Composer update flow.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $global = (bool) $input->getOption('global');

        $this->logger->info('Updating DevTools installation...', [
            'input' => $input,
            'global' => $global,
        ]);

        $statusCode = $this->selfUpdateRunner->update($global, $output);

        if (self::SUCCESS === $statusCode) {
            return $this->success('DevTools self-update completed successfully.', $input, [
                'global' => $global,
            ]);
        }

        return $this->failure('DevTools self-update failed.', $input, [
            'global' => $global,
            'status_code' => $statusCode,
        ]);
    }
}
