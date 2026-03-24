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

use Throwable;
use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;

final class PhpDocCommand extends AbstractCommand
{
    public const string FILENAME = '.docheader';

    public const string CONFIG = '.php-cs-fixer.dist.php';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('phpdoc')
            ->setDescription('Checks and fixes PHPDocs.')
            ->setHelp('This command checks and fixes PHPDocs in your PHP files.')
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to fix the PHPDoc issues automatically.',
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Checking and fixing PHPDocs...</info>');

        $this->ensureDocHeaderExists($output);

        $phpCsFixerResult = $this->runPhpCsFixer($input, $output);
        $rectorResult = $this->runRector($input, $output);

        return self::SUCCESS === $phpCsFixerResult && self::SUCCESS === $rectorResult ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    private function runPhpCsFixer(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [
            \dirname(__DIR__, 2) . '/vendor/bin/php-cs-fixer',
            'fix',
            '--config=' . parent::getConfigFile(self::CONFIG),
            '--cache-file=' . $this->getCurrentWorkingDirectory() . '/tmp/cache/.php-cs-fixer.cache',
            '--diff',
        ];

        if (! $input->getOption('fix')) {
            $arguments[] = '--dry-run';
        }

        $command = new Process($arguments);

        return parent::runProcess($command, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    private function runRector(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [
            \dirname(__DIR__, 2) . '/vendor/bin/rector',
            'process',
            '--config',
            parent::getConfigFile(RefactorCommand::CONFIG),
            '--only',
            AddMissingMethodPhpDocRector::class,
        ];

        if (! $input->getOption('fix')) {
            $arguments[] = '--dry-run';
        }

        $command = new Process($arguments);

        return parent::runProcess($command, $output);
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    private function ensureDocHeaderExists(OutputInterface $output): void
    {
        $projectDocHeader = self::getConfigFile(self::FILENAME, true);

        if ($this->filesystem->exists($projectDocHeader)) {
            return;
        }

        $repositoryDocHeader = self::getConfigFile(self::FILENAME);
        $docHeader = file_get_contents($repositoryDocHeader);

        try {
            $composer = $this->requireComposer();
            $rootPackageName = $composer->getPackage()
                ->getName();

            if ('' !== $rootPackageName) {
                $docHeader = str_replace('fast-forward/dev-tools', $rootPackageName, $docHeader);
            }
        } catch (Throwable) {
        }

        try {
            $this->filesystem->dumpFile($projectDocHeader, $docHeader);
        } catch (Throwable) {
            $output->writeln(
                '<comment>Skipping .docheader creation because the destination file could not be written.</comment>'
            );

            return;
        }

        $output->writeln('<info>Created .docheader from repository template.</info>');
    }
}
