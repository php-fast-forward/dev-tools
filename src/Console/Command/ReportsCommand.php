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
use Composer\Console\Input\InputOption;
use FastForward\DevTools\Console\Output\CommandResponderFactoryInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Coordinates the generation of Fast Forward documentation frontpage and related reports.
 * This class MUST NOT be overridden and SHALL securely combine docs and testing commands.
 */
#[AsCommand(
    name: 'reports',
    description: 'Generates the frontpage for Fast Forward documentation.',
    help: 'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.'
)]
final class ReportsCommand extends BaseCommand
{
    /**
     * Initializes the command with required dependencies.
     *
     * @param ProcessBuilderInterface $processBuilder the builder instance used to construct execution processes
     * @param ProcessQueueInterface $processQueue the execution queue mechanism for running sub-processes
     * @param CommandResponderFactoryInterface $commandResponderFactory the
     *                                                                  structured
     *                                                                  command
     *                                                                  responder
     *                                                                  factory
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly CommandResponderFactoryInterface $commandResponderFactory,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'target',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target directory for the generated reports.',
                default: '.dev-tools',
            )
            ->addOption(
                name: 'coverage',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target directory for the generated test coverage report.',
                default: '.dev-tools/coverage',
            )
            ->addOption(
                name: 'metrics',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Generate code metrics and optionally choose the HTML output directory.',
                default: '.dev-tools/metrics',
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
     * Executes the generation logic for diverse reports.
     *
     * The method MUST run the underlying `docs` and `tests` commands. It SHALL process
     * and generate the frontpage output file successfully.
     *
     * @param InputInterface $input the structured inputs holding specific arguments
     * @param OutputInterface $output the designated output interface
     *
     * @return int the integer outcome from the base process execution
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $responder = $this->commandResponderFactory->from($input, $output);
        $format = $responder->format();
        $textOutput = OutputFormat::TEXT === $format;
        $processOutput = $textOutput ? $output : new BufferedOutput();
        $target = (string) $input->getOption('target');
        $coveragePath = (string) $input->getOption('coverage');
        $metricsPath = (string) $input->getOption('metrics');

        if ($textOutput) {
            $output->writeln('<info>Generating frontpage for Fast Forward documentation...</info>');
        }

        $docsBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--target', $target);

        if (OutputFormat::JSON === $format) {
            $docsBuilder = $docsBuilder->withArgument('--output-format', OutputFormat::JSON->value);
        }

        $docs = $docsBuilder->build('composer dev-tools docs --');

        $coverageBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--no-progress')
            ->withArgument('--coverage-summary')
            ->withArgument('--coverage', $coveragePath);

        if (OutputFormat::JSON === $format) {
            $coverageBuilder = $coverageBuilder->withArgument('--output-format', OutputFormat::JSON->value);
        }

        $coverage = $coverageBuilder->build('composer dev-tools tests --');

        $metricsBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--junit', $coveragePath . '/junit.xml')
            ->withArgument('--target', $metricsPath);

        if (OutputFormat::JSON === $format) {
            $metricsBuilder = $metricsBuilder->withArgument('--output-format', OutputFormat::JSON->value);
        }

        $metrics = $metricsBuilder->build('composer dev-tools metrics --');

        $this->processQueue->add(process: $docs, detached: true);
        $this->processQueue->add(process: $coverage);
        $this->processQueue->add(process: $metrics);

        $result = $this->processQueue->run($processOutput);

        return self::SUCCESS === $result
            ? $responder->success(
                'Documentation reports generated successfully.',
                [
                    'command' => 'reports',
                    'target' => $target,
                    'coverage' => $coveragePath,
                    'metrics' => $metricsPath,
                    'process_output' => $processOutput instanceof BufferedOutput ? $processOutput->fetch() : null,
                ],
            )
            : $responder->failure(
                'Documentation reports generation failed.',
                [
                    'command' => 'reports',
                    'target' => $target,
                    'coverage' => $coveragePath,
                    'metrics' => $metricsPath,
                    'process_output' => $processOutput instanceof BufferedOutput ? $processOutput->fetch() : null,
                ],
            );
    }
}
