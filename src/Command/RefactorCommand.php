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

final class RefactorCommand extends AbstractCommand
{
    public const string CONFIG = 'rector.php';

    /**
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
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
