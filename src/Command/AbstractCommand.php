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

use Symfony\Component\Console\Helper\ProcessHelper;
use Composer\Command\BaseCommand;
use Composer\InstalledVersions;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

/**
 * Provides a base configuration and common utilities for Composer commands.
 * Extending classes MUST rely on this base abstraction to interact with the console
 * application gracefully, and SHALL adhere to the expected return types for commands.
 */
abstract class AbstractCommand extends BaseCommand
{
    /**
     * @var Filesystem The filesystem instance used for file operations. This property MUST be utilized for interacting with the file system securely.
     */
    protected readonly Filesystem $filesystem;

    /**
     * Constructs a new AbstractCommand instance.
     *
     * The method MAY accept a Filesystem instance; if omitted, it SHALL instantiate a new one.
     *
     * @param Filesystem|null $filesystem the filesystem utility to use
     */
    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();

        parent::__construct();
    }

    /**
     * Executes a given system process gracefully and outputs its buffer.
     *
     * The method MUST execute the provided command ensuring the output is channeled
     * to the OutputInterface. It SHOULD leverage TTY if supported. If the process
     * fails, it MUST return `self::FAILURE`; otherwise, it SHALL return `self::SUCCESS`.
     *
     * @param Process $command the configured process instance to run
     * @param OutputInterface $output the output interface to log warnings or results
     *
     * @return int the status code of the command execution
     */
    protected function runProcess(Process $command, OutputInterface $output): int
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelper('process');

        $command = $command->setWorkingDirectory($this->getCurrentWorkingDirectory());
        $callback = null;

        if (Process::isTtySupported()) {
            $command->setTty(true);
        } else {
            $output->writeln(
                '<comment>Warning: TTY is not supported. The command may not display output as expected.</comment>'
            );

            $callback = function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            };
        }

        $process = $processHelper->run(output: $output, cmd: $command, callback: $callback);

        if (! $process->isSuccessful()) {
            $output->writeln(\sprintf(
                '<error>Command "%s" failed with exit code %d. Please check the output above for details.</error>',
                $command->getCommandLine(),
                $command->getExitCode()
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Retrieves the current working directory of the application.
     *
     * The method MUST return the initial working directory defined by the application.
     * If not available, it SHALL fall back to the safe current working directory.
     *
     * @return string the absolute path to the current working directory
     */
    protected function getCurrentWorkingDirectory(): string
    {
        return $this->getApplication()
            ->getInitialWorkingDirectory() ?: getcwd();
    }

    /**
     * Computes the absolute path for a given relative or absolute path.
     *
     * This method MUST return the exact path if it is already absolute.
     * If relative, it SHALL make it absolute relying on the current working directory.
     *
     * @param string $relativePath the path to evaluate or resolve
     *
     * @return string the resolved absolute path
     */
    protected function getAbsolutePath(string $relativePath): string
    {
        if ($this->filesystem->isAbsolutePath($relativePath)) {
            return $relativePath;
        }

        return Path::makeAbsolute($relativePath, $this->getCurrentWorkingDirectory());
    }

    /**
     * Determines the correct absolute path to a configuration file.
     *
     * The method MUST attempt to resolve the configuration file locally in the working directory.
     * If absent and not forced, it SHALL provide the default equivalent from the package itself.
     *
     * @param string $filename the name of the configuration file
     * @param bool $force determines whether to bypass fallback and forcefully return the local file path
     *
     * @return string the resolved absolute path to the configuration file
     */
    protected function getConfigFile(string $filename, bool $force = false): string
    {
        $rootPackagePath = $this->getCurrentWorkingDirectory();

        if ($force || $this->filesystem->exists($rootPackagePath . '/' . $filename)) {
            return Path::makeAbsolute($filename, $rootPackagePath);
        }

        $devToolsPackagePath = InstalledVersions::getInstallPath('fast-forward/dev-tools');

        return Path::makeAbsolute($filename, $devToolsPackagePath);
    }

    /**
     * Configures and executes a registered console command by name.
     *
     * The method MUST look up the command from the application and run it. It SHALL ignore generic
     * validation errors and route the custom input and output correctly.
     *
     * @param string $commandName the name of the required command
     * @param array|InputInterface $input the input arguments or array definition
     * @param OutputInterface $output the interface for buffering output
     *
     * @return int the status code resulting from the dispatched command
     */
    protected function runCommand(string $commandName, array|InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();

        $command = $application->find($commandName);
        $command->ignoreValidationErrors();

        if (\is_array($input)) {
            $input = new ArrayInput($input);
        }

        return $command->run($input, $output);
    }

    /**
     * Retrieves configured PSR-4 namespaces from the composer configuration.
     *
     * This method SHALL parse the underlying `composer.json` using the Composer instance,
     * and MUST provide an empty array if no specific paths exist.
     *
     * @return array the PSR-4 namespaces mappings
     */
    protected function getPsr4Namespaces(): array
    {
        $composer = $this->requireComposer();
        $autoload = $composer->getPackage()
            ->getAutoload();

        return $autoload['psr-4'] ?? [];
    }

    /**
     * Computes the human-readable title or description of the current application.
     *
     * The method SHOULD utilize the package description as the title, but MUST provide
     * the raw package name as a fallback mechanism.
     *
     * @return string the computed title or description string
     */
    protected function getTitle(): string
    {
        $composer = $this->requireComposer();
        $package = $composer->getPackage();

        return $package->getDescription() ?? $package->getName();
    }
}
