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

namespace FastForward\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Provides functionality to execute automated code refactoring using Rector.
 * This class MUST NOT be extended and SHALL encapsulate the logic for Rector invocation.
 */
final class RefactorCommand extends AbstractCommand
{
    /**
     * @var string the default Rector configuration file
     */
    public const string CONFIG = 'rector.php';

    /**
     * Configures the refactor command options and description.
     *
     * This method MUST define the expected `--fix` option. It SHALL configure the command name
     * and descriptions accurately.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('refactor')
            ->setDescription('Runs Rector for code refactoring.')
            ->setHelp('This command runs Rector to refactor your code.')
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code refactoring issues.'
            );
    }

    /**
     * Executes the refactoring process securely.
     *
     * The method MUST execute Rector securely via `Process`. It SHALL use dry-run mode
     * unless the `--fix` option is specified. It MUST return `self::SUCCESS` or `self::FAILURE`.
     *
     * @param InputInterface $input the input interface to retrieve arguments properly
     * @param OutputInterface $output the output interface to log outputs
     *
     * @return int the status code denoting success or failure
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running Rector for code refactoring...</info>');

        $command = new Process([
            $this->getAbsolutePath('vendor/bin/rector'),
            'process',
            '--config',
            parent::getConfigFile(self::CONFIG),
            $input->getOption('fix') ? null : '--dry-run',
        ]);

        return parent::runProcess($command, $output);
    }
}
