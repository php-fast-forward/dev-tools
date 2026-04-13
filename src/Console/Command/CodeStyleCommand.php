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

namespace FastForward\DevTools\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Represents the command responsible for checking and fixing code style issues.
 * This class MUST NOT be overridden and SHALL rely on external tools like ECS and Composer Normalize.
 */
#[AsCommand(
    name: 'code-style',
    description: 'Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.',
    help: 'This command runs EasyCodingStandard and Composer Normalize to check and fix code style issues.'
)]
final class CodeStyleCommand extends AbstractCommand
{
    /**
     * @var string the default configuration file used for EasyCodingStandard
     */
    public const string CONFIG = 'ecs.php';

    /**
     * Configures the current command.
     *
     * This method MUST define the name, description, help text, and options for the command.
     * It SHALL register the `--fix` option to allow automatic resolutions of style issues.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code style issues.'
            );
    }

    /**
     * Executes the code style checks and fixes block.
     *
     * The method MUST execute `composer update --lock`, `composer normalize`, and ECS using secure processes.
     * It SHALL return `self::SUCCESS` if all commands succeed, or `self::FAILURE` otherwise.
     *
     * @param InputInterface $input the input interface to retrieve options
     * @param OutputInterface $output the output interface to log messages
     *
     * @return int the status code of the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running code style checks and fixes...</info>');

        $command = new Process(['composer', 'update', '--lock', '--quiet']);

        parent::runProcess($command, $output);

        $command = new Process(['composer', 'normalize', $input->getOption('fix') ? '--quiet' : '--dry-run']);

        parent::runProcess($command, $output);

        $command = new Process([
            $this->getAbsolutePath('vendor/bin/ecs'),
            '--config=' . parent::getConfigFile(self::CONFIG),
            $input->getOption('fix') ? '--fix' : '--clear-cache',
        ]);

        return parent::runProcess($command, $output);
    }
}
