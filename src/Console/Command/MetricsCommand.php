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
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Metrics\ReportLoaderInterface;
use FastForward\DevTools\Metrics\SummaryRendererInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'metrics',
    description: 'Analyzes code metrics with PhpMetrics.',
    help: 'This command runs PhpMetrics to analyze source code and prints a reduced summary.',
)]
final class MetricsCommand extends BaseCommand
{
    /**
     * @var string the bundled PhpMetrics binary path relative to the consumer root
     */
    private const string BINARY = 'vendor/bin/phpmetrics';

    /**
     * @var string the default cache directory used for temporary metrics reports
     */
    private const string CACHE_DIR = 'tmp/cache/phpmetrics';

    /**
     * @param FilesystemInterface $filesystem the filesystem utility used for path handling and report persistence
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble the PhpMetrics process
     * @param ProcessQueueInterface $processQueue the queue used to execute the PhpMetrics process
     * @param ReportLoaderInterface $reportLoader the loader used to derive a reduced summary from the JSON report
     * @param SummaryRendererInterface $summaryRenderer the renderer used to format the reduced summary
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly ReportLoaderInterface $reportLoader,
        private readonly SummaryRendererInterface $summaryRenderer,
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
                name: 'src',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the source directory that MUST be analyzed.',
                default: 'src',
            )
            ->addOption(
                name: 'exclude',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Comma-separated directories that SHOULD be excluded from analysis.',
                default: 'vendor,test,Test,tests,Tests,testing,Testing,bower_components,node_modules,cache,spec,build',
            )
            ->addOption(
                name: 'report-html',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target directory for the generated HTML report.',
            )
            ->addOption(
                name: 'report-json',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Optional target file for the generated JSON report.',
            )
            ->addOption(
                name: 'cache-dir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the cache directory used for temporary metrics reports.',
                default: self::CACHE_DIR,
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

        try {
            $binary = $this->resolveBinaryPath();
            $source = $this->resolveSourcePath($input);
            $cacheDir = $this->resolveCacheDirectory($input);
            $jsonReport = $this->resolveJsonReportPath($input, $cacheDir);
            $htmlReport = $this->resolveOptionalReportDirectory($input, 'report-html');
        } catch (RuntimeException $runtimeException) {
            $output->writeln('<error>' . $runtimeException->getMessage() . '</error>');

            return self::FAILURE;
        }

        $processBuilder = $this->processBuilder
            ->withArgument('--quiet')
            ->withArgument('--exclude', (string) $input->getOption('exclude'))
            ->withArgument('--report-json', $jsonReport);

        if (null !== $htmlReport) {
            $processBuilder = $processBuilder->withArgument('--report-html', $htmlReport);
        }

        $this->processQueue->add(
            $processBuilder
                ->withArgument($source)
                ->build(self::BINARY)
        );

        $result = $this->processQueue->run($output);

        if (self::SUCCESS !== $result) {
            return $result;
        }

        try {
            $output->writeln($this->summaryRenderer->render($this->reportLoader->load($jsonReport)));
        } catch (RuntimeException $runtimeException) {
            $output->writeln('<error>' . $runtimeException->getMessage() . '</error>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return string the absolute path to the PhpMetrics binary
     */
    private function resolveBinaryPath(): string
    {
        $binary = $this->filesystem->getAbsolutePath(self::BINARY);

        if (! $this->filesystem->exists($binary)) {
            throw new RuntimeException(
                \sprintf(
                    'The PhpMetrics binary was not found at %s. Install dependencies before running the metrics command.',
                    $binary,
                )
            );
        }

        return $binary;
    }

    /**
     * @param InputInterface $input the runtime command input
     *
     * @return string the absolute source directory path
     */
    private function resolveSourcePath(InputInterface $input): string
    {
        $source = $this->filesystem->getAbsolutePath((string) $input->getOption('src'));

        if (! $this->filesystem->exists($source)) {
            throw new RuntimeException(\sprintf('Source directory not found: %s', $source));
        }

        return $source;
    }

    /**
     * @param InputInterface $input the runtime command input
     *
     * @return string the absolute cache directory path
     */
    private function resolveCacheDirectory(InputInterface $input): string
    {
        $cacheDir = $this->filesystem->getAbsolutePath((string) $input->getOption('cache-dir'));
        $this->filesystem->mkdir($cacheDir);

        return $cacheDir;
    }

    /**
     * @param InputInterface $input the runtime command input
     * @param string $cacheDir the absolute cache directory used for fallback output
     *
     * @return string the absolute JSON report path
     */
    private function resolveJsonReportPath(InputInterface $input, string $cacheDir): string
    {
        $reportJson = $input->getOption('report-json');
        $reportJsonPath = null === $reportJson
            ? $this->filesystem->getAbsolutePath('metrics.json', $cacheDir)
            : $this->filesystem->getAbsolutePath((string) $reportJson);

        $this->filesystem->mkdir($this->filesystem->dirname($reportJsonPath));

        return $reportJsonPath;
    }

    /**
     * @param InputInterface $input the runtime command input
     * @param string $option the option that may contain a report directory
     *
     * @return string|null the absolute report directory path when configured
     */
    private function resolveOptionalReportDirectory(InputInterface $input, string $option): ?string
    {
        $reportDirectory = $input->getOption($option);

        if (null === $reportDirectory) {
            return null;
        }

        $reportDirectory = $this->filesystem->getAbsolutePath((string) $reportDirectory);
        $this->filesystem->mkdir($reportDirectory);

        return $reportDirectory;
    }
}
