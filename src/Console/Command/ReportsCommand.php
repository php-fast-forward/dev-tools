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

use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use Composer\Command\BaseCommand;
use Composer\Console\Input\InputOption;
use FastForward\DevTools\Console\Input\HasCacheOption;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use Psr\Log\LoggerInterface;
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
final class ReportsCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasCacheOption;
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * Initializes the command with required dependencies.
     *
     * @param ProcessBuilderInterface $processBuilder the builder instance used to construct execution processes
     * @param ProcessQueueInterface $processQueue the execution queue mechanism for running sub-processes
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
            ->addJsonOption()
            ->addCacheOption('Whether to enable cache writes in nested docs and tests commands.')
            ->addCacheDirOption(
                description: 'Base cache directory used for nested docs and tests command caches.',
                default: ManagedWorkspace::getCacheDirectory('reports'),
            )
            ->addOption(
                name: 'progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to enable progress output from generated report commands.',
            )
            ->addOption(
                name: 'target',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target directory for the generated reports.',
                default: ManagedWorkspace::getOutputDirectory(),
            )
            ->addOption(
                name: 'coverage',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The target directory for the generated test coverage report.',
                default: ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE),
            )
            ->addOption(
                name: 'metrics',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Generate code metrics and optionally choose the HTML output directory.',
                default: ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS),
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
        $jsonOutput = $this->isJsonOutput($input);
        $prettyJsonOutput = $this->isPrettyJsonOutput($input);
        $progress = ! $jsonOutput && (bool) $input->getOption('progress');
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $cacheArgument = $this->resolveCacheArgument($input);
        $cacheDirEnabled = '--no-cache' !== $cacheArgument;
        $target = (string) $input->getOption('target');
        $coveragePath = (string) $input->getOption('coverage');
        $metricsPath = (string) $input->getOption('metrics');

        $this->logger->info('Generating frontpage for Fast Forward documentation...', [
            'input' => $input,
        ]);

        $docsBuilder = $this->processBuilder
            ->withArgument('--target', $target);

        if (null !== $cacheArgument) {
            $docsBuilder = $docsBuilder->withArgument($cacheArgument);
        }

        if ($cacheDirEnabled && null !== $docsCacheDir = $this->resolveCacheDirArgument($input, 'docs')) {
            $docsBuilder = $docsBuilder->withArgument('--cache-dir', $docsCacheDir);
        }

        if ($progress) {
            $docsBuilder = $docsBuilder->withArgument('--progress');
        }

        if ($jsonOutput) {
            $docsBuilder = $docsBuilder->withArgument('--json');
        }

        if ($prettyJsonOutput) {
            $docsBuilder = $docsBuilder->withArgument('--pretty-json');
        }

        $docs = $docsBuilder->build('composer dev-tools docs --');

        $coverageBuilder = $this->processBuilder
            ->withArgument('--coverage-summary')
            ->withArgument('--coverage', $coveragePath);

        if (null !== $cacheArgument) {
            $coverageBuilder = $coverageBuilder->withArgument($cacheArgument);
        }

        if ($cacheDirEnabled && null !== $testsCacheDir = $this->resolveCacheDirArgument($input, 'tests')) {
            $coverageBuilder = $coverageBuilder->withArgument('--cache-dir', $testsCacheDir);
        }

        if ($progress) {
            $coverageBuilder = $coverageBuilder->withArgument('--progress');
        }

        if ($jsonOutput) {
            $coverageBuilder = $coverageBuilder->withArgument('--json');
        }

        if ($prettyJsonOutput) {
            $coverageBuilder = $coverageBuilder->withArgument('--pretty-json');
        }

        $coverage = $coverageBuilder->build('composer dev-tools tests --');

        $metricsBuilder = $this->processBuilder
            ->withArgument('--junit', $coveragePath . '/junit.xml')
            ->withArgument('--target', $metricsPath);

        if ($progress) {
            $metricsBuilder = $metricsBuilder->withArgument('--progress');
        }

        if ($jsonOutput) {
            $metricsBuilder = $metricsBuilder->withArgument('--json');
        }

        if ($prettyJsonOutput) {
            $metricsBuilder = $metricsBuilder->withArgument('--pretty-json');
        }

        $metrics = $metricsBuilder->build('composer dev-tools metrics --');

        $this->processQueue->add(process: $docs, detached: true);
        $this->processQueue->add(process: $coverage);
        $this->processQueue->add(process: $metrics);

        $result = $this->processQueue->run($processOutput);

        if (self::SUCCESS === $result) {
            return $this->success('Documentation reports generated successfully.', $input, [
                'output' => $processOutput,
            ]);
        }

        return $this->failure('Documentation reports generation failed.', $input, [
            'output' => $processOutput,
        ]);
    }
}
