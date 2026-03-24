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
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

abstract class AbstractCommand extends BaseCommand
{
    protected readonly Filesystem $filesystem;

    /**
     * @param Filesystem|null $filesystem
     */
    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();

        parent::__construct();
    }

    /**
     * @param Process $command
     * @param OutputInterface $output
     *
     * @return int
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
            $output->writeln('<comment>Warning: TTY is not supported. The command may not display output as expected.</comment>');

            $callback = function (string $type, string $buffer) use ($output) {
                $output->write($buffer);
            };
        }

        $process = $processHelper->run(
            output: $output, 
            cmd: $command,
            callback: $callback
        );

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
     * @return string
     */
    protected function getCurrentWorkingDirectory(): string
    {
        return $this->getApplication()
            ->getInitialWorkingDirectory() ?: getcwd();
    }

    /**
     * @param string $relativePath
     *
     * @return string
     */
    protected function getAbsolutePath(string $relativePath): string
    {
        if ($this->filesystem->isAbsolutePath($relativePath)) {
            return $relativePath;
        }

        return Path::makeAbsolute($relativePath, $this->getCurrentWorkingDirectory());
    }

    /**
     * @param string $filename
     * @param bool $force
     *
     * @return string
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
     * @param InputInterface $input
     * @param string $commandName
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function runCommand(string $commandName, array|InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();

        $command = $application->find($commandName);

        if (\is_array($input)) {
            $input = new ArrayInput($input);
        }

        return $command->run($input, $output);
    }

    /**
     * @return array
     */
    protected function getPsr4Namespaces(): array
    {
        $composer = $this->requireComposer();
        $autoload = $composer->getPackage()
            ->getAutoload();

        return $autoload['psr-4'] ?? [];
    }

    /**
     * @return string
     */
    protected function getTitle(): string
    {
        $composer = $this->requireComposer();
        $package = $composer->getPackage();

        return $package->getDescription() ?? $package->getName();
    }
}
