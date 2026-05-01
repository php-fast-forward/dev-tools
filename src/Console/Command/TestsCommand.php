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

use JsonException;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Input\HasCacheOption;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Environment\RuntimeEnvironmentInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Project\ProjectCapabilitiesResolverInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function is_numeric;
use function Safe\json_decode;
use function Safe\preg_match;

/**
 * Facilitates the execution of the PHPUnit testing framework.
 * This class MUST NOT be overridden and SHALL configure testing parameters dynamically.
 */
#[AsCommand(name: 'reports:tests', description: 'Runs PHPUnit tests.', aliases: ['phpunit', 'tests'])]
final class TestsCommand extends Command
{
    use HasCacheOption;
    use HasJsonOption;
    use LogsCommandResults;

    private const string PROCESS_LABEL = 'Running PHPUnit Tests';

    /**
     * @var string identifies the local configuration file for PHPUnit processes
     */
    public const string CONFIG = 'phpunit.xml';

    /**
     * @param CoverageSummaryLoaderInterface $coverageSummaryLoader the loader used for `coverage-php` summaries
     * @param ComposerJsonInterface $composer the composer.json reader for autoload information
     * @param FilesystemInterface $filesystem the filesystem utility used for path resolution
     * @param BootstrapShimGenerator $bootstrapShimGenerator the generator used to build the PHPUnit bootstrap shim
     * @param FileLocatorInterface $fileLocator the file locator used to resolve PHPUnit configuration
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble the PHPUnit process
     * @param ProcessQueueInterface $processQueue the queue used to execute PHPUnit
     * @param ProjectCapabilitiesResolverInterface $projectCapabilitiesResolver the project capability resolver
     * @param RuntimeEnvironmentInterface $runtimeEnvironment the runtime environment capability resolver
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly CoverageSummaryLoaderInterface $coverageSummaryLoader,
        private readonly ComposerJsonInterface $composer,
        private readonly FilesystemInterface $filesystem,
        private readonly BootstrapShimGenerator $bootstrapShimGenerator,
        private readonly FileLocatorInterface $fileLocator,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly ProjectCapabilitiesResolverInterface $projectCapabilitiesResolver,
        private readonly RuntimeEnvironmentInterface $runtimeEnvironment,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * Configures the testing command input constraints.
     *
     * The method MUST specify valid arguments for testing paths, caching directories,
     * bootstrap scripts, and coverage instructions. It SHALL align with robust testing standards.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setHelp('This command runs PHPUnit to execute your tests.');
        $this
            ->addJsonOption()
            ->addCacheOption('Whether to enable PHPUnit result caching.')
            ->addCacheDirOption(
                description: 'Path to the PHPUnit cache directory.',
                default: ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPUNIT),
            )
            ->addArgument(
                name: 'path',
                mode: InputArgument::OPTIONAL,
                description: 'Path to the tests directory.',
                default: ProjectCapabilitiesResolverInterface::DEFAULT_TESTS_PATH,
            )
            ->addOption(
                name: 'bootstrap',
                shortcut: 'b',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the bootstrap file.',
                default: './vendor/autoload.php',
            )
            ->addOption(
                name: 'coverage',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Whether to generate code coverage reports.',
            )
            ->addOption(
                name: 'coverage-summary',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to show only the summary for text coverage output.',
            )
            ->addOption(
                name: 'filter',
                shortcut: 'f',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Filter which tests to run based on a pattern.',
            )
            ->addOption(
                name: 'min-coverage',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Minimum line coverage percentage required for a successful run.',
            )
            ->addOption(
                name: 'progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to enable progress output from PHPUnit.',
            );
    }

    /**
     * Triggers the PHPUnit engine based on resolved paths and settings.
     *
     * The method MUST assemble the necessary commands to initiate PHPUnit securely.
     * It SHOULD optionally construct advanced configuration arguments such as caching and coverage.
     *
     * @param InputInterface $input the runtime instruction set from the CLI
     * @param OutputInterface $output the console feedback relay
     *
     * @return int the status integer describing the termination code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonOutput = $this->isJsonOutput($input)
            || ($this->runtimeEnvironment->isAgentPresent() && ! $this->runtimeEnvironment->isComposerTestRun());
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $cacheEnabled = $this->isCacheEnabled($input);

        $this->getLogger()
            ->info('Running PHPUnit tests...', [
                'input' => $input,
            ]);

        try {
            $minimumCoverage = $this->resolveMinimumCoverage($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->failure($invalidArgumentException->getMessage(), $input, [
                'output' => $processOutput,
            ]);
        }

        $testsPath = (string) $input->getArgument('path');
        $projectCapabilities = $this->projectCapabilitiesResolver->resolve(testsPath: $testsPath);

        if (! $projectCapabilities->hasTestsPath() && ! $this->isDefaultTestsPath($testsPath)) {
            return $this->failure('Tests path not found: {path}', $input, [
                'output' => $processOutput,
                'path' => $this->filesystem->getAbsolutePath($testsPath),
            ]);
        }

        if (! $projectCapabilities->canRunTests()) {
            return $this->success(
                'Skipping PHPUnit tests because no tests directory or PHP source files were detected.',
                $input,
                [
                    'output' => $processOutput,
                ],
                LogLevel::WARNING,
            );
        }

        $processBuilder = $this->processBuilder
            ->withArgument('--configuration', $this->fileLocator->locate(self::CONFIG))
            ->withArgument('--bootstrap', $this->resolveBootstrapPath($input))
            ->withArgument('--display-deprecations')
            ->withArgument('--display-phpunit-deprecations')
            ->withArgument('--display-incomplete')
            ->withArgument('--display-skipped');

        if (! $input->getOption('progress') || $jsonOutput) {
            $processBuilder = $processBuilder->withArgument('--no-progress');
        }

        if (! $jsonOutput) {
            $processBuilder = $processBuilder->withArgument('--colors=always');
        }

        if ($cacheEnabled) {
            $processBuilder = $processBuilder->withArgument(
                '--cache-result',
            )->withArgument('--cache-directory', $this->resolvePath($input, 'cache-dir'));
        } else {
            $processBuilder = $processBuilder->withArgument('--do-not-cache-result');
        }

        [$processBuilder, $coverageReportPath] = $this->configureCoverageArguments(
            $input,
            $processBuilder,
            null !== $minimumCoverage,
        );

        if ($input->getOption('filter')) {
            $processBuilder = $processBuilder->withArgument('--filter', $input->getOption('filter'));
        }

        $this->processQueue->add(
            process: $processBuilder
                ->withArgument($input->getArgument('path'))
                ->build([DevToolsPathResolver::getPreferredToolBinaryPath('phpunit')]),
            label: self::PROCESS_LABEL,
        );

        $result = $this->processQueue->run($processOutput);
        $processResultContext = $this->resolveProcessResultContext($processOutput, $result, $jsonOutput);

        if (self::SUCCESS !== $result || null === $minimumCoverage || null === $coverageReportPath) {
            if (self::SUCCESS === $result) {
                return $this->success('PHPUnit tests completed successfully.', $input, $processResultContext);
            }

            return $this->failure('PHPUnit tests failed.', $input, $processResultContext);
        }

        [$validationResult, $message, $coverageContext] = $this->validateMinimumCoverage(
            $coverageReportPath,
            $minimumCoverage,
        );

        if (self::SUCCESS === $validationResult) {
            return $this->success($message, $input, [...$processResultContext, ...$coverageContext]);
        }

        return $this->failure($message, $input, [...$processResultContext, ...$coverageContext]);
    }

    /**
     * Builds structured context for the executed PHPUnit process.
     *
     * @param OutputInterface $processOutput the output sink used while the process ran
     * @param int $exitCode the exit code returned by the process queue
     * @param bool $structuredOutput whether the command captured subprocess output for structured logging
     *
     * @return array<string, array<string, mixed>|OutputInterface>
     */
    private function resolveProcessResultContext(
        OutputInterface $processOutput,
        int $exitCode,
        bool $structuredOutput
    ): array {
        if (! $structuredOutput) {
            return [
                'output' => $processOutput,
            ];
        }

        $context = [
            'phpunit' => [
                'tool' => 'phpunit',
                'label' => self::PROCESS_LABEL,
                'exit_code' => $exitCode,
            ],
        ];

        if (! $processOutput instanceof BufferedOutput) {
            return $context;
        }

        $rawOutput = trim($processOutput->fetch());

        if ('' === $rawOutput) {
            return $context;
        }

        [$decoded, $supplementalOutput] = $this->decodeStructuredProcessOutput($rawOutput);

        if (! \is_array($decoded)) {
            $context['phpunit']['raw_output'] = $supplementalOutput;

            return $context;
        }

        $context['phpunit'] = [...$context['phpunit'], ...$decoded];

        if (null !== $supplementalOutput) {
            $context['phpunit']['raw_output'] = $supplementalOutput;
        }

        return $context;
    }

    /**
     * Attempts to decode structured PHPUnit output while preserving any
     * non-JSON prelude that was emitted before the final reporter payload.
     *
     * @param string $rawOutput the captured subprocess output
     *
     * @return array{array<string, mixed>|null, string|null} decoded payload and preserved supplemental output
     */
    private function decodeStructuredProcessOutput(string $rawOutput): array
    {
        try {
            $decoded = json_decode($rawOutput, true);

            return [\is_array($decoded) ? $decoded : null, null];
        } catch (JsonException) {
        }

        if (1 !== preg_match('/^(?P<prefix>.*?)(?P<payload>\{\s*"result".*)$/s', $rawOutput, $matches)) {
            return [null, $rawOutput];
        }

        try {
            $decoded = json_decode($matches['payload'], true);
        } catch (JsonException) {
            return [null, $rawOutput];
        }

        if (! \is_array($decoded)) {
            return [null, $rawOutput];
        }

        $prefix = trim($matches['prefix']);

        return [$decoded, '' === $prefix ? null : $prefix];
    }

    /**
     * Safely constructs an absolute path tied to a defined capability option.
     *
     * The method MUST compute absolute properties based on the supplied input parameters.
     * It SHALL strictly return a securely bounded path string.
     *
     * @param InputInterface $input the raw parameter definitions
     * @param string $option the requested option key to resolve
     *
     * @return string validated absolute path string
     */
    private function resolvePath(InputInterface $input, string $option): string
    {
        return $this->filesystem->getAbsolutePath($input->getOption($option));
    }

    /**
     * Detects whether a tests path option still points at the default project tests directory.
     *
     * @param string $testsPath the tests path argument received from the CLI
     *
     * @return bool true when the provided path is equivalent to the default tests directory
     */
    private function isDefaultTestsPath(string $testsPath): bool
    {
        return $this->normalizeProjectRelativePath($testsPath) === $this->normalizeProjectRelativePath(
            ProjectCapabilitiesResolverInterface::DEFAULT_TESTS_PATH
        );
    }

    /**
     * Normalizes a project-relative path for resilient default-option comparisons.
     *
     * @param string $path the project-relative path to normalize
     *
     * @return string the normalized project-relative path
     */
    private function normalizeProjectRelativePath(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);

        while (str_starts_with($normalizedPath, './')) {
            $normalizedPath = substr($normalizedPath, 2);
        }

        return rtrim($normalizedPath, '/');
    }

    /**
     * Creates the bootstrap shim path passed to PHPUnit.
     *
     * @param InputInterface $input the raw parameter definitions
     *
     * @return string the generated bootstrap shim path
     */
    private function resolveBootstrapPath(InputInterface $input): string
    {
        return $this->bootstrapShimGenerator->generate(
            $this->resolvePath($input, 'bootstrap'),
            $this->resolvePath($input, 'cache-dir'),
        );
    }

    /**
     * @param InputInterface $input the raw parameter definitions
     *
     * @return float|null the validated minimum coverage percentage, if configured
     */
    private function resolveMinimumCoverage(InputInterface $input): ?float
    {
        $minimumCoverage = $input->getOption('min-coverage');

        if (null === $minimumCoverage) {
            return null;
        }

        if (! is_numeric($minimumCoverage)) {
            throw new InvalidArgumentException('The --min-coverage option MUST be a numeric percentage.');
        }

        $minimumCoverage = (float) $minimumCoverage;

        if (0.0 > $minimumCoverage || 100.0 < $minimumCoverage) {
            throw new InvalidArgumentException('The --min-coverage option MUST be between 0 and 100.');
        }

        return $minimumCoverage;
    }

    /**
     * @param InputInterface $input the raw parameter definitions
     * @param ProcessBuilderInterface $processBuilder the process builder to extend with coverage arguments
     * @param bool $requiresCoverageReport indicates whether a `coverage-php` report is required
     *
     * @return array{ProcessBuilderInterface, string|null} the extended builder and generated `coverage-php` report path
     */
    private function configureCoverageArguments(
        InputInterface $input,
        ProcessBuilderInterface $processBuilder,
        bool $requiresCoverageReport,
    ): array {
        $coverageOption = $input->getOption('coverage');

        if (null === $coverageOption && ! $requiresCoverageReport) {
            return [$processBuilder, null];
        }

        $coveragePath = null !== $coverageOption
            ? $this->resolvePath($input, 'coverage')
            : (
                $this->isCacheEnabled($input)
                    ? $this->resolvePath($input, 'cache-dir')
                    : ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE)
            );

        foreach ($this->composer->getAutoload('psr-4') as $path) {
            $processBuilder = $processBuilder->withArgument(
                '--coverage-filter',
                $this->filesystem->getAbsolutePath($path)
            );
        }

        if (null !== $coverageOption) {
            $processBuilder = $processBuilder
                ->withArgument('--coverage-text')
                ->withArgument('--coverage-html', $coveragePath)
                ->withArgument('--testdox-html', $coveragePath . '/testdox.html')
                ->withArgument('--coverage-clover', $coveragePath . '/clover.xml')
                ->withArgument('--log-junit', $coveragePath . '/junit.xml');

            if ($input->getOption('coverage-summary')) {
                $processBuilder = $processBuilder->withArgument('--only-summary-for-coverage-text');
            }
        }

        $coverageReportPath = $coveragePath . '/coverage.php';
        $processBuilder = $processBuilder->withArgument('--coverage-php', $coverageReportPath);

        return [$processBuilder, $coverageReportPath];
    }

    /**
     * @param string $coverageReportPath the generated `coverage-php` report path
     * @param float $minimumCoverage the required line coverage percentage
     *
     * @return array{int, string, array<string, float|int|string|null>} validation result, human message, and structured coverage context
     */
    private function validateMinimumCoverage(string $coverageReportPath, float $minimumCoverage): array
    {
        try {
            $coverageSummary = $this->coverageSummaryLoader->load($coverageReportPath);
        } catch (RuntimeException $runtimeException) {
            return [
                self::FAILURE,
                $runtimeException->getMessage(),
                [
                    'line_coverage' => null,
                    'covered_lines' => null,
                    'total_lines' => null,
                ],
            ];
        }

        $message = \sprintf(
            'Minimum line coverage of %01.2F%% %s. Current coverage: %s (%d/%d lines).',
            $minimumCoverage,
            $coverageSummary->percentage() >= $minimumCoverage ? 'satisfied' : 'was not met',
            $coverageSummary->percentageAsString(),
            $coverageSummary->executedLines(),
            $coverageSummary->executableLines(),
        );

        return [
            $coverageSummary->percentage() >= $minimumCoverage ? self::SUCCESS : self::FAILURE,
            $message,
            [
                'line_coverage' => $coverageSummary->percentage(),
                'covered_lines' => $coverageSummary->executedLines(),
                'total_lines' => $coverageSummary->executableLines(),
            ],
        ];
    }
}
