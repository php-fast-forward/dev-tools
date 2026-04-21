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
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
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
     * @param CommandResponderFactoryInterface $commandResponderFactory the
     *                                                                  structured
     *                                                                  command
     *                                                                  responder
     *                                                                  factory
     */
    public function __construct(
        private readonly CommandResponderFactoryInterface $commandResponderFactory,
    ) {
        parent::__construct();
    }

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
            )
            ->addOption(
                name: 'output-format',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format for the command result. Supported values: text, json.',
                default: OutputFormat::defaultValue(),
                suggestedValues: OutputFormat::supportedValues(),
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
        $responder = $this->commandResponderFactory->from($input, $output);
        $format = $responder->format();
        $textOutput = OutputFormat::TEXT === $format;
        $commandOutput = $textOutput ? $output : new BufferedOutput();

        $results = [];
        $commands = [];
        $formatArgument = OutputFormat::JSON === $format ? ' --output-format=json' : '';

        if ($textOutput) {
            $output->writeln('<info>Running code standards checks...</info>');
        }

        $fixArgument = $input->getOption('fix') ? ' --fix' : '';

        foreach (['refactor', 'phpdoc', 'code-style', 'reports'] as $command) {
            $commands[] = $command;
            $results[] = $this->runCommand(
                $command . ('reports' === $command ? '' : $fixArgument) . $formatArgument,
                $commandOutput,
            );
        }

        if ($textOutput) {
            $output->writeln('<info>All code standards checks completed!</info>');
        }

        return \in_array(self::FAILURE, $results, true)
            ? $responder->failure(
                'Code standards checks failed.',
                [
                    'command' => 'standards',
                    'fix' => (bool) $input->getOption('fix'),
                    'commands' => $commands,
                    'process_output' => $commandOutput instanceof BufferedOutput ? $commandOutput->fetch() : null,
                ],
            )
            : $responder->success(
                'Code standards checks completed successfully.',
                [
                    'command' => 'standards',
                    'fix' => (bool) $input->getOption('fix'),
                    'commands' => $commands,
                    'process_output' => $commandOutput instanceof BufferedOutput ? $commandOutput->fetch() : null,
                ],
            );
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
