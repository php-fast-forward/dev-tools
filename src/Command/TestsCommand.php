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

namespace FastForward\DevTools\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Facilitates the execution of the PHPUnit testing framework.
 * This class MUST NOT be overridden and SHALL configure testing parameters dynamically.
 */
final class TestsCommand extends AbstractCommand
{
    /**
     * @var string identifies the local configuration file for PHPUnit processes
     */
    public const string CONFIG = 'phpunit.xml';

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
            ->setName('tests')
            ->setDescription('Runs PHPUnit tests.')
            ->setHelp('This command runs PHPUnit to execute your tests.')
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
                name: 'parallel',
                shortcut: 'p',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Run tests in parallel using ParaTest. Optional: specify number of workers (e.g., --parallel or --parallel=4).',
                default: null,
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

        $parallel = $input->getOption('parallel');
        $runner = $this->getTestRunner($parallel !== false ? $parallel : null);

        $arguments = [
            $runner,
            '--configuration=' . parent::getConfigFile(self::CONFIG),
            '--bootstrap=' . $this->resolvePath($input, 'bootstrap'),
            '--display-deprecations',
            '--display-phpunit-deprecations',
            '--display-incomplete',
            '--display-skipped',
        ];

        if ($parallel !== null) {
            if (is_numeric($parallel)) {
                $arguments[] = '--processes=' . (int) $parallel;
            }
            $arguments[] = '--parallel';
        }

        if (! $input->getOption('no-cache')) {
            $arguments[] = '--cache-directory=' . $this->resolvePath($input, 'cache-dir');
        }

        if ($input->getOption('coverage')) {
            $output->writeln(
                '<info>Generating code coverage reports on path: ' . $this->resolvePath($input, 'coverage') . '</info>'
            );

            foreach ($this->getPsr4Namespaces() as $path) {
                $arguments[] = '--coverage-filter=' . $this->getAbsolutePath($path);
            }

            $arguments[] = '--coverage-text';
            $arguments[] = '--coverage-html=' . $this->resolvePath($input, 'coverage');
            $arguments[] = '--testdox-html=' . $this->resolvePath($input, 'coverage') . '/testdox.html';
            $arguments[] = '--coverage-clover=' . $this->resolvePath($input, 'coverage') . '/clover.xml';
            $arguments[] = '--coverage-php=' . $this->resolvePath($input, 'coverage') . '/coverage.php';
        }

        if ($input->getOption('filter')) {
            $arguments[] = '--filter=' . $input->getOption('filter');
        }

        $command = new Process([...$arguments, $input->getArgument('path')]);

        return parent::runProcess($command, $output);
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
     * Determines the test runner to use based on parallel execution flag.
     *
     * The method MUST return the appropriate test runner binary path.
     * If parallel is enabled, it SHALL return paratest; otherwise, it SHALL return phpunit.
     *
     * @param string|null $parallel the parallel option value
     *
     * @return string the path to the test runner binary
     */
    private function getTestRunner(?string $parallel): string
    {
        if ($parallel !== null && $this->filesystem->exists($this->getAbsolutePath('vendor/bin/paratest'))) {
            return $this->getAbsolutePath('vendor/bin/paratest');
        }

        return $this->getAbsolutePath('vendor/bin/phpunit');
    }
}
