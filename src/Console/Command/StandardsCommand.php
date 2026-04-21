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
use Psr\Log\LoggerInterface;
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
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures constraints and arguments for the collective standard runner.
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
                default: 'text',
                suggestedValues: ['text', 'json'],
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = 'json' === (string) $input->getOption('output-format');
        $commandOutput = $jsonOutput ? new BufferedOutput() : $output;
        $results = [];
        $commands = [];
        $formatArgument = $jsonOutput ? ' --output-format=json' : '';

        $this->logger->info('Running code standards checks...');

        $fixArgument = $input->getOption('fix') ? ' --fix' : '';

        foreach (['refactor', 'phpdoc', 'code-style', 'reports'] as $command) {
            $commands[] = $command;
            $results[] = $this->runCommand(
                $command . ('reports' === $command ? '' : $fixArgument) . $formatArgument,
                $commandOutput,
            );
        }

        $context = [
            'command' => 'standards',
            'fix' => (bool) $input->getOption('fix'),
            'commands' => $commands,
            'process_output' => $commandOutput instanceof BufferedOutput ? $commandOutput->fetch() : null,
        ];

        if (\in_array(self::FAILURE, $results, true)) {
            $this->logger->error('Code standards checks failed.', $context);

            return self::FAILURE;
        }

        $this->logger->info('Code standards checks completed successfully.', $context);

        return self::SUCCESS;
    }

    /**
     * @param string $command
     * @param OutputInterface $output
     */
    private function runCommand(string $command, OutputInterface $output): int
    {
        return $this->getApplication()
            ->doRun(new StringInput($command), $output);
    }
}
