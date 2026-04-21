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
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function rtrim;

#[AsCommand(
    name: 'metrics',
    description: 'Analyzes code metrics with PhpMetrics.',
    help: 'This command runs PhpMetrics to analyze the current working directory.',
)]
final class MetricsCommand extends BaseCommand
{
    /**
     * @var string the bundled PhpMetrics binary path relative to the consumer root
     */
    private const string BINARY = 'vendor/bin/phpmetrics';

    /**
     * @var int the PHP error reporting mask that suppresses deprecations emitted by PhpMetrics internals
     */
    private const int PHP_ERROR_REPORTING = \E_ALL & ~\E_DEPRECATED;

    /**
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble the PhpMetrics process
     * @param ProcessQueueInterface $processQueue the queue used to execute the PhpMetrics process
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly LoggerInterface $logger,
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
                name: 'exclude',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Comma-separated directories that SHOULD be excluded from analysis.',
                default: 'vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources',
            )
            ->addOption(
                name: 'target',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Target directory for the generated metrics reports.',
                default: '.dev-tools/metrics',
            )
            ->addOption(
                name: 'junit',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target file for the generated JUnit XML report.',
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
     * @param InputInterface $input the runtime command input
     * @param OutputInterface $output the console output stream
     *
     * @return int the command execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = 'json' === (string) $input->getOption('output-format');
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;

        $target = rtrim((string) $input->getOption('target'), '/');
        $exclude = (string) $input->getOption('exclude');
        $junit = $input->getOption('junit');

        $this->logger->info('Running code metrics analysis...');

        $processBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--git', 'git')
            ->withArgument('--exclude', $exclude)
            ->withArgument('--report-html', $target)
            ->withArgument('--report-json', $target . '/report.json')
            ->withArgument('--report-summary-json', $target . '/report-summary.json');

        if (null !== $junit) {
            $processBuilder = $processBuilder->withArgument('--junit', $junit);
        }

        $this->processQueue->add(
            $processBuilder
                ->withArgument('.')
                ->build([\PHP_BINARY, '-derror_reporting=' . self::PHP_ERROR_REPORTING, self::BINARY])
        );

        $result = $this->processQueue->run($processOutput);

        $context = [
            'command' => 'metrics',
            'exclude' => $exclude,
            'target' => $target,
            'junit' => $junit,
            'process_output' => $processOutput instanceof BufferedOutput ? $processOutput->fetch() : null,
        ];

        if (self::SUCCESS === $result) {
            $this->logger->info('Code metrics analysis completed successfully.', $context);

            return self::SUCCESS;
        }

        $this->logger->error('Code metrics analysis failed.', $context);

        return self::FAILURE;
    }
}
