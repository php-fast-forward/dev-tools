<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Console\Command;

use FastForward\DevTools\Composer\Json\ComposerJson;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;
use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

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
final class TestsCommand extends AbstractCommand
{
    /**
     * @var string identifies the local configuration file for PHPUnit processes
     */
    public const string CONFIG = 'phpunit.xml';

    /**
     * @param CoverageSummaryLoaderInterface $coverageSummaryLoader the loader used for `coverage-php` summaries
     * @param ComposerJson $composerJson the composer.json reader for autoload information
     * @param Filesystem $filesystem the filesystem utility used for path resolution
     */
    public function __construct(
        private readonly CoverageSummaryLoaderInterface $coverageSummaryLoader,
        private readonly ComposerJson $composerJson,
        Filesystem $filesystem,
    ) {
        parent::__construct($filesystem);
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
                name: 'filter',
                shortcut: 'f',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Filter which tests to run based on a pattern.',
            )
            ->addOption(
                name: 'min-coverage',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Minimum line coverage percentage required for a successful run.',
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

        $arguments = [
            $this->getAbsolutePath('vendor/bin/phpunit'),
            '--configuration=' . parent::getConfigFile(self::CONFIG),
            '--bootstrap=' . $this->resolvePath($input, 'bootstrap'),
            '--display-deprecations',
            '--display-phpunit-deprecations',
            '--display-incomplete',
            '--display-skipped',
        ];

        if (! $input->getOption('no-cache')) {
            $arguments[] = '--cache-directory=' . $this->resolvePath($input, 'cache-dir');
        }

        $coverageReportPath = $this->configureCoverageArguments($input, $arguments, null !== $minimumCoverage);

        if ($input->getOption('filter')) {
            $arguments[] = '--filter=' . $input->getOption('filter');
        }

        $command = new Process([...$arguments, $input->getArgument('path')]);

        $result = parent::runProcess($command, $output);

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
        return $this->getAbsolutePath($input->getOption($option));
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
     * @param array<int, string> $arguments the mutable argument list for the PHPUnit process
     * @param bool $requiresCoverageReport indicates whether a `coverage-php` report is required
     *
     * @return string|null the absolute path to the generated `coverage-php` report
     */
    private function configureCoverageArguments(
        InputInterface $input,
        array &$arguments,
        bool $requiresCoverageReport,
    ): ?string {
        $coverageOption = $input->getOption('coverage');

        if (null === $coverageOption && ! $requiresCoverageReport) {
            return null;
        }

        $coveragePath = null !== $coverageOption
            ? $this->resolvePath($input, 'coverage')
            : $this->resolvePath($input, 'cache-dir');

        foreach ($this->composerJson->getAutoload() as $path) {
            $arguments[] = '--coverage-filter=' . $this->getAbsolutePath($path);
        }

        if (null !== $coverageOption) {
            $arguments[] = '--coverage-text';
            $arguments[] = '--coverage-html=' . $coveragePath;
            $arguments[] = '--testdox-html=' . $coveragePath . '/testdox.html';
            $arguments[] = '--coverage-clover=' . $coveragePath . '/clover.xml';
        }

        $coverageReportPath = $coveragePath . '/coverage.php';
        $arguments[] = '--coverage-php=' . $coverageReportPath;

        return $coverageReportPath;
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
