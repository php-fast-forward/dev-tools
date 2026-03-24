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

final class StandardsCommand extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('standards')
            ->setDescription('Runs Fast Forward code standards checks.')
            ->setHelp(
                'This command runs all Fast Forward code standards checks, including code refactoring, '
                . 'PHPDoc validation, code style checks, documentation generation, and tests execution.'
            )
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code standards issues.'
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
        $output->writeln('<info>Running code standards checks...</info>');

        $this->runCommand('refactor', $input, $output);
        $this->runCommand('phpdoc', $input, $output);
        $this->runCommand('code-style', $input, $output);
        $this->runCommand('reports', $input, $output);

        $output->writeln('<info>All code standards checks completed!</info>');

        return self::SUCCESS;
    }
}
