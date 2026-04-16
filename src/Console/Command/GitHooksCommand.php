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

use Composer\Command\BaseCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Installs Git hooks and initializes GrumPHP hooks for the consumer repository.
 */
#[AsCommand(
    name: 'git-hooks',
    description: 'Installs Fast Forward Git hooks.',
    help: 'This command runs GrumPHP hook initialization and copies packaged Git hooks into the current repository.'
)]
final class GitHooksCommand extends BaseCommand
{
    /**
     * Creates a new GitHooksCommand instance.
     *
     * @param FilesystemInterface $filesystem the filesystem used to copy hooks
     * @param FileLocatorInterface $fileLocator the locator used to find packaged hooks
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble GrumPHP processes
     * @param ProcessQueueInterface $processQueue the queue used to execute GrumPHP initialization
     * @param Finder $finder the finder used to iterate hook files
     */
    public function __construct(
        private readonly FilesystemInterface $filesystem,
        private readonly FileLocatorInterface $fileLocator,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly Finder $finder,
    ) {
        parent::__construct();
    }

    /**
     * Configures hook source, target, and initialization options.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'source',
                shortcut: 's',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the packaged Git hooks directory.',
                default: 'resources/git-hooks',
            )
            ->addOption(
                name: 'target',
                shortcut: 't',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Path to the target Git hooks directory.',
                default: '.git/hooks',
            )
            ->addOption(
                name: 'skip-grumphp-init',
                mode: InputOption::VALUE_NONE,
                description: 'Skip running grumphp git:init before copying hooks.',
            )
            ->addOption(
                name: 'no-overwrite',
                mode: InputOption::VALUE_NONE,
                description: 'Do not overwrite existing hook files.',
            );
    }

    /**
     * Initializes GrumPHP hooks and copies packaged hooks.
     *
     * @param InputInterface $input the command input
     * @param OutputInterface $output the command output
     *
     * @return int the command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $input->getOption('skip-grumphp-init')) {
            $this->processQueue->add(
                $this->processBuilder
                    ->withArgument('git:init')
                    ->build('vendor/bin/grumphp')
            );

            if (self::SUCCESS !== $this->processQueue->run($output)) {
                return self::FAILURE;
            }
        }

        $sourcePath = $this->fileLocator->locate((string) $input->getOption('source'));
        $targetPath = (string) $this->filesystem->getAbsolutePath((string) $input->getOption('target'));
        $overwrite = ! $input->getOption('no-overwrite');

        $files = $this->finder
            ->files()
            ->in($sourcePath);

        foreach ($files as $file) {
            $hookPath = Path::join($targetPath, $file->getRelativePathname());

            if (! $overwrite && $this->filesystem->exists($hookPath)) {
                $output->writeln(\sprintf('<comment>Skipped existing %s hook.</comment>', $file->getFilename()));

                continue;
            }

            $this->filesystem->copy($file->getRealPath(), $hookPath, $overwrite);
            $this->filesystem->chmod($hookPath, 0o755, 0o755);

            $output->writeln(\sprintf('<info>Installed %s hook.</info>', $file->getFilename()));
        }

        return self::SUCCESS;
    }
}
