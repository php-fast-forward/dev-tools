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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
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
                default: 'vendor,test,tests,tmp,cache,spec,build,backup,resources',
            )
            ->addOption(
                name: 'report-html',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target directory for the generated HTML report.',
                default: 'public/metrics',
            )
            ->addOption(
                name: 'report-json',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target file for the generated JSON report.',
                default: 'public/metrics/report.json',
            )
            ->addOption(
                name: 'report-summary-json',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target file for the generated summary JSON report.',
                default: 'public/metrics/report-summary.json',
            )
            ->addOption(
                name: 'junit',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target file for the generated JUnit XML report.',
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
        $output->writeln('<info>Running code metrics analysis...</info>');

        $processBuilder = $this->processBuilder
            ->withArgument('--ansi')
            ->withArgument('--git', 'git')
            ->withArgument('--exclude', (string) $input->getOption('exclude'))
            ->withArgument('--report-html', $input->getOption('report-html'))
            ->withArgument('--report-json', $input->getOption('report-json'))
            ->withArgument('--report-summary-json', $input->getOption('report-summary-json'));

        if (null !== $input->getOption('junit')) {
            $processBuilder = $processBuilder->withArgument('--junit', $input->getOption('junit'));
        }

        $this->processQueue->add(
            $processBuilder
                ->withArgument('.')
                ->build([\PHP_BINARY, '-derror_reporting=' . self::PHP_ERROR_REPORTING, self::BINARY])
        );

        return $this->processQueue->run($output);
    }
}
