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
use Throwable;
use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;

/**
 * Provides operations to inspect, lint, and repair PHPDoc comments across the project.
 * The class MUST NOT be extended and SHALL coordinate tools like PHP-CS-Fixer and Rector.
 */
#[AsCommand(
    name: 'phpdoc',
    description: 'Checks and fixes PHPDocs.',
    help: 'This command checks and fixes PHPDocs in your PHP files.',
)]
final class PhpDocCommand extends AbstractCommand
{
    /**
     * @var string determines the template filename for docheaders
     */
    public const string FILENAME = '.docheader';

    /**
     * @var string stores the underlying configuration file for PHP-CS-Fixer
     */
    public const string CONFIG = '.php-cs-fixer.dist.php';

    /**
     * Creates a new PhpDocCommand instance.
     *
     * @param ComposerJson $composerJson the composer.json accessor
     * @param Filesystem $filesystem the filesystem component
     */
    public function __construct(
        private readonly ComposerJson $composerJson,
        Filesystem $filesystem
    ) {
        return parent::__construct($filesystem);
    }

    /**
     * Configures the PHPDoc command.
     *
     * This method MUST securely configure the expected inputs, such as the `--fix` option.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to fix the PHPDoc issues automatically.',
            );
    }

    /**
     * Executes the PHPDoc checks and rectifications.
     *
     * The method MUST ensure the `.docheader` template is present. It SHALL then invoke
     * PHP-CS-Fixer and Rector. If both succeed, it MUST return `self::SUCCESS`.
     *
     * @param InputInterface $input the command input parameters
     * @param OutputInterface $output the system output handler
     *
     * @return int the success or failure state
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
     * Executes the PHP-CS-Fixer checks internally.
     *
     * The method SHOULD run in dry-run mode unless the fix flag is explicitly provided.
     * It MUST return an integer describing the exit code.
     *
     * @param InputInterface $input the parsed console inputs
     * @param OutputInterface $output the configured outputs
     *
     * @return int the status result of the underlying process
     */
    private function runPhpCsFixer(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [
            $this->getAbsolutePath('vendor/bin/php-cs-fixer'),
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
     * Runs Rector to insert missing method block comments automatically.
     *
     * The method MUST apply the `AddMissingMethodPhpDocRector` constraint locally.
     * It SHALL strictly return an integer denoting success or failure.
     *
     * @param InputInterface $input the incoming console parameters
     * @param OutputInterface $output the outgoing console display
     *
     * @return int the code indicating the process result
     */
    private function runRector(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [
            $this->getAbsolutePath('vendor/bin/rector'),
            'process',
            '--config',
            parent::getConfigFile(RefactorCommand::CONFIG),
            '--autoload-file',
            $this->getAbsolutePath('vendor/autoload.php'),
            '--only',
            '\\' . AddMissingMethodPhpDocRector::class,
        ];

        if (! $input->getOption('fix')) {
            $arguments[] = '--dry-run';
        }

        $command = new Process($arguments);

        return parent::runProcess($command, $output);
    }

    /**
     * Creates the missing document header configuration file if needed.
     *
     * The method MUST query the local filesystem. If the file is missing, it SHOULD copy
     * the tool template into the root folder.
     *
     * @param OutputInterface $output the logger where missing capabilities are announced
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
        $docHeader = str_replace('fast-forward/dev-tools', $this->composerJson->getPackageName(), $docHeader);

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
