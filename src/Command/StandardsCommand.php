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

/**
 * Executes the full suite of Fast Forward code standard checks.
 * This class MUST NOT be modified through inheritance and SHALL streamline code validation workflows.
 */
final class StandardsCommand extends AbstractCommand
{
    /**
     * Configures constraints and arguments for the collective standard runner.
     *
     * This method MUST specify definitions and help texts appropriately. It SHALL
     * expose an optional `--fix` mode.
     *
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
     * Evaluates multiple commands seamlessly in a sequential execution.
     *
     * The method MUST trigger refactoring, phpdoc generation, code styling, and reports building block consecutively.
     * It SHALL reliably return a standard SUCCESS execution state on completion.
     *
     * @param InputInterface $input internal input arguments retrieved via terminal runtime constraints
     * @param OutputInterface $output external output mechanisms
     *
     * @return int the status indicator describing the completion
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
