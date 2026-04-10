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

use RuntimeException;
use Symfony\Component\Console\Helper\ProcessHelper;
use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\StringInput;
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
     * @param bool $tty
     *
     * @return int the status code of the command execution
     */
    protected function runProcess(Process $command, OutputInterface $output, bool $tty = true): int
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelper('process');

        $command = $command->setWorkingDirectory($this->getCurrentWorkingDirectory());
        $callback = null;

        try {
            $command->setTty($tty);
        } catch (RuntimeException) {
            $output->writeln(
                '<comment>Warning: TTY is not supported. The command may not display output as expected.</comment>'
            );
            $tty = false;
        }

        if (! $tty) {
            $callback = function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            };
        }

        $process = $processHelper->run(output: $output, cmd: $command, callback: $callback);

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
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
        try {
            return $this->getApplication()
                ->getInitialWorkingDirectory() ?: getcwd();
        } catch (RuntimeException) {
            return getcwd();
        }
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

        return $this->getDevToolsFile($filename);
    }

    /**
     * Resolves the absolute path to a file within the fast-forward/dev-tools package.
     *
     * This method uses Composer's InstalledVersions to determine the installation path of the
     * fast-forward/dev-tools package and returns the absolute path to the given filename within it.
     * It is used as a fallback when a configuration file is not found in the project root.
     *
     * @param string $filename the name of the file to resolve within the dev-tools package
     *
     * @return string the absolute path to the file inside the dev-tools package
     */
    protected function getDevToolsFile(string $filename): string
    {
        return Path::makeAbsolute($filename, \dirname(__DIR__, 2));
    }

    /**
     * Configures and executes a registered console command by name.
     *
     * The method MUST run the specified command with the provided input and output interfaces.
     *
     * @param string $command the commandline name of the command to execute
     * @param OutputInterface $output the interface for buffering output
     *
     * @return int the status code resulting from the dispatched command
     */
    protected function runCommand(string $command, OutputInterface $output): int
    {
        return $this->getApplication()
            ->doRun(new StringInput($command), $output);
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
    protected function getProjectName(): string
    {
        $composer = $this->requireComposer();
        $package = $composer->getPackage();

        return $package->getName();
    }

    /**
     * Computes the human-readable description of the current application.
     *
     * The method SHOULD utilize the package description as the title, but MUST provide
     * the raw package name as a fallback mechanism.
     *
     * @return string the computed title or description string
     */
    protected function getProjectDescription(): string
    {
        $composer = $this->requireComposer();
        $package = $composer->getPackage();

        return $package->getDescription();
    }
}
