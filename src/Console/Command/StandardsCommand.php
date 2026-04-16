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

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes the full suite of Fast Forward code standard checks.
 * This class MUST NOT be modified through inheritance and SHALL streamline code validation workflows.
 */
#[AsCommand(
    name: 'standards',
    description: 'Runs Fast Forward code standards checks.',
    help: 'This command runs all Fast Forward code standards checks, including code refactoring, PHPDoc validation, code style checks, documentation generation, and tests execution.'
)]
final class StandardsCommand extends BaseCommand
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

        $results = [];

        $fix = $input->getOption('fix') ? '--fix' : '';

        $results[] = $this->runCommand('refactor ' . $fix, $output);
        $results[] = $this->runCommand('phpdoc ' . $fix, $output);
        $results[] = $this->runCommand('code-style ' . $fix, $output);
        $results[] = $this->runCommand('reports', $output);

        $output->writeln('<info>All code standards checks completed!</info>');

        return \in_array(self::FAILURE, $results, true) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Runs a registered command through the current console application.
     *
     * @param string $command the command line to execute
     * @param OutputInterface $output the output that receives command feedback
     *
     * @return int the dispatched command status code
     */
    private function runCommand(string $command, OutputInterface $output): int
    {
        return $this->getApplication()
            ->doRun(new StringInput($command), $output);
    }
}
