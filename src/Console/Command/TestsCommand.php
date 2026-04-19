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
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_numeric;

/**
 * Facilitates the execution of the PHPUnit testing framework.
 * This class MUST NOT be overridden and SHALL configure testing parameters dynamically.
 */
#[AsCommand(
    name: 'tests',
    description: 'Runs PHPUnit tests.',
    help: 'This command runs PHPUnit to execute your tests.'
)]
final class TestsCommand extends BaseCommand
{
    /**
     * @var string identifies the local configuration file for PHPUnit processes
     */
    public const string CONFIG = 'phpunit.xml';

    /**
     * @param CoverageSummaryLoaderInterface $coverageSummaryLoader the loader used for `coverage-php` summaries
     * @param ComposerJsonInterface $composer the composer.json reader for autoload information
     * @param FilesystemInterface $filesystem the filesystem utility used for path resolution
     * @param FileLocatorInterface $fileLocator the file locator used to resolve PHPUnit configuration
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble the PHPUnit process
     * @param ProcessQueueInterface $processQueue the queue used to execute PHPUnit
     */
    public function __construct(
        private readonly CoverageSummaryLoaderInterface $coverageSummaryLoader,
        private readonly ComposerJsonInterface $composer,
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
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
        $this
            ->addArgument(
                name: 'path',
                mode: InputArgument::OPTIONAL,
                description: 'Path to the tests directory.',
                default: './tests',
            )
            ->addOption(
                name: 'bootstrap',
                shortcut: 'b',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the bootstrap file.',
                default: './vendor/autoload.php',
            )
            ->addOption(
                name: 'cache-dir',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the PHPUnit cache directory.',
                default: './tmp/cache/phpunit',
            )
            ->addOption(
                name: 'no-cache',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to disable PHPUnit caching.',
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
                name: 'no-progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to disable progress output from PHPUnit.',
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
        $output->writeln('<info>Running PHPUnit tests...</info>');

        try {
            $minimumCoverage = $this->resolveMinimumCoverage($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $output->writeln('<error>' . $invalidArgumentException->getMessage() . '</error>');

            return self::FAILURE;
        }

        $processBuilder = $this->processBuilder
            ->withArgument('--configuration', $this->fileLocator->locate(self::CONFIG))
            ->withArgument('--bootstrap', $this->resolvePath($input, 'bootstrap'))
            ->withArgument('--display-deprecations')
            ->withArgument('--display-phpunit-deprecations')
            ->withArgument('--display-incomplete')
            ->withArgument('--display-skipped');

        if ($input->getOption('no-progress')) {
            $processBuilder = $processBuilder->withArgument('--no-progress');
        }

        if (! $input->getOption('no-cache')) {
            $processBuilder = $processBuilder->withArgument(
                '--cache-directory',
                $this->resolvePath($input, 'cache-dir')
            );
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
            $processBuilder
                ->withArgument($input->getArgument('path'))
                ->build('vendor/bin/phpunit')
        );

        $result = $this->processQueue->run($output);

        if (self::SUCCESS !== $result || null === $minimumCoverage || null === $coverageReportPath) {
            return $result;
        }

        return $this->validateMinimumCoverage($coverageReportPath, $minimumCoverage, $output);
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
            : $this->resolvePath($input, 'cache-dir');

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
     * @param OutputInterface $output the output interface to log validation results
     *
     * @return int the final status code after validating minimum coverage
     */
    private function validateMinimumCoverage(
        string $coverageReportPath,
        float $minimumCoverage,
        OutputInterface $output,
    ): int {
        try {
            $coverageSummary = $this->coverageSummaryLoader->load($coverageReportPath);
        } catch (RuntimeException $runtimeException) {
            $output->writeln('<error>' . $runtimeException->getMessage() . '</error>');

            return self::FAILURE;
        }

        $message = \sprintf(
            'Minimum line coverage of %01.2F%% %s. Current coverage: %s (%d/%d lines).',
            $minimumCoverage,
            $coverageSummary->percentage() >= $minimumCoverage ? 'satisfied' : 'was not met',
            $coverageSummary->percentageAsString(),
            $coverageSummary->executedLines(),
            $coverageSummary->executableLines(),
        );

        if ($coverageSummary->percentage() >= $minimumCoverage) {
            $output->writeln('<info>' . $message . '</info>');

            return self::SUCCESS;
        }

        $output->writeln('<error>' . $message . '</error>');

        return self::FAILURE;
    }
}
